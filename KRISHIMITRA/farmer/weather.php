<?php
require_once '../config/database.php';

// Check if farmer is logged in
if (!isLoggedIn('farmer')) {
    redirect('farmer_login.php');
}

$farmer_id = $_SESSION['farmer_id'];

// Get farmer's location from database
$stmt = $pdo->prepare("SELECT location FROM farmers WHERE id = ?");
$stmt->execute([$farmer_id]);
$farmer = $stmt->fetch();
$location = $farmer['location'];

// Get coordinates (you could store these in the farmers table)
// For now, we'll use a simple mapping or ask the farmer
$coordinates = getCoordinates($location);

$weather_data = null;
$error = null;

// Cache key - store weather for 30 minutes to avoid excessive API calls
$cache_key = 'weather_' . $farmer_id;
$cached = getWeatherFromCache($cache_key);

if ($cached) {
    $weather_data = $cached;
} else {
    // Fetch from API
    $weather_data = fetchWeatherFromAPI($coordinates['lat'], $coordinates['lon']);
    if ($weather_data) {
        saveWeatherToCache($cache_key, $weather_data);
    } else {
        $error = 'Could not fetch weather data. Please try again later.';
    }
}

// Helper functions
function getCoordinates($location) {
    // Simple mapping for common locations
    // In a real app, you'd use a geocoding API
    $locations = [
        'Mehsana' => ['lat' => 23.59, 'lon' => 72.37],
        'Ahmedabad' => ['lat' => 23.02, 'lon' => 72.57],
        'Gandhinagar' => ['lat' => 23.22, 'lon' => 72.65],
        
    ];
    
    if (isset($locations[$location])) {
        return $locations[$location];
    }
    
    // Default to Ahmedabad if location not found
    return ['lat' => 23.02, 'lon' => 72.57];
}

function fetchWeatherFromAPI($lat, $lon) {
    // Build the API URL with all parameters
    $url = "https://api.open-meteo.com/v1/forecast?" . http_build_query([
        'latitude' => $lat,
        'longitude' => $lon,
        'current' => 'temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code,apparent_temperature',
        'daily' => 'weather_code,temperature_2m_max,temperature_2m_min',
        'timezone' => 'auto',
        'forecast_days' => 3
    ]);
    
    // Use cURL to fetch data
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Only for local development
    
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
    
    // Add a human-readable weather description
    $data['current']['weather_description'] = getWeatherDescription($data['current']['weather_code']);
    
    return $data;
}

function getWeatherDescription($code) {
    // WMO Weather Interpretation Codes
    // Source: https://open-meteo.com/en/docs
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

function getWeatherFromCache($key) {
    // Simple file-based caching
    $cache_dir = '../cache/';
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0777, true);
    }
    
    $cache_file = $cache_dir . $key . '.json';
    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        if ($data && isset($data['expires']) && $data['expires'] > time()) {
            return $data['weather'];
        }
    }
    return null;
}

function saveWeatherToCache($key, $weather) {
    $cache_dir = '../cache/';
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0777, true);
    }
    
    $cache_file = $cache_dir . $key . '.json';
    $data = [
        'weather' => $weather,
        'expires' => time() + (30 * 60) // 30 minutes cache
    ];
    file_put_contents($cache_file, json_encode($data));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather Advisory - KrishiMitra</title>
    <link rel="stylesheet" href="farmer_common.css">
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
            <h2><i class="fas fa-cloud-sun"></i> Weather Advisory</h2>
            <p>Real-time weather for <?php echo htmlspecialchars($location); ?></p>
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
                <h3><i class="fas fa-lightbulb"></i> Farming Advisory</h3>
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
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Cache Info -->
            <div class="cache-info">
                <small><i class="fas fa-clock"></i> Weather data updated every 30 minutes</small>
                <br>
                <small>Location: <?php echo htmlspecialchars($location); ?> (<?php echo $coordinates['lat']; ?>, <?php echo $coordinates['lon']; ?>)</small>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php
// Helper functions for display
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
    $code = $weather['weather_code'];
    
    $advice = [];
    
    // Temperature advice
    if ($temp > 35) {
        $advice[] = '🌡️ High temperature alert! Irrigate crops in the early morning or evening to prevent water loss.';
    } elseif ($temp < 10) {
        $advice[] = '❄️ Cold conditions expected. Protect sensitive crops with mulching or covering.';
    }
    
    // Humidity advice
    if ($humidity > 80) {
        $advice[] = '💧 High humidity detected. Monitor for fungal diseases in crops.';
    } elseif ($humidity < 30) {
        $advice[] = '🌵 Low humidity. Ensure adequate irrigation for your crops.';
    }
    
    // Wind advice
    if ($wind > 30) {
        $advice[] = '💨 Strong winds expected. Consider staking tall crops or applying windbreaks.';
    }
    
    // Rain advice
    if ($code >= 51 && $code <= 67) {
        $advice[] = '🌧️ Rain expected. Avoid applying fertilizers or pesticides before rainfall.';
    }
    
    if (empty($advice)) {
        return '🌾 Weather conditions are favorable for farming activities. Continue regular operations.';
    }
    
    return implode('<br>', $advice);
}
?>

</body>
</html>