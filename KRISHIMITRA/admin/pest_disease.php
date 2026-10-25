<?php
require_once '../config/database.php';

// Only logged-in admins
if (!isLoggedIn('admin')) {
    redirect('admin_login.php');
}

// =========================================================
// PEST & DISEASE KNOWLEDGE BASE (shared with farmer portal)
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
    <title>Pest &amp; Disease Data - KrishiMitra Admin</title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="stylesheet" href="manage_farmers.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .pd-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:18px; }
        .pd-card { background:#fff; border:1px solid #e4ebe6; border-radius:14px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.04); border-left:4px solid #27ae60; }
        .pd-card h4 { color:#1e8449; font-size:16px; margin-bottom:4px; }
        .pd-card .cause { color:#8a6c32; font-size:11px; margin-bottom:10px; }
        .pd-card .tag { display:inline-block; background:#eef7f1; color:#1e8449; padding:3px 9px; border-radius:12px; font-size:11px; font-weight:600; margin:2px 3px 2px 0; }
        .pd-card p { color:#65736a; font-size:12px; line-height:1.7; margin-top:8px; }
        .pd-card p i { color:#27ae60; }
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
        <a class="active" href="pest_disease.php"><i class="fas fa-bug"></i> Pest &amp; Disease Data</a>
        <a href="alerts_notifications.php"><i class="fas fa-bell"></i> Alerts &amp; Notifications</a>
        <a href="reports_analytics.php"><i class="fas fa-file-alt"></i> Reports &amp; Analytics</a>
        <a href="system_logs.php"><i class="fas fa-database"></i> System Logs</a>
    </div>

    <div class="content">
        <div class="welcome-box">
            <h2><i class="fas fa-bug"></i> Pest &amp; Disease Data</h2>
            <p>Knowledge base used by the farmer symptom checker (<?php echo count($diseases); ?> entries, <?php echo count($all_crops); ?> crops)</p>
        </div>

        <div class="stats-grid">
            <div class="stat-box">
                <i class="fas fa-bug" style="color:#27ae60;"></i>
                <div class="stat-info"><h3>Tracked Problems</h3><p><?php echo count($diseases); ?></p></div>
            </div>
            <div class="stat-box">
                <i class="fas fa-seedling" style="color:#e67e22;"></i>
                <div class="stat-info"><h3>Covered Crops</h3><p><?php echo count($all_crops); ?></p></div>
            </div>
        </div>

        <div class="pd-cards">
            <?php foreach ($diseases as $d): ?>
                <div class="pd-card">
                    <h4><?php echo htmlspecialchars($d['name']); ?></h4>
                    <div class="cause"><i class="fas fa-biohazard"></i> <?php echo htmlspecialchars($d['cause']); ?></div>
                    <div>
                        <?php foreach ($d['crops'] as $c): ?>
                            <span class="tag"><?php echo htmlspecialchars($c); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <p><i class="fas fa-tools"></i> <strong>Management:</strong> <?php echo htmlspecialchars($d['management']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

</body>
</html>
