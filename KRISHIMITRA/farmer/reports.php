<?php
require_once '../config/database.php';

// Check if farmer is logged in
if (!isLoggedIn('farmer')) {
    redirect('farmer_login.php');
}

$farmer_id = $_SESSION['farmer_id'];

// Fetch farmer details
$stmt = $pdo->prepare("SELECT * FROM farmers WHERE id = ?");
$stmt->execute([$farmer_id]);
$farmer = $stmt->fetch();

// Fetch farms
$stmt = $pdo->prepare("SELECT * FROM farms WHERE farmer_id = ? ORDER BY created_at DESC");
$stmt->execute([$farmer_id]);
$farms = $stmt->fetchAll();

// Fetch all soil data
$stmt = $pdo->prepare("
    SELECT sd.*, f.farm_name
    FROM soil_data sd
    LEFT JOIN farms f ON sd.farm_id = f.id
    WHERE sd.farmer_id = ?
    ORDER BY sd.test_date DESC
");
$stmt->execute([$farmer_id]);
$soil_records = $stmt->fetchAll();

// Compute summary statistics
$total_farms = count($farms);
$total_soil_tests = count($soil_records);
$total_land = 0;
foreach ($farms as $f) { $total_land += (float)$f['farm_size']; }

// Soil health tally
$healthy = $warning = $deficient = 0;
foreach ($soil_records as $sd) {
    $health = getSoilHealthStatus($sd['nitrogen'], $sd['phosphorus'], $sd['potassium']);
    if ($health['status'] === 'healthy') $healthy++;
    elseif ($health['status'] === 'warning') $warning++;
    else $deficient++;
}

// Latest soil test overall (for the recommendation note)
$latest_soil = $soil_records[0] ?? null;
$recommended_note = '';
if ($latest_soil) {
    $stmt = $pdo->query("SELECT * FROM crops ORDER BY crop_name");
    $crops = $stmt->fetchAll();
    $best_crop = null;
    $best_score = -1;
    foreach ($crops as $crop) {
        $score = 0; $maxScore = 0;
        $checks = [
            ['v'=>(float)$latest_soil['nitrogen'],  'mn'=>$crop['min_nitrogen'],  'mx'=>$crop['max_nitrogen'],  'w'=>25],
            ['v'=>(float)$latest_soil['phosphorus'],'mn'=>$crop['min_phosphorus'],'mx'=>$crop['max_phosphorus'],'w'=>25],
            ['v'=>(float)$latest_soil['potassium'], 'mn'=>$crop['min_potassium'], 'mx'=>$crop['max_potassium'], 'w'=>25],
            ['v'=>(float)$latest_soil['ph'],        'mn'=>$crop['min_ph'],       'mx'=>$crop['max_ph'],       'w'=>15],
            ['v'=>(float)$latest_soil['temperature'],'mn'=>$crop['min_temperature'],'mx'=>$crop['max_temperature'],'w'=>15],
            ['v'=>(float)$latest_soil['rainfall'],  'mn'=>$crop['min_rainfall'],  'mx'=>$crop['max_rainfall'],  'w'=>10],
            ['v'=>(float)$latest_soil['humidity'],  'mn'=>$crop['min_humidity'],  'mx'=>$crop['max_humidity'],  'w'=>10],
        ];
        foreach ($checks as $ck) {
            $maxScore += $ck['w'];
            if ($ck['v'] >= $ck['mn'] && $ck['v'] <= $ck['mx']) { $score += $ck['w']; }
            else {
                $range = max(1, ($ck['mx'] - $ck['mn']));
                $dist = $ck['v'] < $ck['mn'] ? ($ck['mn'] - $ck['v']) : ($ck['v'] - $ck['mx']);
                $score += $ck['w'] * max(0, 1 - ($dist / $range));
            }
        }
        $pct = $maxScore > 0 ? ($score / $maxScore * 100) : 0;
        if ($pct > $best_score) { $best_score = $pct; $best_crop = $crop['crop_name']; }
    }
    if ($best_crop) {
        $recommended_note = $best_crop;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - KrishiMitra</title>
    <link rel="stylesheet" href="farmer_common.css">
    <link rel="stylesheet" href="farmer_dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .report-section { background:#fff; border:1px solid #e4ebe6; border-radius:14px; padding:24px; margin-bottom:22px; box-shadow:0 3px 15px rgba(29,73,42,0.04); }
        .report-section h3 { color:#28543a; font-size:17px; margin-bottom:16px; }
        .report-section h3 i { color:#27ae60; margin-right:7px; }

        .rp-stat { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; }
        .rp-box { border:1px solid #e6ece8; border-radius:11px; padding:16px; text-align:center; }
        .rp-box .lbl { color:#8a958e; font-size:9px; font-weight:600; text-transform:uppercase; }
        .rp-box .val { color:#28543a; font-size:22px; font-weight:600; margin-top:5px; }

        .bar-row { display:flex; align-items:center; gap:12px; margin-bottom:12px; font-size:10px; color:#526158; }
        .bar-row .bar { flex:1; height:10px; background:#edf2ee; border-radius:6px; overflow:hidden; }
        .bar-row .bar span { display:block; height:100%; border-radius:6px; }
        .bar-healthy .bar span { background:#27ae60; }
        .bar-warning .bar span { background:#f1a62e; }
        .bar-deficient .bar span { background:#e05d4a; }

        .rp-table { width:100%; border-collapse:collapse; }
        .rp-table th { padding:11px 12px; background:#f6f9f7; color:#647168; text-align:left; font-size:9px; font-weight:600; border-bottom:1px solid #e5ebe7; }
        .rp-table td { padding:12px; color:#6b776f; font-size:10px; border-bottom:1px solid #edf1ee; }

        .print-btn { display:inline-flex; align-items:center; gap:7px; padding:10px 16px; border:none; border-radius:8px; background:#23934e; color:#fff; font-family:'Poppins',sans-serif; font-size:11px; font-weight:600; cursor:pointer; }
        .print-btn:hover { background:#197d41; }
        @media print { .navbar,.sidebar,.no-print{display:none!important;} .content{margin:0!important;width:100%!important;padding:0!important;} .layout{padding-top:0!important;} }
        @media (max-width:800px){ .rp-stat{grid-template-columns:1fr 1fr;} }
        @media (max-width:520px){ .rp-stat{grid-template-columns:1fr;} }
    </style>
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <div class="brand">
        <img src="../pictures/images/logo1.png" alt="KrishiMitra Logo">
        <h2>KrishiMitra</h2>
    </div>
    <div class="nav-actions">
        <span class="farmer-user"><i class="fas fa-user"></i><span><?php echo htmlspecialchars($farmer['name']); ?></span></span>
        <a href="farmer_dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a>
        <a href="../logout.php" class="logout-link"><i class="fas fa-right-from-bracket"></i><span>Logout</span></a>
    </div>
</div>

<div class="layout">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-heading"><span>FARMER PORTAL</span></div>
        <nav>
            <a href="farmer_dashboard.php"><i class="fas fa-house"></i><span>Dashboard</span></a>
            <a href="manage_farms.php"><i class="fas fa-tractor"></i><span>My Farms</span></a>
            <a href="soil_data.php"><i class="fas fa-seedling"></i><span>Soil Data</span></a>
            <a href="crop_recommendation.php"><i class="fas fa-leaf"></i><span>Crop Advisory</span></a>
            <a href="weather.php"><i class="fas fa-cloud-sun"></i><span>Weather</span></a>
            <a href="market_prices.php"><i class="fas fa-indian-rupee-sign"></i><span>Market Prices</span></a>
            <a href="disease_detection.php"><i class="fas fa-bug"></i><span>Pest Detection</span></a>
            <a class="active" href="reports.php"><i class="fas fa-file-lines"></i><span>Reports</span></a>
        </nav>
    </aside>

    <!-- CONTENT -->
    <div class="content">
        <div class="page-header">
            <div>
                <h1><i class="fas fa-file-lines"></i> Farm Reports</h1>
                <p>Summary of your farms, soil health and advisory</p>
            </div>
        </div>

        <!-- OVERVIEW -->
        <div class="report-section">
            <h3><i class="fas fa-chart-pie"></i> Portfolio Overview</h3>
            <div class="rp-stat">
                <div class="rp-box"><div class="lbl">Farms</div><div class="val"><?php echo $total_farms; ?></div></div>
                <div class="rp-box"><div class="lbl">Total Land</div><div class="val"><?php echo number_format($total_land, 2); ?> ac</div></div>
                <div class="rp-box"><div class="lbl">Soil Tests</div><div class="val"><?php echo $total_soil_tests; ?></div></div>
                <div class="rp-box"><div class="lbl">Location</div><div class="val" style="font-size:16px;"><?php echo htmlspecialchars($farmer['location']); ?></div></div>
            </div>
        </div>

        <!-- SOIL HEALTH -->
        <div class="report-section">
            <h3><i class="fas fa-seedling"></i> Soil Health Summary</h3>
            <?php if ($total_soil_tests > 0): ?>
                <?php $total = max(1, $healthy + $warning + $deficient); ?>
                <div class="bar-row bar-healthy"><span>Good (<?php echo $healthy; ?>)</span><div class="bar"><span style="width:<?php echo round($healthy/$total*100); ?>%;"></span></div></div>
                <div class="bar-row bar-warning"><span>Needs Attention (<?php echo $warning; ?>)</span><div class="bar"><span style="width:<?php echo round($warning/$total*100); ?>%;"></span></div></div>
                <div class="bar-row bar-deficient"><span>Deficient (<?php echo $deficient; ?>)</span><div class="bar"><span style="width:<?php echo round($deficient/$total*100); ?>%;"></span></div></div>
            <?php else: ?>
                <div class="no-data"><i class="fas fa-flask"></i><p>No soil test data available yet.</p></div>
            <?php endif; ?>
        </div>

        <!-- FARMS -->
        <div class="report-section">
            <h3><i class="fas fa-tractor"></i> Farms &amp; Land</h3>
            <?php if ($total_farms > 0): ?>
                <div class="table-responsive">
                    <table class="rp-table">
                        <thead><tr><th>Farm</th><th>Location</th><th>Size (acres)</th><th>Soil Type</th></tr></thead>
                        <tbody>
                            <?php foreach ($farms as $f): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($f['farm_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($f['location']); ?></td>
                                <td><?php echo $f['farm_size']; ?></td>
                                <td><?php echo htmlspecialchars($f['soil_type'] ?? 'Not set'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data"><i class="fas fa-tractor"></i><p>No farms added yet.</p></div>
            <?php endif; ?>
        </div>

        <!-- LATEST SOIL TESTS -->
        <div class="report-section">
            <h3><i class="fas fa-flask"></i> Recent Soil Tests</h3>
            <?php if ($total_soil_tests > 0): ?>
                <div class="table-responsive">
                    <table class="rp-table">
                        <thead><tr><th>Farm</th><th>Date</th><th>N</th><th>P</th><th>K</th><th>pH</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($soil_records, 0, 10) as $sd):
                                $health = getSoilHealthStatus($sd['nitrogen'], $sd['phosphorus'], $sd['potassium']);
                                $cls = $health['status']; $lbl = $health['label'];
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sd['farm_name']); ?></td>
                                <td><?php echo date('d M Y', strtotime($sd['test_date'])); ?></td>
                                <td><?php echo $sd['nitrogen']; ?></td>
                                <td><?php echo $sd['phosphorus']; ?></td>
                                <td><?php echo $sd['potassium']; ?></td>
                                <td><?php echo $sd['ph']; ?></td>
                                <td><span class="status-badge <?php echo $cls; ?>"><?php echo $lbl; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data"><i class="fas fa-flask"></i><p>No soil test data available yet.</p></div>
            <?php endif; ?>
        </div>

        <!-- RECOMMENDATION -->
        <div class="report-section">
            <h3><i class="fas fa-leaf"></i> Current Recommended Crop</h3>
            <?php if ($recommended_note): ?>
                <p style="font-size:13px;color:#28543a;">
                    <i class="fas fa-seedling" style="color:#27ae60;"></i>
                    Based on your latest soil test, <strong><?php echo htmlspecialchars($recommended_note); ?></strong>
                    is the most suitable crop to consider.
                </p>
                <a href="crop_recommendation.php" class="btn-primary no-print" style="margin-top:14px;">
                    <i class="fas fa-leaf"></i> View Full Advisory
                </a>
            <?php else: ?>
                <div class="no-data"><i class="fas fa-leaf"></i><p>Add soil data to get a crop recommendation.</p></div>
            <?php endif; ?>
        </div>

    </div>
</div>

</body>
</html>
