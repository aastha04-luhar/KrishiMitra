<?php
require_once '../config/database.php';

// Check if farmer is logged in
if (!isLoggedIn('farmer')) {
    redirect('farmer_login.php');
}

// =========================================================
// PEST & DISEASE KNOWLEDGE BASE
// Each entry lists symptoms so matching is possible
// =========================================================
$diseases = [
    [
        'name' => 'Rice Blast',
        'crops' => ['Rice'],
        'cause' => 'Fungal (Magnaporthe oryzae)',
        'symptoms' => ['leaf spots', 'brown spots', 'neck rot', 'white lesions'],
        'management' => 'Use resistant varieties, avoid excess nitrogen, apply fungicides (tricyclazole, kitazin) at the first sign of infection.',
    ],
    [
        'name' => 'Late Blight of Potato',
        'crops' => ['Potato', 'Tomato'],
        'cause' => 'Fungal (Phytophthora infestans)',
        'symptoms' => ['leaf spots', 'brown spots', 'wilting', 'dark lesions'],
        'management' => 'Remove infected plants, apply copper-based fungicides, improve drainage and air circulation.',
    ],
    [
        'name' => 'Wheat Rust',
        'crops' => ['Wheat'],
        'cause' => 'Fungal (Puccinia spp.)',
        'symptoms' => ['yellow spots', 'rusty pustules', 'leaf discoloration'],
        'management' => 'Grow resistant varieties, timely sowing, apply triazole fungicides when rust appears.',
    ],
    [
        'name' => 'Cotton Bollworm',
        'crops' => ['Cotton'],
        'cause' => 'Insect (Helicoverpa armigera)',
        'symptoms' => ['holes in bolls', 'chewed leaves', 'frass', 'dropped bolls'],
        'management' => 'Install pheromone traps, manual removal of larvae, apply biological control or recommended insecticides at larval stage.',
    ],
    [
        'name' => 'Sugarcane Top Borer',
        'crops' => ['Sugarcane'],
        'cause' => 'Insect (Scirpophaga excerptalis)',
        'symptoms' => ['dead heart', 'shot holes', 'stunted growth', 'bore holes'],
        'management' => 'Remove and destroy infected shoots, release egg parasitoids, apply recommended insecticides.',
    ],
    [
        'name' => 'Groundnut Tikka Disease',
        'crops' => ['Groundnut'],
        'cause' => 'Fungal (Cercospora spp.)',
        'symptoms' => ['leaf spots', 'brown spots', 'yellow halos', 'premature defoliation'],
        'management' => 'Use disease-free seed, crop rotation, apply mancozeb or chlorothalonil sprays.',
    ],
    [
        'name' => 'Maize Stem Borer',
        'crops' => ['Maize', 'Bajra'],
        'cause' => 'Insect (Chilo partellus)',
        'symptoms' => ['dead heart', 'shot holes', 'bore holes', 'stunted growth'],
        'management' => 'Destroy crop residue after harvest, apply granular insecticides in the whorl, release Trichogramma egg parasitoids.',
    ],
    [
        'name' => 'Mustard Aphid',
        'crops' => ['Mustard'],
        'cause' => 'Insect (Lipaphis erysimi)',
        'symptoms' => ['honeydew', 'sticky leaves', 'curled leaves', 'black sooty mold'],
        'management' => 'Avoid late sowing, apply neem-based products, use recommended insecticides at threshold levels.',
    ],
];

// Handle form submit - symptom matching
$selected_crops = isset($_POST['crop']) ? (array)$_POST['crop'] : [];
$selected_symptoms = isset($_POST['symptoms']) ? (array)$_POST['symptoms'] : [];
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($selected_symptoms)) {
    foreach ($diseases as $disease) {
        $matched = 0;
        foreach ($selected_symptoms as $sym) {
            if (in_array($sym, $disease['symptoms'])) {
                $matched++;
            }
        }
        // Only consider diseases matching selected crops (if any selected)
        $cropMatch = empty($selected_crops) ||
                     count(array_intersect($selected_crops, $disease['crops'])) > 0;
        if ($cropMatch && $matched > 0) {
            $confidence = min(100, round(($matched / count($disease['symptoms'])) * 100));
            $results[] = ['disease' => $disease, 'matched' => $matched, 'confidence' => $confidence];
        }
    }
    usort($results, function ($a, $b) { return $b['matched'] <=> $a['matched']; });
}

// All symptoms available in the knowledge base (for the checkboxes)
$all_symptoms = [];
foreach ($diseases as $d) {
    foreach ($d['symptoms'] as $s) {
        if (!in_array($s, $all_symptoms)) $all_symptoms[] = $s;
    }
}
$all_crops = [];
foreach ($diseases as $d) {
    foreach ($d['crops'] as $c) {
        if (!in_array($c, $all_crops)) $all_crops[] = $c;
    }
}
sort($all_crops);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pest Detection - KrishiMitra</title>
    <link rel="stylesheet" href="farmer_common.css">
    <link rel="stylesheet" href="farmer_dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .pest-layout { display:grid; grid-template-columns:1fr 1fr; gap:22px; align-items:start; }

        .detect-card { background:#fff; border:1px solid #e4ebe6; border-radius:14px; padding:24px; box-shadow:0 3px 15px rgba(29,73,42,0.04); }
        .detect-card h3 { color:#28543a; font-size:17px; margin-bottom:14px; }
        .detect-card h3 i { color:#27ae60; margin-right:6px; }

        .check-group { margin-bottom:14px; }
        .check-group .group-label { color:#526158; font-size:10px; font-weight:600; margin-bottom:8px; }
        .check-list { display:flex; flex-wrap:wrap; gap:8px; }
        .check-list label {
            display:inline-flex; align-items:center; gap:6px;
            padding:7px 11px; border:1px solid #dce5df; border-radius:20px;
            font-size:9px; color:#59665e; cursor:pointer; background:#fff; transition:.15s;
        }
        .check-list label:hover { border-color:#a8d3b5; background:#f7fcf8; }
        .check-list label input { accent-color:#27ae60; }

        .result-card { background:#fff; border:1px solid #e4ebe6; border-radius:14px; padding:24px; margin-bottom:16px; box-shadow:0 3px 15px rgba(29,73,42,0.04); border-left:4px solid #27ae60; }
        .result-card h4 { color:#28543a; font-size:15px; margin-bottom:3px; }
        .result-card .cause { color:#8a6c32; font-size:10px; margin-bottom:8px; }
        .result-card p { color:#65736a; font-size:10px; line-height:1.7; }
        .conf-tag { display:inline-block; padding:4px 10px; border-radius:20px; background:#e7f7ec; color:#258049; font-size:8px; font-weight:600; margin-bottom:8px; }

        .ref-card { background:#fff; border:1px solid #e4ebe6; border-radius:14px; padding:24px; box-shadow:0 3px 15px rgba(29,73,42,0.04); }
        .ref-card h3 { color:#28543a; font-size:16px; margin-bottom:14px; }
        .ref-item { padding:13px 0; border-bottom:1px solid #edf1ee; }
        .ref-item:last-child { border-bottom:none; }
        .ref-item h4 { color:#28543a; font-size:13px; }
        .ref-item .sub { color:#8a6c32; font-size:9px; margin-bottom:5px; }
        .ref-item p { color:#65736a; font-size:9px; line-height:1.6; }

        .btn-detect { width:100%; padding:11px; border:none; border-radius:8px; background:#23934e; color:#fff; font-family:'Poppins',sans-serif; font-size:11px; font-weight:600; cursor:pointer; }
        .btn-detect:hover { background:#197d41; }

        @media (max-width:1000px){ .pest-layout{grid-template-columns:1fr;} }
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
        <span class="farmer-user"><i class="fas fa-user"></i><span><?php echo htmlspecialchars($_SESSION['farmer_name']); ?></span></span>
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
            <a class="active" href="disease_detection.php"><i class="fas fa-bug"></i><span>Pest Detection</span></a>
            <a href="reports.php"><i class="fas fa-file-lines"></i><span>Reports</span></a>
        </nav>
    </aside>

    <!-- CONTENT -->
    <div class="content">
        <div class="page-header">
            <div>
                <h1><i class="fas fa-bug"></i> Pest &amp; Disease Detection</h1>
                <p>Identify possible crop problems using the symptom guide</p>
            </div>
        </div>

        <div class="pest-layout">

            <!-- DETECTOR -->
            <div>
                <div class="detect-card">
                    <h3><i class="fas fa-stethoscope"></i> Symptom Checker</h3>
                    <form method="POST" action="">
                        <div class="check-group">
                            <div class="group-label">Select affected crops (optional)</div>
                            <div class="check-list">
                                <?php foreach ($all_crops as $crop): ?>
                                    <label>
                                        <input type="checkbox" name="crop[]" value="<?php echo $crop; ?>"
                                               <?php echo in_array($crop, $selected_crops) ? 'checked' : ''; ?>>
                                        <?php echo $crop; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="check-group">
                            <div class="group-label">Select observed symptoms</div>
                            <div class="check-list">
                                <?php foreach ($all_symptoms as $sym): ?>
                                    <label>
                                        <input type="checkbox" name="symptoms[]" value="<?php echo $sym; ?>"
                                               <?php echo in_array($sym, $selected_symptoms) ? 'checked' : ''; ?>>
                                        <?php echo ucwords($sym); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn-detect"><i class="fas fa-search"></i> Detect Possible Problems</button>
                    </form>
                </div>

                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <div style="margin-top:22px;">
                        <h3 style="color:#28543a;font-size:16px;margin-bottom:12px;">
                            <i class="fas fa-clipboard-check"></i> Detection Results
                        </h3>
                        <?php if (count($results) > 0): ?>
                            <?php foreach ($results as $r): ?>
                                <div class="result-card">
                                    <span class="conf-tag"><i class="fas fa-chart-line"></i> <?php echo $r['confidence']; ?>% match</span>
                                    <h4><?php echo htmlspecialchars($r['disease']['name']); ?></h4>
                                    <div class="cause"><i class="fas fa-biohazard"></i> <?php echo htmlspecialchars($r['disease']['cause']); ?></div>
                                    <p><i class="fas fa-tools" style="color:#27ae60;"></i> <strong>Management:</strong> <?php echo htmlspecialchars($r['disease']['management']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-shield-halved"></i>
                                <p>No known pest/disease matched your selection.</p>
                                <p style="font-size:9px;color:#9aa39d;">Try selecting more symptoms or contact your local agriculture officer.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- REFERENCE FULL LIST -->
            <div class="ref-card">
                <h3><i class="fas fa-book-open"></i> Common Pests &amp; Diseases</h3>
                <?php foreach ($diseases as $d): ?>
                    <div class="ref-item">
                        <h4><?php echo htmlspecialchars($d['name']); ?></h4>
                        <div class="sub"><?php echo implode(', ', $d['crops']); ?> | <?php echo htmlspecialchars($d['cause']); ?></div>
                        <p><?php echo htmlspecialchars($d['management']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</div>

</body>
</html>
