<?php
require_once '../config/database.php';

// Only logged-in admins
if (!isLoggedIn('admin')) {
    redirect('admin_login.php');
}

// Coordinates lookup for known districts (reuse farmer mapping + common additions)
function getCoordinates($location) {
    $locations = [
        'Mehsana' => ['lat' => 23.59, 'lon' => 72.37],
        'Ahmedabad' => ['lat' => 23.02, 'lon' => 72.57],
        'Gandhinagar' => ['lat' => 23.22, 'lon' => 72.65],
        'Punjab' => ['lat' => 31.15, 'lon' => 75.34],
        'Rajasthan' => ['lat' => 26.91, 'lon' => 75.79],
        'Gujarat' => ['lat' => 22.30, 'lon' => 70.80],
        'Maharashtra' => ['lat' => 19.75, 'lon' => 75.71],
        'Karnataka' => ['lat' => 15.31, 'lon' => 75.71],
        'Madhya Pradesh' => ['lat' => 23.24, 'lon' => 77.42],
        'Telangana' => ['lat' => 17.98, 'lon' => 79.53],
        'Pune' => ['lat' => 18.52, 'lon' => 73.85],
        'Delhi' => ['lat' => 28.70, 'lon' => 77.10],
    ];
    if (isset($locations[$location])) {
        return $locations[$location];
    }
    return ['lat' => 23.02, 'lon' => 72.57];
}

function getWeatherDescription($code) {
    $descriptions = [
        0 => 'Clear sky', 1 => 'Mainly clear', 2 => 'Partly cloudy', 3 => 'Overcast',
        45 => 'Foggy', 48 => 'Depositing rime fog', 51 => 'Light drizzle', 53 => 'Moderate drizzle',
        55 => 'Dense drizzle', 56 => 'Light freezing drizzle', 57 => 'Dense freezing drizzle',
        61 => 'Slight rain', 63 => 'Moderate rain', 65 => 'Heavy rain', 66 => 'Light freezing rain',
        67 => 'Heavy freezing rain', 71 => 'Slight snow fall', 73 => 'Moderate snow fall',
        75 => 'Heavy snow fall', 77 => 'Snow grains', 80 => 'Slight rain showers',
        81 => 'Moderate rain showers', 82 => 'Violent rain showers', 85 => 'Slight snow showers',
        86 => 'Heavy snow showers', 95 => 'Thunderstorm', 96 => 'Thunderstorm with slight hail',
        99 => 'Thunderstorm with heavy hail',
    ];
    return isset($descriptions[$code]) ? $descriptions[$code] : 'Unknown';
}

function fetchWeather($lat, $lon) {
    $url = "https://api.open-meteo.com/v1/forecast?" . http_build_query([
        'latitude' => $lat,
        'longitude' => $lon,
        'current' => 'temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code',
        'timezone' => 'auto',
        'forecast_days' => 1,
    ]);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http_code !== 200) return null;
    $data = json_decode($response, true);
    return ($data && isset($data['current'])) ? $data : null;
}

// Gather unique farm locations
$rows = $pdo->query("SELECT DISTINCT location FROM farms WHERE location IS NOT NULL AND location != ''")->fetchAll();
$locations = [];
foreach ($rows as $r) {
    if (!in_array($r['location'], $locations)) $locations[] = $r['location'];
}

// Also include farmer districts
$frows = $pdo->query("SELECT DISTINCT district FROM farmers WHERE district IS NOT NULL AND district != ''")->fetchAll();
foreach ($frows as $r) {
    if (!in_array($r['district'], $locations)) $locations[] = $r['district'];
}
if (empty($locations)) {
    $locations = ['Ahmedabad'];
}

// Build weather cards
$weather_cards = [];
foreach ($locations as $loc) {
    $coord = getCoordinates($loc);
    $data = fetchWeather($coord['lat'], $coord['lon']);
    if ($data) {
        $cur = $data['current'];
        $weather_cards[] = [
            'location' => $loc,
            'temp' => $cur['temperature_2m'],
            'humidity' => $cur['relative_humidity_2m'],
            'wind' => $cur['wind_speed_10m'],
            'desc' => getWeatherDescription($cur['weather_code']),
        ];
    } else {
        $weather_cards[] = [
            'location' => $loc,
            'temp' => null,
            'humidity' => null,
            'wind' => null,
            'desc' => 'Unavailable',
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather System - KrishiMitra Admin</title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="stylesheet" href="manage_farmers.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .wx-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:16px; }
        .wx-card { background:#fff; border:1px solid #e4ebe6; border-radius:14px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.04); }
        .wx-card .loc { color:#8a958e; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
        .wx-card .temp { color:#1e8449; font-size:34px; font-weight:600; margin:6px 0 2px; }
        .wx-card .desc { color:#65736a; font-size:12px; margin-bottom:10px; }
        .wx-card .meta { display:flex; gap:14px; font-size:11px; color:#526158; }
        .wx-card .meta i { color:#27ae60; width:16px; }
        .wx-note { font-size:11px; color:#9aa39d; margin-top:18px; }
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
        <a class="active" href="weather_admin.php"><i class="fas fa-cloud-sun"></i> Weather System</a>
        <a href="market_rates.php"><i class="fas fa-rupee-sign"></i> Market Rates</a>
        <a href="pest_disease.php"><i class="fas fa-bug"></i> Pest &amp; Disease Data</a>
        <a href="alerts_notifications.php"><i class="fas fa-bell"></i> Alerts &amp; Notifications</a>
        <a href="reports_analytics.php"><i class="fas fa-file-alt"></i> Reports &amp; Analytics</a>
        <a href="system_logs.php"><i class="fas fa-database"></i> System Logs</a>
    </div>

    <div class="content">
        <div class="welcome-box">
            <h2><i class="fas fa-cloud-sun"></i> Weather System</h2>
            <p>Current weather conditions for recorded farm locations (Open-Meteo)</p>
        </div>

        <div class="wx-grid">
            <?php foreach ($weather_cards as $w): ?>
                <div class="wx-card">
                    <div class="loc"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($w['location']); ?></div>
                    <?php if ($w['temp'] !== null): ?>
                        <div class="temp"><?php echo round((float)$w['temp']); ?>°C</div>
                        <div class="desc"><i class="fas fa-cloud-sun"></i> <?php echo htmlspecialchars($w['desc']); ?></div>
                        <div class="meta">
                            <span><i class="fas fa-droplet"></i> <?php echo (int)$w['humidity']; ?>%</span>
                            <span><i class="fas fa-wind"></i> <?php echo round((float)$w['wind']); ?> km/h</span>
                        </div>
                    <?php else: ?>
                        <div class="temp" style="color:#c34d3a;">--</div>
                        <div class="desc">Weather unavailable</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <p class="wx-note"><i class="fas fa-info-circle"></i> Live data from the Open-Meteo API for each unique farm/farmer location on the platform.</p>
    </div>
</div>

</body>
</html>
