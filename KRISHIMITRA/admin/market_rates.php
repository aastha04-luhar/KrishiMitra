<?php
require_once '../config/database.php';

// Only logged-in admins
if (!isLoggedIn('admin')) {
    redirect('admin_login.php');
}

// =========================================================
// REPRESENTATIVE MARKET PRICE DATA (₹ per quintal)
// Shared reference dataset shown to farmers for selling decisions
// =========================================================
$market_data = [
    ['crop' => 'Wheat',      'variety' => 'Lokwan',      'state' => 'Punjab',       'price' => 2275, 'unit' => 'per quintal', 'trend' => 'up'],
    ['crop' => 'Wheat',      'variety' => 'Sharbati',    'state' => 'Madhya Pradesh','price' => 2450, 'unit' => 'per quintal', 'trend' => 'up'],
    ['crop' => 'Mustard',    'variety' => 'Yellow',      'state' => 'Rajasthan',    'price' => 5450, 'unit' => 'per quintal', 'trend' => 'up'],
    ['crop' => 'Maize',      'variety' => 'Hybrid',      'state' => 'Karnataka',    'price' => 1980, 'unit' => 'per quintal', 'trend' => 'down'],
    ['crop' => 'Chickpea',   'variety' => 'Desi',        'state' => 'Maharashtra',  'price' => 5900, 'unit' => 'per quintal', 'trend' => 'up'],
    ['crop' => 'Groundnut',  'variety' => 'Bold',        'state' => 'Gujarat',      'price' => 6750, 'unit' => 'per quintal', 'trend' => 'stable'],
    ['crop' => 'Cotton',     'variety' => 'K8',          'state' => 'Gujarat',      'price' => 6650, 'unit' => 'per quintal', 'trend' => 'up'],
    ['crop' => 'Bajra',      'variety' => 'Hybrid',      'state' => 'Rajasthan',    'price' => 2250, 'unit' => 'per quintal', 'trend' => 'down'],
    ['crop' => 'Rice',       'variety' => 'Paddy (Common)','state' => 'Telangana',  'price' => 2200, 'unit' => 'per quintal', 'trend' => 'stable'],
    ['crop' => 'Tomato',     'variety' => 'Deshi',       'state' => 'Maharashtra',  'price' => 1450, 'unit' => 'per 40 kg',   'trend' => 'up'],
    ['crop' => 'Onion',      'variety' => 'Nasik',       'state' => 'Maharashtra',  'price' => 1750, 'unit' => 'per quintal', 'trend' => 'up'],
    ['crop' => 'Potato',     'variety' => 'Jyoti',       'state' => 'Uttar Pradesh','price' => 1550, 'unit' => 'per quintal', 'trend' => 'stable'],
    ['crop' => 'Soybean',    'variety' => 'Yellow',      'state' => 'Madhya Pradesh','price' => 4600, 'unit' => 'per quintal', 'trend' => 'down'],
    ['crop' => 'Sugarcane',  'variety' => 'CO-86032',    'state' => 'Maharashtra',  'price' => 3400, 'unit' => 'per tonne',   'trend' => 'stable'],
    ['crop' => 'Tur Dal',    'variety' => 'Arhar',       'state' => 'Karnataka',    'price' => 7250, 'unit' => 'per quintal', 'trend' => 'up'],
];

// Filtering
$search = isset($_GET['search']) ? strtolower(sanitize($_GET['search'])) : '';
$trend_filter = isset($_GET['trend']) ? sanitize($_GET['trend']) : '';

$filtered = array_filter($market_data, function ($item) use ($search, $trend_filter) {
    $match_search = $search === '' ||
                    strpos(strtolower($item['crop']), $search) !== false ||
                    strpos(strtolower($item['variety']), $search) !== false ||
                    strpos(strtolower($item['state']), $search) !== false;
    $match_trend = $trend_filter === '' || $item['trend'] === $trend_filter;
    return $match_search && $match_trend;
});

$all_prices = array_column($market_data, 'price');
$avg_price = round(array_sum($all_prices) / count($all_prices));
$up_count   = count(array_filter($market_data, fn($i) => $i['trend'] === 'up'));
$down_count = count(array_filter($market_data, fn($i) => $i['trend'] === 'down'));
$highest = max($all_prices);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Market Rates - KrishiMitra Admin</title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="stylesheet" href="manage_farmers.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .mr-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:22px; }
        .mr-stat { background:#fff; border:1px solid #e4ebe6; border-radius:12px; padding:18px; box-shadow:0 2px 10px rgba(0,0,0,0.04); }
        .mr-stat .lbl { color:#8a958e; font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
        .mr-stat .val { color:#1e8449; font-size:24px; font-weight:600; margin-top:5px; }
        .mr-stat.up .val { color:#258049; }
        .mr-stat.down .val { color:#c34d3a; }
        .trend-badge { display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; }
        .trend-badge.up { background:#e7f7ec; color:#258049; }
        .trend-badge.down { background:#fff0ed; color:#c34d3a; }
        .trend-badge.stable { background:#eef3ff; color:#3d6bb3; }
        .price-cell { font-weight:700; color:#1e8449; }
        .note { font-size:11px; color:#9aa39d; margin-top:14px; }
        @media (max-width:800px){ .mr-stats{grid-template-columns:1fr 1fr;} }
        @media (max-width:520px){ .mr-stats{grid-template-columns:1fr;} }
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
        <a class="active" href="market_rates.php"><i class="fas fa-rupee-sign"></i> Market Rates</a>
        <a href="pest_disease.php"><i class="fas fa-bug"></i> Pest &amp; Disease Data</a>
        <a href="alerts_notifications.php"><i class="fas fa-bell"></i> Alerts &amp; Notifications</a>
        <a href="reports_analytics.php"><i class="fas fa-file-alt"></i> Reports &amp; Analytics</a>
        <a href="system_logs.php"><i class="fas fa-database"></i> System Logs</a>
    </div>

    <div class="content">
        <div class="welcome-box">
            <h2><i class="fas fa-rupee-sign"></i> Market Rates</h2>
            <p>Agricultural produce prices shared with farmers for better selling decisions</p>
        </div>

        <!-- Stats -->
        <div class="mr-stats">
            <div class="mr-stat"><div class="lbl">Commodities Tracked</div><div class="val"><?php echo count($market_data); ?></div></div>
            <div class="mr-stat up"><div class="lbl">Prices Rising</div><div class="val"><?php echo $up_count; ?></div></div>
            <div class="mr-stat down"><div class="lbl">Prices Falling</div><div class="val"><?php echo $down_count; ?></div></div>
            <div class="mr-stat"><div class="lbl">Avg Price</div><div class="val">₹<?php echo number_format($avg_price); ?></div></div>
        </div>

        <!-- Filter -->
        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <div class="filter-group">
                    <input type="text" name="search" placeholder="Search by crop, variety or state..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <select name="trend">
                        <option value="">All Trends</option>
                        <option value="up" <?php echo $trend_filter === 'up' ? 'selected' : ''; ?>>Rising</option>
                        <option value="down" <?php echo $trend_filter === 'down' ? 'selected' : ''; ?>>Falling</option>
                        <option value="stable" <?php echo $trend_filter === 'stable' ? 'selected' : ''; ?>>Stable</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Search</button>
                </div>
                <div class="filter-group">
                    <a href="market_rates.php" class="btn-reset"><i class="fas fa-undo"></i> Reset</a>
                </div>
            </form>
        </div>

        <div class="farmers-table">
            <div class="table-header">
                <h3><i class="fas fa-list"></i> Commodity Price List</h3>
                <span class="record-count"><?php echo count($filtered); ?> commodities</span>
            </div>

            <?php if (count($filtered) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr><th>Crop</th><th>Variety</th><th>State</th><th>Price</th><th>Trend</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filtered as $item): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['crop']); ?></strong></td>
                                <td><?php echo htmlspecialchars($item['variety']); ?></td>
                                <td><?php echo htmlspecialchars($item['state']); ?></td>
                                <td class="price-cell">₹<?php echo number_format($item['price']); ?> <small style="color:#9aa39d;font-weight:400;"><?php echo $item['unit']; ?></small></td>
                                <td>
                                    <span class="trend-badge <?php echo $item['trend']; ?>">
                                        <i class="fas fa-<?php echo $item['trend'] === 'up' ? 'arrow-up' : ($item['trend'] === 'down' ? 'arrow-down' : 'minus'); ?>"></i>
                                        <?php echo ucfirst($item['trend']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-rupee-sign"></i>
                    <p>No commodities found matching your criteria.</p>
                    <a href="market_rates.php" class="btn-primary"><i class="fas fa-undo"></i> Clear Filters</a>
                </div>
            <?php endif; ?>
            <p class="note"><i class="fas fa-info-circle"></i> Prices are indicative reference values. Actual mandi rates may vary by location and season.</p>
        </div>
    </div>
</div>

</body>
</html>
