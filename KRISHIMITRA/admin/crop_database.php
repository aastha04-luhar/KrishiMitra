<?php
require_once '../config/database.php';

// Only logged-in admins
if (!isLoggedIn('admin')) {
    redirect('admin_login.php');
}

// Search filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Get all crops
$sql = "SELECT * FROM crops";
$params = [];
if ($search) {
    $sql .= " WHERE crop_name LIKE ? OR soil_type LIKE ?";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
}
$sql .= " ORDER BY crop_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$crops = $stmt->fetchAll();

$total_crops = count($crops);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crop Database - KrishiMitra Admin</title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="stylesheet" href="manage_farmers.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
        <a class="active" href="crop_database.php"><i class="fas fa-seedling"></i> Crop Database</a>
        <a href="weather_admin.php"><i class="fas fa-cloud-sun"></i> Weather System</a>
        <a href="market_rates.php"><i class="fas fa-rupee-sign"></i> Market Rates</a>
        <a href="pest_disease.php"><i class="fas fa-bug"></i> Pest &amp; Disease Data</a>
        <a href="alerts_notifications.php"><i class="fas fa-bell"></i> Alerts &amp; Notifications</a>
        <a href="reports_analytics.php"><i class="fas fa-file-alt"></i> Reports &amp; Analytics</a>
        <a href="system_logs.php"><i class="fas fa-database"></i> System Logs</a>
    </div>

    <div class="content">
        <div class="welcome-box">
            <h2><i class="fas fa-seedling"></i> Crop Database</h2>
            <p>View all crops and their recommended growing parameters</p>
        </div>

        <div class="stats-grid">
            <div class="stat-box">
                <i class="fas fa-seedling" style="color:#27ae60;"></i>
                <div class="stat-info"><h3>Total Crops</h3><p><?php echo $total_crops; ?></p></div>
            </div>
        </div>

        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <div class="filter-group">
                    <input type="text" name="search" placeholder="Search by crop name or soil type..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Search</button>
                </div>
                <div class="filter-group">
                    <a href="crop_database.php" class="btn-reset"><i class="fas fa-undo"></i> Reset</a>
                </div>
            </form>
        </div>

        <div class="farmers-table">
            <div class="table-header">
                <h3><i class="fas fa-book"></i> Crop Parameters</h3>
                <span class="record-count"><?php echo $total_crops; ?> crops</span>
            </div>

            <?php if (count($crops) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Crop</th>
                                <th>Nitrogen (N)</th>
                                <th>Phosphorus (P)</th>
                                <th>Potassium (K)</th>
                                <th>pH</th>
                                <th>Temperature (°C)</th>
                                <th>Rainfall (mm)</th>
                                <th>Humidity (%)</th>
                                <th>Soil Type</th>
                                <th>Water Req.</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($crops as $crop): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><strong><?php echo htmlspecialchars($crop['crop_name']); ?></strong></td>
                                <td><?php echo $crop['min_nitrogen']; ?> - <?php echo $crop['max_nitrogen']; ?></td>
                                <td><?php echo $crop['min_phosphorus']; ?> - <?php echo $crop['max_phosphorus']; ?></td>
                                <td><?php echo $crop['min_potassium']; ?> - <?php echo $crop['max_potassium']; ?></td>
                                <td><?php echo $crop['min_ph']; ?> - <?php echo $crop['max_ph']; ?></td>
                                <td><?php echo $crop['min_temperature']; ?> - <?php echo $crop['max_temperature']; ?></td>
                                <td><?php echo $crop['min_rainfall']; ?> - <?php echo $crop['max_rainfall']; ?></td>
                                <td><?php echo $crop['min_humidity']; ?> - <?php echo $crop['max_humidity']; ?></td>
                                <td><?php echo htmlspecialchars($crop['soil_type']); ?></td>
                                <td><?php echo htmlspecialchars($crop['water_requirement']); ?></td>
                                <td><?php echo htmlspecialchars($crop['crop_duration']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-seedling"></i>
                    <p>No crops found matching your criteria.</p>
                    <a href="crop_database.php" class="btn-primary"><i class="fas fa-undo"></i> Clear Search</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
