<?php
require_once '../config/database.php';

// Only logged-in admins
if (!isLoggedIn('admin')) {
    redirect('admin_login.php');
}

$farmer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get farmer
$stmt = $pdo->prepare("SELECT * FROM farmers WHERE id = ?");
$stmt->execute([$farmer_id]);
$farmer = $stmt->fetch();

if (!$farmer) {
    redirect('manage_farmers.php?msg=nofound');
}

// Get farms
$stmt = $pdo->prepare("SELECT * FROM farms WHERE farmer_id = ? ORDER BY created_at DESC");
$stmt->execute([$farmer_id]);
$farms = $stmt->fetchAll();

// Get soil data
$stmt = $pdo->prepare("
    SELECT sd.*, f.farm_name
    FROM soil_data sd
    LEFT JOIN farms f ON sd.farm_id = f.id
    WHERE sd.farmer_id = ?
    ORDER BY sd.test_date DESC
");
$stmt->execute([$farmer_id]);
$soil_records = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Details - KrishiMitra</title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="stylesheet" href="manage_farmers.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .detail-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:22px; }
        .detail-box { background:#fff; border:1px solid #e4ebe6; border-radius:12px; padding:16px; }
        .detail-box .lbl { color:#8a958e; font-size:9px; font-weight:600; text-transform:uppercase; }
        .detail-box .val { color:#28543a; font-size:16px; font-weight:600; margin-top:4px; }
        .table-wrap { background:#fff; border:1px solid #e4ebe6; border-radius:14px; padding:22px; margin-bottom:22px; }
        .table-wrap h3 { color:#28543a; font-size:16px; margin-bottom:14px; }
        .table-wrap table { width:100%; border-collapse:collapse; }
        .table-wrap th { padding:11px 12px; background:#f6f9f7; color:#647168; text-align:left; font-size:9px; font-weight:600; border-bottom:1px solid #e5ebe7; }
        .table-wrap td { padding:12px; color:#6b776f; font-size:10px; border-bottom:1px solid #edf1ee; }
        @media (max-width:700px){ .detail-grid{grid-template-columns:1fr 1fr;} }
        @media (max-width:480px){ .detail-grid{grid-template-columns:1fr;} }
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
        <a class="active" href="manage_farmers.php"><i class="fas fa-users"></i> Farmer Management</a>
        <a href="crop_database.php"><i class="fas fa-seedling"></i> Crop Database</a>
        <a href="weather_admin.php"><i class="fas fa-cloud-sun"></i> Weather System</a>
        <a href="market_rates.php"><i class="fas fa-rupee-sign"></i> Market Rates</a>
        <a href="pest_disease.php"><i class="fas fa-bug"></i> Pest &amp; Disease Data</a>
        <a href="alerts_notifications.php"><i class="fas fa-bell"></i> Alerts &amp; Notifications</a>
        <a href="reports_analytics.php"><i class="fas fa-file-alt"></i> Reports &amp; Analytics</a>
        <a href="system_logs.php"><i class="fas fa-database"></i> System Logs</a>
    </div>

    <div class="content">
        <div class="welcome-box">
            <h2><i class="fas fa-user"></i> Farmer Details</h2>
            <p>Complete profile and activity for <?php echo htmlspecialchars($farmer['name']); ?></p>
        </div>

        <a href="manage_farmers.php" class="btn-secondary" style="margin-bottom:18px;display:inline-flex;align-items:center;gap:6px;padding:9px 14px;border:1px solid #d9e5dd;border-radius:8px;font-size:11px;text-decoration:none;color:#597064;">
            <i class="fas fa-arrow-left"></i> Back to Farmers
        </a>

        <div class="detail-grid">
            <div class="detail-box"><div class="lbl">Name</div><div class="val"><?php echo htmlspecialchars($farmer['name']); ?></div></div>
            <div class="detail-box"><div class="lbl">Location</div><div class="val"><?php echo htmlspecialchars($farmer['location']); ?></div></div>
            <div class="detail-box"><div class="lbl">Phone</div><div class="val"><?php echo htmlspecialchars($farmer['phone']); ?></div></div>
            <div class="detail-box"><div class="lbl">Status</div><div class="val"><?php echo ucfirst($farmer['status']); ?></div></div>
            <div class="detail-box"><div class="lbl">District</div><div class="val"><?php echo htmlspecialchars($farmer['district'] ?? 'Not set'); ?></div></div>
            <div class="detail-box"><div class="lbl">Registered</div><div class="val"><?php echo date('d M Y', strtotime($farmer['created_at'])); ?></div></div>
        </div>

        <div class="table-wrap">
            <h3><i class="fas fa-tractor"></i> Farms (<?php echo count($farms); ?>)</h3>
            <?php if (count($farms) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead><tr><th>#</th><th>Farm Name</th><th>Location</th><th>Size (acres)</th><th>Soil Type</th></tr></thead>
                    <tbody>
                        <?php $i=1; foreach ($farms as $f): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
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
                <p style="font-size:11px;color:#929c95;">No farms added.</p>
            <?php endif; ?>
        </div>

        <div class="table-wrap">
            <h3><i class="fas fa-flask"></i> Soil Tests (<?php echo count($soil_records); ?>)</h3>
            <?php if (count($soil_records) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead><tr><th>#</th><th>Farm</th><th>Date</th><th>N</th><th>P</th><th>K</th><th>pH</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php $i=1; foreach ($soil_records as $sd):
                            $health = getSoilHealthStatus($sd['nitrogen'], $sd['phosphorus'], $sd['potassium']);
                        ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($sd['farm_name']); ?></td>
                            <td><?php echo date('d M Y', strtotime($sd['test_date'])); ?></td>
                            <td><?php echo $sd['nitrogen']; ?></td>
                            <td><?php echo $sd['phosphorus']; ?></td>
                            <td><?php echo $sd['potassium']; ?></td>
                            <td><?php echo $sd['ph']; ?></td>
                            <td><span class="status-badge <?php echo $health['status']; ?>"><?php echo $health['label']; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p style="font-size:11px;color:#929c95;">No soil test data available.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
