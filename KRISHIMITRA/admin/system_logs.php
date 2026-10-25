<?php
require_once '../config/database.php';

// Only logged-in admins
if (!isLoggedIn('admin')) {
    redirect('admin_login.php');
}

$logs = [];

// Farmer registrations
$rows = $pdo->query("SELECT name, created_at FROM farmers ORDER BY created_at DESC")->fetchAll();
foreach ($rows as $r) {
    $logs[] = ['time' => $r['created_at'], 'type' => 'farmer', 'icon' => 'fa-user-plus', 'text' => "Farmer '{$r['name']}' registered."];
}

// Farm additions
$rows = $pdo->query("
    SELECT fa.farm_name, f.name AS farmer_name, fa.created_at
    FROM farms fa LEFT JOIN farmers f ON fa.farmer_id = f.id
    ORDER BY fa.created_at DESC
")->fetchAll();
foreach ($rows as $r) {
    $owner = $r['farmer_name'] ? $r['farmer_name'] : 'Unknown';
    $logs[] = ['time' => $r['created_at'], 'type' => 'farm', 'icon' => 'fa-tractor', 'text' => "Farm '{$r['farm_name']}' added by {$owner}."];
}

// Soil tests
$rows = $pdo->query("
    SELECT sd.test_date, sd.created_at, f.name AS farmer_name
    FROM soil_data sd LEFT JOIN farmers f ON sd.farmer_id = f.id
    ORDER BY sd.created_at DESC
")->fetchAll();
foreach ($rows as $r) {
    $owner = $r['farmer_name'] ? $r['farmer_name'] : 'Unknown';
    $logs[] = ['time' => $r['created_at'], 'type' => 'soil', 'icon' => 'fa-flask', 'text' => "Soil test recorded for {$owner}."];
}

// Admin registrations
$rows = $pdo->query("SELECT name, created_at FROM admins ORDER BY created_at DESC")->fetchAll();
foreach ($rows as $r) {
    $logs[] = ['time' => $r['created_at'], 'type' => 'admin', 'icon' => 'fa-user-shield', 'text' => "Admin '{$r['name']}' account created."];
}

// Sort by time descending
usort($logs, function ($a, $b) {
    $ta = strtotime($a['time']); $tb = strtotime($b['time']);
    return $tb - $ta;
});

$types = ['farmer' => 0, 'farm' => 0, 'soil' => 0, 'admin' => 0];
foreach ($logs as $l) { $types[$l['type']]++; }

// Optional filter
$filter = isset($_GET['type']) ? sanitize($_GET['type']) : '';
if ($filter && isset($types[$filter])) {
    $logs = array_values(array_filter($logs, function ($l) use ($filter) { return $l['type'] === $filter; }));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - KrishiMitra Admin</title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="stylesheet" href="manage_farmers.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .log-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:22px; }
        .log-stat { background:#fff; border:1px solid #e4ebe6; border-radius:12px; padding:16px; text-align:center; }
        .log-stat .val { font-size:24px; font-weight:600; color:#1e8449; }
        .log-stat .lbl { color:#8a958e; font-size:10px; text-transform:uppercase; }
        .log-item { display:flex; align-items:center; gap:14px; background:#fff; border:1px solid #e4ebe6; border-radius:10px; padding:13px 16px; margin-bottom:10px; }
        .log-item i { font-size:18px; width:30px; color:#27ae60; }
        .log-item .lt { flex:1; color:#526158; font-size:13px; }
        .log-item .tt { color:#9aa39d; font-size:11px; white-space:nowrap; }
        @media (max-width:800px){ .log-stats{grid-template-columns:1fr 1fr;} }
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
        <a href="alerts_notifications.php"><i class="fas fa-bell"></i> Alerts &amp; Notifications</a>
        <a href="reports_analytics.php"><i class="fas fa-file-alt"></i> Reports &amp; Analytics</a>
        <a class="active" href="system_logs.php"><i class="fas fa-database"></i> System Logs</a>
    </div>

    <div class="content">
        <div class="welcome-box">
            <h2><i class="fas fa-database"></i> System Logs</h2>
            <p>Recent platform activity across farmers, farms and soil data</p>
        </div>

        <div class="log-stats">
            <div class="log-stat"><div class="val"><?php echo $types['farmer']; ?></div><div class="lbl">Farmer</div></div>
            <div class="log-stat"><div class="val"><?php echo $types['farm']; ?></div><div class="lbl">Farm</div></div>
            <div class="log-stat"><div class="val"><?php echo $types['soil']; ?></div><div class="lbl">Soil Test</div></div>
            <div class="log-stat"><div class="val"><?php echo $types['admin']; ?></div><div class="lbl">Admin</div></div>
        </div>

        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <div class="filter-group">
                    <select name="type">
                        <option value="">All Activity</option>
                        <option value="farmer" <?php echo $filter === 'farmer' ? 'selected' : ''; ?>>Farmer</option>
                        <option value="farm" <?php echo $filter === 'farm' ? 'selected' : ''; ?>>Farm</option>
                        <option value="soil" <?php echo $filter === 'soil' ? 'selected' : ''; ?>>Soil Test</option>
                        <option value="admin" <?php echo $filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
                </div>
                <div class="filter-group">
                    <a href="system_logs.php" class="btn-reset"><i class="fas fa-undo"></i> Reset</a>
                </div>
            </form>
        </div>

        <?php if (count($logs) > 0): ?>
            <?php foreach ($logs as $l): ?>
                <div class="log-item">
                    <i class="fas <?php echo $l['icon']; ?>"></i>
                    <div class="lt"><?php echo htmlspecialchars($l['text']); ?></div>
                    <div class="tt"><i class="far fa-clock"></i> <?php echo date('d M Y, h:i A', strtotime($l['time'])); ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-data"><i class="fas fa-database"></i><p>No logs found.</p></div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
