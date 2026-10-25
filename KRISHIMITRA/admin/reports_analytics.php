<?php
require_once '../config/database.php';

// Only logged-in admins
if (!isLoggedIn('admin')) {
    redirect('admin_login.php');
}

// ---- Aggregate stats ----
$total_farmers = (int)$pdo->query("SELECT COUNT(*) FROM farmers")->fetchColumn();
$active_farmers = (int)$pdo->query("SELECT COUNT(*) FROM farmers WHERE status='active'")->fetchColumn();
$total_farms = (int)$pdo->query("SELECT COUNT(*) FROM farms")->fetchColumn();
$total_soil_tests = (int)$pdo->query("SELECT COUNT(*) FROM soil_data")->fetchColumn();
$total_crops = (int)$pdo->query("SELECT COUNT(*) FROM crops")->fetchColumn();
$total_land = (float)$pdo->query("SELECT COALESCE(SUM(farm_size),0) FROM farms")->fetchColumn();

// ---- Soil health distribution ----
$healthy = $warning = $deficient = 0;
$soil = $pdo->query("SELECT nitrogen, phosphorus, potassium FROM soil_data")->fetchAll();
foreach ($soil as $sd) {
    $h = getSoilHealthStatus($sd['nitrogen'], $sd['phosphorus'], $sd['potassium']);
    if ($h['status'] === 'healthy') $healthy++;
    elseif ($h['status'] === 'warning') $warning++;
    else $deficient++;
}
$soil_total = $healthy + $warning + $deficient;

// ---- Farms by farmer (top contributors) ----
$farms_by_farmer = $pdo->query("
    SELECT f.name AS farmer_name, COUNT(fa.id) AS farm_count,
           COALESCE(SUM(fa.farm_size),0) AS land
    FROM farmers f
    LEFT JOIN farms fa ON fa.farmer_id = f.id
    GROUP BY f.id ORDER BY farm_count DESC, land DESC
")->fetchAll();

// ---- Soil tests per farmer ----
$tests_per_farmer = $pdo->query("
    SELECT f.name AS farmer_name, COUNT(sd.id) AS tests
    FROM farmers f
    LEFT JOIN soil_data sd ON sd.farmer_id = f.id
    GROUP BY f.id ORDER BY tests DESC
")->fetchAll();

// ---- Recent registrations / soil tests (activity log) ----
$recent_soil = $pdo->query("
    SELECT sd.*, f.name AS farmer_name, fa.farm_name
    FROM soil_data sd
    LEFT JOIN farmers f ON sd.farmer_id = f.id
    LEFT JOIN farms fa ON sd.farm_id = fa.id
    ORDER BY sd.test_date DESC LIMIT 8
")->fetchAll();

// ---- Location distribution ----
$loc_dist = $pdo->query("SELECT location, COUNT(*) c FROM farmers GROUP BY location ORDER BY c DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports &amp; Analytics - KrishiMitra Admin</title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="stylesheet" href="manage_farmers.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .an-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px; margin-bottom:22px; }
        .an-stat { background:#fff; border:1px solid #e4ebe6; border-radius:12px; padding:18px; text-align:center; }
        .an-stat i { font-size:24px; color:#27ae60; }
        .an-stat .val { font-size:26px; font-weight:600; color:#1e8449; margin:4px 0 2px; }
        .an-stat .lbl { color:#8a958e; font-size:10px; text-transform:uppercase; letter-spacing:.5px; }
        .rp-table { width:100%; border-collapse:collapse; }
        .rp-table th { padding:11px 12px; background:#f6f9f7; color:#647168; text-align:left; font-size:10px; font-weight:600; border-bottom:1px solid #e5ebe7; }
        .rp-table td { padding:12px; color:#6b776f; font-size:12px; border-bottom:1px solid #edf1ee; }
        .bar-row { display:flex; align-items:center; gap:12px; margin-bottom:12px; font-size:12px; color:#526158; }
        .bar-row .bar { flex:1; height:12px; background:#edf2ee; border-radius:6px; overflow:hidden; }
        .bar-row .bar span { display:block; height:100%; }
        .bar-healthy .bar span { background:#27ae60; }
        .bar-warning .bar span { background:#f1a62e; }
        .bar-deficient .bar span { background:#e05d4a; }
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
        <a class="active" href="reports_analytics.php"><i class="fas fa-file-alt"></i> Reports &amp; Analytics</a>
        <a href="system_logs.php"><i class="fas fa-database"></i> System Logs</a>
    </div>

    <div class="content">
        <div class="welcome-box">
            <h2><i class="fas fa-file-alt"></i> Reports &amp; Analytics</h2>
            <p>Platform-wide statistics across farmers, farms and soil data</p>
        </div>

        <!-- KPI -->
        <div class="an-stats">
            <div class="an-stat"><i class="fas fa-users"></i><div class="val"><?php echo $total_farmers; ?></div><div class="lbl">Farmers</div></div>
            <div class="an-stat"><i class="fas fa-tractor"></i><div class="val"><?php echo $total_farms; ?></div><div class="lbl">Farms</div></div>
            <div class="an-stat"><i class="fas fa-flask"></i><div class="val"><?php echo $total_soil_tests; ?></div><div class="lbl">Soil Tests</div></div>
            <div class="an-stat"><i class="fas fa-seedling"></i><div class="val"><?php echo $total_crops; ?></div><div class="lbl">Crops</div></div>
            <div class="an-stat"><i class="fas fa-map"></i><div class="val"><?php echo number_format($total_land, 1); ?></div><div class="lbl">Acres</div></div>
        </div>

        <!-- Soil health -->
        <div class="farmers-table" style="margin-bottom:22px;">
            <div class="table-header"><h3><i class="fas fa-seedling"></i> Soil Health Summary</h3></div>
            <?php if ($soil_total > 0): ?>
                <?php $t = $soil_total; ?>
                <div class="bar-row bar-healthy"><span>Good (<?php echo $healthy; ?>)</span><div class="bar"><span style="width:<?php echo round($healthy/$t*100); ?>%;"></span></div></div>
                <div class="bar-row bar-warning"><span>Needs Attention (<?php echo $warning; ?>)</span><div class="bar"><span style="width:<?php echo round($warning/$t*100); ?>%;"></span></div></div>
                <div class="bar-row bar-deficient"><span>Deficient (<?php echo $deficient; ?>)</span><div class="bar"><span style="width:<?php echo round($deficient/$t*100); ?>%;"></span></div></div>
            <?php else: ?>
                <div class="no-data"><i class="fas fa-flask"></i><p>No soil test data available.</p></div>
            <?php endif; ?>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:22px;">
            <div class="farmers-table">
                <div class="table-header"><h3><i class="fas fa-tractor"></i> Farms by Farmer</h3></div>
                <?php if (count($farms_by_farmer) > 0): ?>
                <div class="table-responsive">
                    <table class="rp-table">
                        <thead><tr><th>Farmer</th><th>Farms</th><th>Land (acres)</th></tr></thead>
                        <tbody>
                            <?php foreach ($farms_by_farmer as $r): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($r['farmer_name']); ?></strong></td>
                                <td><?php echo $r['farm_count']; ?></td>
                                <td><?php echo number_format((float)$r['land'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="no-data"><p>No farms yet.</p></div>
                <?php endif; ?>
            </div>

            <div class="farmers-table">
                <div class="table-header"><h3><i class="fas fa-flask"></i> Soil Tests by Farmer</h3></div>
                <?php if (count($tests_per_farmer) > 0): ?>
                <div class="table-responsive">
                    <table class="rp-table">
                        <thead><tr><th>Farmer</th><th>Soil Tests</th></tr></thead>
                        <tbody>
                            <?php foreach ($tests_per_farmer as $r): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($r['farmer_name']); ?></strong></td>
                                <td><?php echo $r['tests']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="no-data"><p>No soil tests yet.</p></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent soil tests -->
        <div class="farmers-table" style="margin-top:22px;">
            <div class="table-header"><h3><i class="fas fa-clock"></i> Recent Soil Tests</h3></div>
            <?php if (count($recent_soil) > 0): ?>
                <div class="table-responsive">
                    <table class="rp-table">
                        <thead><tr><th>Date</th><th>Farmer</th><th>Farm</th><th>N</th><th>P</th><th>K</th><th>pH</th></tr></thead>
                        <tbody>
                            <?php foreach ($recent_soil as $sd): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($sd['test_date'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($sd['farmer_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($sd['farm_name']); ?></td>
                                <td><?php echo $sd['nitrogen']; ?></td>
                                <td><?php echo $sd['phosphorus']; ?></td>
                                <td><?php echo $sd['potassium']; ?></td>
                                <td><?php echo $sd['ph']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data"><i class="fas fa-flask"></i><p>No soil tests recorded yet.</p></div>
            <?php endif; ?>
        </div>

        <!-- Location distribution -->
        <div class="farmers-table" style="margin-top:22px;">
            <div class="table-header"><h3><i class="fas fa-map-marker-alt"></i> Farmers by Location</h3></div>
            <?php if (count($loc_dist) > 0): ?>
                <div class="table-responsive">
                    <table class="rp-table">
                        <thead><tr><th>Location</th><th>Farmers</th></tr></thead>
                        <tbody>
                            <?php foreach ($loc_dist as $r): ?>
                            <tr><td><strong><?php echo htmlspecialchars($r['location']); ?></strong></td><td><?php echo $r['c']; ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data"><p>No farmers yet.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
