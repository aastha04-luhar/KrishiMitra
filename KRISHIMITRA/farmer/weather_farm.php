<?php
require_once '../config/database.php';

// Check if farmer is logged in
if (!isLoggedIn('farmer')) {
    redirect('farmer_login.php');
}

$farmer_id = $_SESSION['farmer_id'];
$farm_id = isset($_GET['farm_id']) ? (int)$_GET['farm_id'] : 0;

// Get farm details
$stmt = $pdo->prepare("SELECT * FROM farms WHERE id = ? AND farmer_id = ?");
$stmt->execute([$farm_id, $farmer_id]);
$farm = $stmt->fetch();

if (!$farm) {
    // If no farm selected, redirect to farm list
    header("Location: manage_farms.php");
    exit();
}

// Get weather for this specific farm
$weather_data = null;
$error = null;

// Use farm's latitude/longitude if available, otherwise use location
$lat = $farm['latitude'];
$lon = $farm['longitude'];

if (empty($lat) || empty($lon)) {
    // Auto-detect from location name using geocoding
    $location_name = $farm['location'] . ',' . $farm['district'];
    $coords = getCoordinatesFromLocation($location_name);
    if ($coords) {
        $lat = $coords['lat'];
        $lon = $coords['lon'];
        // Update farm with coordinates for future use
        $stmt = $pdo->prepare("UPDATE farms SET latitude = ?, longitude = ? WHERE id = ?");
        $stmt->execute([$lat, $lon, $farm_id]);
    }
}

if ($lat && $lon) {
    $weather_data = fetchWeatherFromAPI($lat, $lon);
} else {
    $error = 'Could not get location coordinates for this farm.';
}

// Helper functions
function getCoordinatesFromLocation($location) {
    // Use Open-Meteo Geocoding API
    $url = "https://geocoding-api.open-meteo.com/v1/search?name=" . urlencode($location) . "&count=1&language=en&format=json";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    if ($data && isset($data['results'][0])) {
        return [
            'lat' => $data['results'][0]['latitude'],
            'lon' => $data['results'][0]['longitude']
        ];
    }
    return null;
}

function fetchWeatherFromAPI($lat, $lon) {
    $url = "https://api.open-meteo.com/v1/forecast?" . http_build_query([
        'latitude' => $lat,
        'longitude' => $lon,
        'current' => 'temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code,apparent_temperature,precipitation',
        'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum',
        'timezone' => 'auto',
        'forecast_days' => 3
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        return null;
    }
    
    $data = json_decode($response, true);
    if (!$data || !isset($data['current'])) {
        return null;
    }
    
    $data['current']['weather_description'] = getWeatherDescription($data['current']['weather_code']);
    return $data;
}

function getWeatherDescription($code) {
    $descriptions = [
        0 => 'Clear sky',
        1 => 'Mainly clear',
        2 => 'Partly cloudy',
        3 => 'Overcast',
        45 => 'Foggy',
        48 => 'Depositing rime fog',
        51 => 'Light drizzle',
        53 => 'Moderate drizzle',
        55 => 'Dense drizzle',
        56 => 'Light freezing drizzle',
        57 => 'Dense freezing drizzle',
        61 => 'Slight rain',
        63 => 'Moderate rain',
        65 => 'Heavy rain',
        66 => 'Light freezing rain',
        67 => 'Heavy freezing rain',
        71 => 'Slight snow fall',
        73 => 'Moderate snow fall',
        75 => 'Heavy snow fall',
        77 => 'Snow grains',
        80 => 'Slight rain showers',
        81 => 'Moderate rain showers',
        82 => 'Violent rain showers',
        85 => 'Slight snow showers',
        86 => 'Heavy snow showers',
        95 => 'Thunderstorm',
        96 => 'Thunderstorm with slight hail',
        99 => 'Thunderstorm with heavy hail'
    ];
    return isset($descriptions[$code]) ? $descriptions[$code] : 'Unknown';
}

function getWeatherIcon($code) {
    if ($code >= 0 && $code <= 3) return 'sun';
    if ($code >= 45 && $code <= 48) return 'smog';
    if ($code >= 51 && $code <= 67) return 'cloud-rain';
    if ($code >= 71 && $code <= 77) return 'snowflake';
    if ($code >= 80 && $code <= 82) return 'cloud-showers-heavy';
    if ($code >= 85 && $code <= 86) return 'snow';
    if ($code >= 95 && $code <= 99) return 'bolt';
    return 'cloud';
}

function getFarmingAdvisory($weather) {
    $temp = $weather['temperature_2m'];
    $humidity = $weather['relative_humidity_2m'];
    $wind = $weather['wind_speed_10m'];
    $precip = isset($weather['precipitation']) ? $weather['precipitation'] : 0;
    $code = $weather['weather_code'];
    
    $advice = [];
    
    // Temperature advice
    if ($temp > 35) {
        $advice[] = '🌡️ <strong>High temperature alert!</strong> Irrigate crops in the early morning or evening to prevent water loss.';
    } elseif ($temp < 10) {
        $advice[] = '❄️ <strong>Cold conditions expected.</strong> Protect sensitive crops with mulching or covering.';
    }
    
    // Humidity advice
    if ($humidity > 80) {
        $advice[] = '💧 <strong>High humidity detected.</strong> Monitor for fungal diseases in crops.';
    } elseif ($humidity < 30) {
        $advice[] = '🌵 <strong>Low humidity.</strong> Ensure adequate irrigation for your crops.';
    }
    
    // Wind advice
    if ($wind > 30) {
        $advice[] = '💨 <strong>Strong winds expected.</strong> Consider staking tall crops or applying windbreaks.';
    }
    
    // Rain advice
    if ($precip > 5) {
        $advice[] = '🌧️ <strong>Rain expected.</strong> ' . ($precip > 20 ? 'Heavy rainfall - check drainage systems!' : 'Good for crop growth.');
    }
    
    if (empty($advice)) {
        return '🌾 <strong>Weather conditions are favorable</strong> for farming activities. Continue regular operations.';
    }
    
    return implode('<br>', $advice);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farm Weather - <?php echo htmlspecialchars($farm['farm_name']); ?></title>
    <link rel="stylesheet" href="farmer_common.css">
    <link rel="stylesheet" href="farmer_dashboard.css">
    <link rel="stylesheet" href="weather.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <div class="brand">
        <img src="../pictures/images/logo1.png" alt="KrishiMitra Logo">
        <h2>KrishiMitra</h2>
    </div>
    <div class="nav-actions">
        <a href="farmer_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="layout">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-heading">
            <span>FARMER PORTAL</span>
        </div>
        <nav>
            <a href="farmer_dashboard.php"><i class="fas fa-house"></i><span>Dashboard</span></a>
            <a href="manage_farms.php"><i class="fas fa-tractor"></i><span>My Farms</span></a>
            <a href="soil_data.php"><i class="fas fa-seedling"></i><span>Soil Data</span></a>
            <a href="crop_recommendation.php"><i class="fas fa-leaf"></i><span>Crop Advisory</span></a>
            <a class="active" href="weather.php"><i class="fas fa-cloud-sun"></i><span>Weather</span></a>
            <a href="market_prices.php"><i class="fas fa-indian-rupee-sign"></i><span>Market Prices</span></a>
            <a href="disease_detection.php"><i class="fas fa-bug"></i><span>Pest Detection</span></a>
            <a href="reports.php"><i class="fas fa-file-lines"></i><span>Reports</span></a>
        </nav>
    </aside>

    <!-- CONTENT -->
    <div class="content">
        <div class="welcome-box">
            <h2><i class="fas fa-cloud-sun"></i> Weather for <?php echo htmlspecialchars($farm['farm_name']); ?></h2>
            <p><?php echo htmlspecialchars($farm['location']); ?>, <?php echo htmlspecialchars($farm['district']); ?></p>
            <p style="font-size: 13px; color: #999;">
                <i class="fas fa-map-pin"></i> Coordinates: <?php echo $lat ? $lat . ', ' . $lon : 'Not set'; ?>
            </p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($weather_data): ?>
            <?php 
            $current = $weather_data['current'];
            $daily = $weather_data['daily'];
            ?>
            
            <!-- Current Weather -->
            <div class="current-weather">
                <div class="weather-main">
                    <div class="weather-icon">
                        <i class="fas fa-<?php echo getWeatherIcon($current['weather_code']); ?>"></i>
                    </div>
                    <div class="weather-temp">
                        <span class="temp"><?php echo round($current['temperature_2m']); ?>°C</span>
                        <span class="feels-like">Feels like <?php echo round($current['apparent_temperature']); ?>°C</span>
                    </div>
                    <div class="weather-desc">
                        <h3><?php echo $current['weather_description']; ?></h3>
                        <?php if (isset($current['precipitation'])): ?>
                            <span class="precip">🌧️ <?php echo $current['precipitation']; ?> mm</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="weather-details">
                    <div class="detail-item">
                        <i class="fas fa-water"></i>
                        <div>
                            <span class="label">Humidity</span>
                            <span class="value"><?php echo $current['relative_humidity_2m']; ?>%</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-wind"></i>
                        <div>
                            <span class="label">Wind Speed</span>
                            <span class="value"><?php echo $current['wind_speed_10m']; ?> km/h</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Farming Advisory -->
            <div class="advisory-box">
                <h3><i class="fas fa-lightbulb"></i> Farming Advisory for this Farm</h3>
                <div class="advisory-content">
                    <?php echo getFarmingAdvisory($current); ?>
                </div>
            </div>

            <!-- 3-Day Forecast -->
            <div class="forecast-section">
                <h3><i class="fas fa-calendar-alt"></i> 3-Day Forecast</h3>
                <div class="forecast-grid">
                    <?php for ($i = 0; $i < 3; $i++): ?>
                        <div class="forecast-card">
                            <div class="forecast-date">
                                <?php echo date('D, d M', strtotime($daily['time'][$i])); ?>
                            </div>
                            <div class="forecast-icon">
                                <i class="fas fa-<?php echo getWeatherIcon($daily['weather_code'][$i]); ?>"></i>
                            </div>
                            <div class="forecast-temp">
                                <span class="max"><?php echo round($daily['temperature_2m_max'][$i]); ?>°</span>
                                <span class="min"><?php echo round($daily['temperature_2m_min'][$i]); ?>°</span>
                            </div>
                            <div class="forecast-desc">
                                <?php echo getWeatherDescription($daily['weather_code'][$i]); ?>
                            </div>
                            <?php if (isset($daily['precipitation_sum'][$i]) && $daily['precipitation_sum'][$i] > 0): ?>
                                <div class="forecast-precip">
                                    🌧️ <?php echo $daily['precipitation_sum'][$i]; ?> mm
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <div style="margin-top: 15px;">
                <a href="manage_farms.php" class="btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Farms
                </a>
                <a href="weather_farm.php?farm_id=<?php echo $farm_id; ?>" class="btn-primary" style="background: #3498db;">
                    <i class="fas fa-sync"></i> Refresh
                </a>
            </div>

        <?php endif; ?>
    </div>
</div>

</body>
</html>