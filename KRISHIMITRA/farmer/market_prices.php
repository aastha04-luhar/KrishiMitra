<?php
require_once '../config/database.php';

// Check if farmer is logged in
if (!isLoggedIn('farmer')) {
    redirect('farmer_login.php');
}

// =========================================================
// SAMPLE MARKET PRICE DATA (₹ per quintal)
// Representative Indian mandi prices for better selling decisions
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

// Statistics for the summary cards
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
    <title>Market Prices - KrishiMitra</title>
    <link rel="stylesheet" href="farmer_common.css">
    <link rel="stylesheet" href="farmer_dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .market-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:22px; }
        .market-stat { background:#fff; border:1px solid #e4ebe6; border-radius:12px; padding:18px; box-shadow:0 3px 12px rgba(29,73,42,0.04); }
        .market-stat .lbl { color:#8a958e; font-size:9px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
        .market-stat .val { color:#28543a; font-size:21px; font-weight:600; margin-top:5px; }
        .market-stat.up .val { color:#258049; }
        .market-stat.down .val { color:#c34d3a; }

        .filter-bar { display:flex; flex-wrap:wrap; gap:12px; align-items:center; padding:16px 20px; background:#fff; border:1px solid #e4ebe6; border-radius:12px; margin-bottom:22px; }
        .filter-bar input, .filter-bar select { padding:9px 12px; border:1px solid #dce5df; border-radius:8px; font-family:'Poppins',sans-serif; font-size:10px; outline:none; }
        .filter-bar input { flex:1; min-width:200px; }
        .filter-bar input:focus, .filter-bar select:focus { border-color:#27ae60; }

        .market-table { background:#fff; border:1px solid #e4ebe6; border-radius:14px; padding:24px; box-shadow:0 3px 15px rgba(29,73,42,0.04); }
        .market-table h3 { color:#28543a; font-size:17px; margin-bottom:18px; }
        .market-table table { width:100%; border-collapse:collapse; }
        .market-table th { padding:12px 13px; background:#f6f9f7; color:#647168; text-align:left; font-size:9px; font-weight:600; border-bottom:1px solid #e5ebe7; }
        .market-table td { padding:13px; color:#6b776f; font-size:10px; border-bottom:1px solid #edf1ee; }
        .market-table tbody tr:hover { background:#fafcfb; }
        .price-cell { font-weight:700; color:#28543a; font-size:13px; }
        .trend-badge { display:inline-flex; align-items:center; gap:4px; padding:4px 9px; border-radius:20px; font-size:8px; font-weight:600; }
        .trend-badge.up { background:#e7f7ec; color:#258049; }
        .trend-badge.down { background:#fff0ed; color:#c34d3a; }
        .trend-badge.stable { background:#eef3ff; color:#3d6bb3; }
        .note { font-size:9px; color:#9aa39d; margin-top:14px; }
        @media (max-width:900px){ .market-stats{grid-template-columns:1fr 1fr;} }
        @media (max-width:600px){ .market-stats{grid-template-columns:1fr;} .table-responsive{overflow-x:auto;} }
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
            <a class="active" href="market_prices.php"><i class="fas fa-indian-rupee-sign"></i><span>Market Prices</span></a>
            <a href="disease_detection.php"><i class="fas fa-bug"></i><span>Pest Detection</span></a>
            <a href="reports.php"><i class="fas fa-file-lines"></i><span>Reports</span></a>
        </nav>
    </aside>

    <!-- CONTENT -->
    <div class="content">
        <div class="page-header">
            <div>
                <h1><i class="fas fa-indian-rupee-sign"></i> Market Prices</h1>
                <p>Agricultural produce prices to make better selling decisions</p>
            </div>
        </div>

        <!-- Statistics -->
        <div class="market-stats">
            <div class="market-stat">
                <div class="lbl">Commodities Tracked</div>
                <div class="val"><?php echo count($market_data); ?></div>
            </div>
            <div class="market-stat up">
                <div class="lbl">Prices Rising</div>
                <div class="val"><?php echo $up_count; ?></div>
            </div>
            <div class="market-stat down">
                <div class="lbl">Prices Falling</div>
                <div class="val"><?php echo $down_count; ?></div>
            </div>
            <div class="market-stat">
                <div class="lbl">Highest Price</div>
                <div class="val">₹<?php echo number_format($highest); ?></div>
            </div>
        </div>

        <!-- Filter -->
        <div class="filter-bar">
            <form method="GET" action="" style="display:flex;flex-wrap:wrap;gap:12px;flex:1;">
                <input type="text" name="search" placeholder="Search by crop, variety or state..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="trend">
                    <option value="">All Trends</option>
                    <option value="up" <?php echo $trend_filter === 'up' ? 'selected' : ''; ?>>Rising</option>
                    <option value="down" <?php echo $trend_filter === 'down' ? 'selected' : ''; ?>>Falling</option>
                    <option value="stable" <?php echo $trend_filter === 'stable' ? 'selected' : ''; ?>>Stable</option>
                </select>
                <button type="submit" class="btn-primary"><i class="fas fa-search"></i> Search</button>
                <a href="market_prices.php" class="btn-secondary"><i class="fas fa-undo"></i> Reset</a>
            </form>
        </div>

        <!-- Table -->
        <div class="market-table">
            <h3><i class="fas fa-list"></i> Commodity Price List</h3>
            <?php if (count($filtered) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Crop</th>
                            <th>Variety</th>
                            <th>State</th>
                            <th>Price</th>
                            <th>Trend</th>
                        </tr>
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
                    <a href="market_prices.php" class="btn-primary" style="margin-top:10px;">Clear Filters</a>
                </div>
            <?php endif; ?>
            <p class="note"><i class="fas fa-info-circle"></i> Prices are indicative reference values. Actual mandi rates may vary by location and season.</p>
        </div>
    </div>
</div>

</body>
</html>
