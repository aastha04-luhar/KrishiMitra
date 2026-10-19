<?php
require_once '../config/database.php';

// Only logged-in admins
if (!isLoggedIn('admin')) {
    redirect('admin_login.php');
}

$alerts = [];

// 1. Pending farmer approvals
$n = $pdo->query("SELECT COUNT(*) FROM farmers WHERE approved = 0 AND status = 'inactive'")->fetchColumn();
if ($n > 0) {
    $alerts[] = ['type' => 'warning', 'icon' => 'fa-user-clock', 'title' => "$n farmer registration(s) need approval", 'desc' => 'Review and approve pending farmer accounts.', 'href' => 'manage_farmers.php'];
}

// 2. Blocked / inactive farmers
$n = $pdo->query("SELECT COUNT(*) FROM farmers WHERE status = 'inactive'")->fetchColumn();
$alerts[] = ['type' => 'info', 'icon' => 'fa-user-slash', 'title' => "$n farmer account(s) inactive", 'desc' => 'Inactive accounts are not receiving advisories.', 'href' => 'manage_farmers.php?status=inactive'];

// 3. Deficient soil records
$soil = $pdo->query("SELECT nitrogen, phosphorus, potassium FROM soil_data")->fetchAll();
$deficient = 0; $attention = 0;
foreach ($soil as $sd) {
    $h = getSoilHealthStatus($sd['nitrogen'], $sd['phosphorus'], $sd['potassium']);
    if ($h['status'] === 'danger') $deficient++;
    elseif ($h['status'] === 'warning') $attention++;
}
if ($deficient > 0) {
    $alerts[] = ['type' => 'danger', 'icon' => 'fa-circle-exclamation', 'title' => "$deficient soil test(s) are nutrient-deficient", 'desc' => 'These farms may need fertilization advice.', 'href' => '#soil'];
}
if ($attention > 0) {
    $alerts[] = ['type' => 'info', 'icon' => 'fa-triangle-exclamation', 'title' => "$attention soil test(s) need attention", 'desc' => 'Nutrient levels below ideal threshold.', 'href' => 'reports_analytics.php'];
}

// 4. Farmers with no farms
$n = $pdo->query("SELECT COUNT(*) FROM farmers f LEFT JOIN farms fa ON fa.farmer_id = f.id WHERE fa.id IS NULL")->fetchColumn();
if ($n > 0) {
    $alerts[] = ['type' => 'info', 'icon' => 'fa-tractor', 'title' => "$n farmer(s) have not added any farms", 'desc' => 'Encourage them to register their farms.', 'href' => 'reports_analytics.php'];
}

// 5. No soil data
$n = $pdo->query("SELECT COUNT(*) FROM farmers f LEFT JOIN soil_data sd ON sd.farmer_id = f.id WHERE sd.id IS NULL")->fetchColumn();
if ($n > 0) {
    $alerts[] = ['type' => 'info', 'icon' => 'fa-flask', 'title' => "$n farmer(s) have no soil test data", 'desc' => 'Soil data improves crop recommendations.', 'href' => 'reports_analytics.php'];
}

// 6. Low crop database coverage
$crops = (int)$pdo->query("SELECT COUNT(*) FROM crops")->fetchColumn();
if ($crops < 4) {
    $alerts[] = ['type' => 'danger', 'icon' => 'fa-seedling', 'title' => "Crop database is small ($crops crops)", 'desc' => 'Add more crops to improve recommendation coverage.', 'href' => 'crop_database.php'];
}

$summary = ['warning' => 0, 'danger' => 0, 'info' => 0];
foreach ($alerts as $a) { $summary[$a['type']]++; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerts &amp; Notifications - KrishiMitra Admin</title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="stylesheet" href="manage_farmers.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .al-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:22px; }
        .al-stat { background:#fff; border:1px solid #e4ebe6; border-radius:12px; padding:18px; text-align:center; }
        .al-stat .val { font-size:26px; font-weight:600; color:#1e8449; }
        .al-stat .lbl { color:#8a958e; font-size:10px; text-transform:uppercase; }
        .alert-item { display:flex; align-items:flex-start; gap:14px; background:#fff; border:1px solid #e4ebe6; border-left:4px solid #ccc; border-radius:10px; padding:16px 18px; margin-bottom:12px; box-shadow:0 1px 6px rgba(0,0,0,0.03); }
        .alert-item.warning { border-left-color:#f1a62e; }
        .alert-item.danger { border-left-color:#e05d4a; }
        .alert-item.info { border-left-color:#3498db; }
        .alert-item i.big { font-size:22px; width:34px; }
        .alert-item.warning i.big { color:#f1a62e; }
        .alert-item.danger i.big { color:#e05d4a; }
        .alert-item.info i.big { color:#3498db; }
        .alert-item .at h4 { color:#28543a; font-size:14px; margin:0 0 2px; }
        .alert-item .at p { color:#7b8880; font-size:12px; margin:0 0 6px; }
        .alert-item .at a { color:#1e8449; font-size:12px; font-weight:600; text-decoration:none; }
    </style>
</head>
<body>

<div class="navbar">
    <div class="brand">
        <img src="../assets/images/logo1.png" alt="KrishiMitra Logo" onerror="this.src='../pictures/images/logo1.png'">
        <h2>KrishiMitra</h2>
    </div>
    <div class="nav-links">
        <span><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
        <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="layout">
    <div class="sidebar">
        <h3>Admin Controls</h3>
        <a href="admin_dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a>
        <a href="manage_farmers.php"><i class="fas fa-users"></i> Farmer Management</a>
        <a href="crop_database.php"><i class="fas fa-seedling"></i> Crop Database</a>
        <a href="weather_admin.php"><i class="fas fa-cloud-sun"></i> Weather System</a>
        <a href="market_rates.php"><i class="fas fa-rupee-sign"></i> Market Rates</a>
        <a href="pest_disease.php"><i class="fas fa-bug"></i> Pest &amp; Disease Data</a>
        <a class="active" href="alerts_notifications.php"><i class="fas fa-bell"></i> Alerts &amp; Notifications</a>
        <a href="reports_analytics.php"><i class="fas fa-file-alt"></i> Reports &amp; Analytics</a>
        <a href="system_logs.php"><i class="fas fa-database"></i> System Logs</a>
    </div>

    <div class="content">
        <div class="welcome-box">
            <h2><i class="fas fa-bell"></i> Alerts &amp; Notifications</h2>
            <p>System-generated alerts based on current platform data</p>
        </div>

        <div class="al-stats">
            <div class="al-stat"><div class="val"><?php echo $summary['danger']; ?></div><div class="lbl">Critical</div></div>
            <div class="al-stat"><div class="val"><?php echo $summary['warning']; ?></div><div class="lbl">Warnings</div></div>
            <div class="al-stat"><div class="val"><?php echo $summary['info']; ?></div><div class="lbl">Info</div></div>
        </div>

        <?php if (count($alerts) > 0): ?>
            <?php foreach ($alerts as $a): ?>
                <div class="alert-item <?php echo $a['type']; ?>">
                    <i class="fas <?php echo $a['icon']; ?> big"></i>
                    <div class="at">
                        <h4><?php echo $a['title']; ?></h4>
                        <p><?php echo $a['desc']; ?></p>
                        <a href="<?php echo $a['href']; ?>">View details <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-data"><i class="fas fa-bell-slash"></i><p>No alerts right now. Everything looks good.</p></div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
