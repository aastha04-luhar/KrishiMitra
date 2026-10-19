<?php

require_once '../config/database.php';

// Check if farmer is logged in
if (!isLoggedIn('farmer')) {
    redirect('farmer_login.php');
}

$farmer_id = $_SESSION['farmer_id'];

// Accept either a specific soil record or a farm
$soil_id = isset($_GET['soil_id']) ? (int)$_GET['soil_id'] : 0;
$farm_id = isset($_GET['farm_id']) ? (int)$_GET['farm_id'] : 0;

// =========================================================
// LOAD THE SOIL RECORD TO RECOMMEND FOR
// =========================================================
$soil = null;

if ($soil_id > 0) {
    // Fetch a specific soil test
    $stmt = $pdo->prepare("
        SELECT sd.*, f.farm_name, f.location, f.soil_type
        FROM soil_data sd
        LEFT JOIN farms f ON sd.farm_id = f.id
        WHERE sd.id = ? AND sd.farmer_id = ?
        LIMIT 1
    ");
    $stmt->execute([$soil_id, $farmer_id]);
    $soil = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($farm_id > 0) {
    // Fetch the latest soil test for this farm
    $stmt = $pdo->prepare("
        SELECT sd.*, f.farm_name, f.location, f.soil_type
        FROM soil_data sd
        LEFT JOIN farms f ON sd.farm_id = f.id
        WHERE sd.farm_id = ? AND sd.farmer_id = ?
        ORDER BY sd.test_date DESC, sd.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$farm_id, $farmer_id]);
    $soil = $stmt->fetch(PDO::FETCH_ASSOC);
}

// If no soil record, list the farmer's farms so they can pick one
$farms = [];
if (!$soil) {
    $stmt = $pdo->prepare("SELECT * FROM farms WHERE farmer_id = ? ORDER BY farm_name");
    $stmt->execute([$farmer_id]);
    $farms = $stmt->fetchAll();
}

// =========================================================
// RECOMMENDATION ENGINE (rule-based compatibility scoring)
// =========================================================
$recommendations = [];

if ($soil) {
    $n = (float)$soil['nitrogen'];
    $p = (float)$soil['phosphorus'];
    $k = (float)$soil['potassium'];
    $ph = (float)$soil['ph'];
    $temp = (float)$soil['temperature'];
    $hum = (float)$soil['humidity'];
    $rain = (float)$soil['rainfall'];

    $stmt = $pdo->query("SELECT * FROM crops ORDER BY crop_name");
    $crops = $stmt->fetchAll();
    $measured = ['N' => $n, 'P' => $p, 'K' => $k];

    foreach ($crops as $crop) {
        $score = 0;
        $maxScore = 0;
        $notes = [];

        // Helper closure: how much a value fits inside [min,max]
        $fitScore = function ($value, $min, $max, $weight) use (&$score, &$maxScore, &$notes, $crop) {
            $maxScore += $weight;
            if ($value >= $min && $value <= $max) {
                $score += $weight;
            } else {
                $distance = $value < $min ? ($min - $value) : ($value - $max);
                $range = max(1, ($max - $min));
                $partial = max(0, 1 - ($distance / $range));
                $score += $weight * $partial;
            }
        };

        // Nutrient fit (N, P, K)
        $fitScore($n, (float)$crop['min_nitrogen'], (float)$crop['max_nitrogen'], 25);
        $fitScore($p, (float)$crop['min_phosphorus'], (float)$crop['max_phosphorus'], 25);
        $fitScore($k, (float)$crop['min_potassium'], (float)$crop['max_potassium'], 25);

        // Soil pH fit
        $fitScore($ph, (float)$crop['min_ph'], (float)$crop['max_ph'], 15);

        // Temperature fit
        $fitScore($temp, (float)$crop['min_temperature'], (float)$crop['max_temperature'], 15);

        // Rainfall fit
        $fitScore($rain, (float)$crop['min_rainfall'], (float)$crop['max_rainfall'], 10);

        // Humidity fit
        $fitScore($hum, (float)$crop['min_humidity'], (float)$crop['max_humidity'], 10);

        $percentage = $maxScore > 0 ? round(($score / $maxScore) * 100) : 0;

        // Identify which nutrient is deficient for advisory text
        $deficiencies = [];
        $nutrientMap = ['N' => 'nitrogen', 'P' => 'phosphorus', 'K' => 'potassium'];
        foreach (['N', 'P', 'K'] as $npk) {
            $minKey = 'min_' . $nutrientMap[$npk];
            if ($measured[$npk] < (float)$crop[$minKey]) {
                $deficiencies[] = $npk;
            }
        }

        $recommendations[] = [
            'crop' => $crop,
            'score' => $percentage,
            'deficiencies' => $deficiencies,
        ];
    }

    // Sort by score descending (and name ascending as tiebreaker)
    usort($recommendations, function ($a, $b) {
        if ($b['score'] === $a['score']) {
            return strcmp($a['crop']['crop_name'], $b['crop']['crop_name']);
        }
        return $b['score'] <=> $a['score'];
    });

    // Overall soil health assessment
    $soil_health = getSoilHealthStatus($n, $p, $k);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Crop Recommendation - KrishiMitra</title>

    <link rel="stylesheet" href="farmer_common.css">

    <link rel="stylesheet" href="farmer_dashboard.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .rec-result {
            background: #ffffff;
            border: 1px solid #e4ebe6;
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 22px;
            box-shadow: 0 3px 15px rgba(29,73,42,0.04);
        }

        .rec-result h3 {
            color: #28543a;
            font-size: 16px;
            margin-bottom: 14px;
        }

        .health-note {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 12px 15px;
            border-radius: 9px;
            font-size: 11px;
            margin-bottom: 16px;
        }
        .health-note.healthy { background:#e7f7ec; color:#258049; }
        .health-note.warning { background:#fff4df; color:#b57922; }
        .health-note.danger  { background:#fff0ed; color:#c34d3a; }

        .top-pick {
            border: 2px solid #27ae60;
            background: linear-gradient(120deg, #f0fbf3, #ffffff);
        }

        .rank-list {
            display: grid;
            gap: 14px;
        }

        .rank-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 18px;
            border: 1px solid #e6ece8;
            border-radius: 11px;
            background: #ffffff;
        }

        .rank-num {
            width: 34px; height: 34px;
            flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            border-radius: 9px;
            background: #eaf7ee; color: #24904c;
            font-weight: 600; font-size: 14px;
        }
        .rank-item.top .rank-num { background:#27ae60; color:#fff; }

        .rank-info { flex: 1; min-width: 0; }
        .rank-info h4 { color:#28543a; font-size:15px; margin-bottom:3px; }
        .rank-info p  { color:#7a867f; font-size:10px; }

        .rank-score { text-align: right; flex-shrink: 0; }
        .rank-score .num { font-size:22px; font-weight:700; color:#24904c; }
        .rank-score .bar { width:130px; height:8px; background:#edf2ee; border-radius:6px; overflow:hidden; margin-top:5px; }
        .rank-score .bar span { display:block; height:100%; background:#27ae60; border-radius:6px; }

        .badge-deficit {
            display:inline-block;
            padding:3px 8px;
            border-radius:12px;
            background:#fff4df; color:#b57922;
            font-size:8px; font-weight:600;
            margin-top:5px;
        }

        .pick-form {
            background:#ffffff; border:1px solid #e4ebe6; border-radius:14px;
            padding:24px; margin-bottom:22px; box-shadow:0 3px 15px rgba(29,73,42,0.04);
        }
        .pick-form h3 { color:#28543a; font-size:16px; margin-bottom:14px; }
        .pick-form select,
        .pick-form button { width:100%; padding:11px 13px; border-radius:8px; border:1px solid #dce5df; margin-bottom:10px; font-family:'Poppins',sans-serif; font-size:11px; }
        .pick-form button { background:#23934e; color:#fff; border:none; font-weight:600; cursor:pointer; }

        @media (max-width:600px){
            .rank-item{flex-wrap:wrap; gap:10px;}
            .rank-score{text-align:left;width:100%;}
        }
    </style>

</head>

<body>

<!-- NAVBAR -->

<div class="navbar">

    <div class="brand">

        <img src="../pictures/images/logo1.png"
             alt="KrishiMitra Logo">

        <h2>KrishiMitra</h2>

    </div>

    <div class="nav-actions">

        <span class="farmer-user">
            <i class="fas fa-user"></i>
            <span><?php echo htmlspecialchars($_SESSION['farmer_name']); ?></span>
        </span>

        <a href="farmer_dashboard.php">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>

        <a href="../logout.php" class="logout-link">
            <i class="fas fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>

    </div>

</div>


<!-- MAIN LAYOUT -->

<div class="layout">

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="sidebar-heading">
            <span>FARMER PORTAL</span>
        </div>

        <nav>

            <a href="farmer_dashboard.php">
                <i class="fas fa-house"></i>
                <span>Dashboard</span>
            </a>

            <a href="manage_farms.php">
                <i class="fas fa-tractor"></i>
                <span>My Farms</span>
            </a>

            <a href="soil_data.php">
                <i class="fas fa-seedling"></i>
                <span>Soil Data</span>
            </a>

            <a class="active" href="crop_recommendation.php">
                <i class="fas fa-leaf"></i>
                <span>Crop Advisory</span>
            </a>

            <a href="weather.php">
                <i class="fas fa-cloud-sun"></i>
                <span>Weather</span>
            </a>

            <a href="market_prices.php">
                <i class="fas fa-indian-rupee-sign"></i>
                <span>Market Prices</span>
            </a>

            <a href="disease_detection.php">
                <i class="fas fa-bug"></i>
                <span>Pest Detection</span>
            </a>

            <a href="reports.php">
                <i class="fas fa-file-lines"></i>
                <span>Reports</span>
            </a>

        </nav>

    </aside>

    <!-- CONTENT -->

    <div class="content">

        <div class="page-header">

            <div>
                <h1><i class="fas fa-leaf"></i> Crop Advisory</h1>
                <p>Rule-based crop recommendation for your farm</p>
            </div>

        </div>

        <?php if (!$soil): ?>

            <!-- NO SOIL DATA -> ASK FARMER TO PICK A FARM/SOIL -->
            <div class="pick-form">

                <?php if (count($farms) > 0): ?>

                    <h3><i class="fas fa-tractor"></i> Select a Farm</h3>
                    <p style="font-size:11px;color:#7a867f;margin-bottom:14px;">
                        Choose a farm to see a crop recommendation based on its latest soil test.
                    </p>
                    <form method="GET" action="soil_data.php">
                        <select name="farm_id" onchange="this.form.submit()">
                            <option value="">-- Select Farm --</option>
                            <?php foreach ($farms as $f): ?>
                                <option value="<?php echo $f['id']; ?>">
                                    <?php echo htmlspecialchars($f['farm_name']); ?> (<?php echo htmlspecialchars($f['location']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                <?php else: ?>

                    <h3><i class="fas fa-info-circle"></i> No Data Available</h3>
                    <p style="font-size:11px;color:#7a867f;">
                        You need to add a farm and at least one soil test before we can recommend crops.
                    </p>
                    <a href="manage_farms.php" class="btn-primary" style="margin-top:10px;">
                        <i class="fas fa-plus"></i> Add a Farm
                    </a>

                <?php endif; ?>

            </div>

        <?php else: ?>

            <!-- FARM INFORMATION -->

            <div class="rec-result">

                <h3><i class="fas fa-tractor"></i> Farm Information</h3>

                <p style="margin-bottom:6px;"><strong>Farm:</strong>
                    <?php echo htmlspecialchars($soil['farm_name']); ?></p>

                <p style="margin-bottom:6px;"><strong>Location:</strong>
                    <?php echo htmlspecialchars($soil['location']); ?></p>

                <p style="margin-bottom:6px;"><strong>Soil Type:</strong>
                    <?php echo htmlspecialchars($soil['soil_type'] ?? 'Not specified'); ?></p>

                <p><strong>Soil Test Date:</strong>
                    <?php echo date('d M Y', strtotime($soil['test_date'])); ?></p>

            </div>


            <!-- SOIL CONDITIONS -->

            <div class="rec-result">

                <h3><i class="fas fa-flask"></i> Soil &amp; Environmental Conditions</h3>

                <div class="kpi-container" style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">

                    <div class="kpi-card" style="border:1px solid #e6ece8;border-radius:11px;padding:16px;text-align:center;">
                        <h4 style="font-size:10px;color:#8a958e;">Nitrogen (N)</h4>
                        <p style="font-size:22px;font-weight:600;color:#28543a;"><?php echo $soil['nitrogen']; ?> ppm</p>
                    </div>

                    <div class="kpi-card" style="border:1px solid #e6ece8;border-radius:11px;padding:16px;text-align:center;">
                        <h4 style="font-size:10px;color:#8a958e;">Phosphorus (P)</h4>
                        <p style="font-size:22px;font-weight:600;color:#28543a;"><?php echo $soil['phosphorus']; ?> ppm</p>
                    </div>

                    <div class="kpi-card" style="border:1px solid #e6ece8;border-radius:11px;padding:16px;text-align:center;">
                        <h4 style="font-size:10px;color:#8a958e;">Potassium (K)</h4>
                        <p style="font-size:22px;font-weight:600;color:#28543a;"><?php echo $soil['potassium']; ?> ppm</p>
                    </div>

                    <div class="kpi-card" style="border:1px solid #e6ece8;border-radius:11px;padding:16px;text-align:center;">
                        <h4 style="font-size:10px;color:#8a958e;">Soil pH</h4>
                        <p style="font-size:22px;font-weight:600;color:#28543a;"><?php echo $soil['ph']; ?></p>
                    </div>

                    <div class="kpi-card" style="border:1px solid #e6ece8;border-radius:11px;padding:16px;text-align:center;">
                        <h4 style="font-size:10px;color:#8a958e;">Temperature</h4>
                        <p style="font-size:22px;font-weight:600;color:#28543a;"><?php echo $soil['temperature']; ?> °C</p>
                    </div>

                    <div class="kpi-card" style="border:1px solid #e6ece8;border-radius:11px;padding:16px;text-align:center;">
                        <h4 style="font-size:10px;color:#8a958e;">Humidity / Rainfall</h4>
                        <p style="font-size:16px;font-weight:600;color:#28543a;"><?php echo $soil['humidity']; ?>% / <?php echo $soil['rainfall']; ?> mm</p>
                    </div>

                </div>

                <div class="health-note <?php echo $soil_health['status']; ?>" style="margin-top:18px;">
                    <i class="fas fa-<?php echo $soil_health['status'] === 'danger' ? 'exclamation-triangle' : ($soil_health['status'] === 'warning' ? 'exclamation-circle' : 'check-circle'); ?>"></i>
                    Soil Health: <strong><?php echo $soil_health['label']; ?></strong>
                </div>

            </div>


            <!-- RECOMMENDATION RESULTS -->

            <div class="rec-result top-pick">

                <?php if (!empty($recommendations)): ?>

                    <h3><i class="fas fa-trophy"></i> Recommended Crop</h3>

                    <?php $best = $recommendations[0]; ?>

                    <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;">
                        <div style="width:70px;height:70px;flex-shrink:0;display:flex;align-items:center;justify-content:center;border-radius:14px;background:#27ae60;color:#fff;font-size:30px;">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <div style="flex:1;min-width:200px;">
                            <h2 style="color:#174d2e;font-size:26px;margin-bottom:4px;">
                                <?php echo htmlspecialchars($best['crop']['crop_name']); ?>
                            </h2>
                            <p style="color:#7a867f;font-size:11px;">
                                <strong><?php echo $best['score']; ?>%</strong> compatibility with your soil and
                                environmental conditions
                            </p>
                        </div>
                    </div>

                    <?php if (!empty($best['deficiencies'])): ?>
                        <div style="margin-top:14px;">
                            <span class="badge-deficit">
                                <i class="fas fa-exclamation-triangle"></i> Low nutrient (consider supplementing):
                                <?php echo implode(', ', $best['deficiencies']); ?>
                            </span>
                        </div>
                    <?php endif; ?>

                <?php foreach ($best['crop'] as $key => $val): ?>
                    <?php if (in_array($key, ['description'])): ?>
                        <?php if (!empty($val)): ?>
                            <p style="margin-top:14px;font-size:11px;color:#65736a;line-height:1.7;">
                                <i class="fas fa-info-circle" style="color:#27ae60;"></i>
                                <?php echo htmlspecialchars($val); ?>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php endif; ?>

            </div>


            <!-- FULL RANKING LIST -->

            <div class="rec-result">

                <h3><i class="fas fa-list-ol"></i> All Crop Compatibility</h3>

                <div class="rank-list">

                    <?php $rank = 1; foreach ($recommendations as $rec): ?>

                        <div class="rank-item <?php echo $rank === 1 ? 'top' : ''; ?>">

                            <div class="rank-num"><?php echo $rank++; ?></div>

                            <div class="rank-info">
                                <h4><?php echo htmlspecialchars($rec['crop']['crop_name']); ?></h4>
                                <p>
                                    <?php echo htmlspecialchars($rec['crop']['soil_type'] ?? 'All soils'); ?> |
                                    Water: <?php echo htmlspecialchars($rec['crop']['water_requirement'] ?? 'Medium'); ?> |
                                    Duration: <?php echo htmlspecialchars($rec['crop']['crop_duration'] ?? 'N/A'); ?>
                                </p>
                                <?php if (!empty($rec['deficiencies'])): ?>
                                    <span class="badge-deficit">
                                        Needs more: <?php echo implode(', ', $rec['deficiencies']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="rank-score">
                                <div class="num"><?php echo $rec['score']; ?>%</div>
                                <div class="bar"><span style="width:<?php echo $rec['score']; ?>%;"></span></div>
                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

            <div style="margin-top:5px;">
                <a href="soil_data.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Soil Data
                </a>
                <a href="reports.php" class="btn-primary" style="margin-left:8px;">
                    <i class="fas fa-file-lines"></i> View Reports
                </a>
            </div>

        <?php endif; ?>

    </div>

</div>

</body>

</html>
