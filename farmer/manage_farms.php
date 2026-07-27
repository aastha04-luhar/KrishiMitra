<?php
require_once '../config/database.php';

// Check if farmer is logged in
if (!isLoggedIn('farmer')) {
    redirect('farmer_login.php');
}

$farmer_id = $_SESSION['farmer_id'];
$message = '';
$message_type = '';

// Add new farm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_farm'])) {
    $farm_name = sanitize($_POST['farm_name']);
    $location = sanitize($_POST['location']);
    $farm_size = sanitize($_POST['farm_size']);
    $soil_type = sanitize($_POST['soil_type']);
    
    if (empty($farm_name) || empty($location) || empty($farm_size)) {
        $message = 'Please fill in all required fields!';
        $message_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO farms (farmer_id, farm_name, location, farm_size, soil_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$farmer_id, $farm_name, $location, $farm_size, $soil_type]);
            $message = 'Farm added successfully!';
            $message_type = 'success';
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                $message = 'You already have a farm with this name!';
            } else {
                $message = 'Error: ' . $e->getMessage();
            }
            $message_type = 'error';
        }
    }
}

// Delete farm
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM farms WHERE id = ? AND farmer_id = ?");
        $stmt->execute([$id, $farmer_id]);
        $message = 'Farm deleted successfully!';
        $message_type = 'success';
    } catch(PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Get all farms for this farmer
$stmt = $pdo->prepare("SELECT * FROM farms WHERE farmer_id = ? ORDER BY created_at DESC");
$stmt->execute([$farmer_id]);
$farms = $stmt->fetchAll();

// ✅ FIXED: Get farm statistics with proper error handling
$total_farms = 0;
$total_tests = 0;

try {
    // Count total farms
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM farms WHERE farmer_id = ?");
    $stmt->execute([$farmer_id]);
    $result = $stmt->fetch();
    $total_farms = $result ? $result['total'] : 0;
    
    // Count total soil tests
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM soil_data WHERE farmer_id = ?");
    $stmt->execute([$farmer_id]);
    $result = $stmt->fetch();
    $total_tests = $result ? $result['total'] : 0;
} catch(PDOException $e) {
    // If soil_data table doesn't exist yet, just set to 0
    $total_tests = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Farms - KrishiMitra</title>
    <link rel="stylesheet" href="farmer_dashboard.css">
    <link rel="stylesheet" href="manage_farms.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <div class="brand">
        <img src="../assets/images/logo1.png" alt="KrishiMitra Logo" onerror="this.src='../pictures/images/logo1.png'">
        <h2>KrishiMitra</h2>
    </div>
    <div class="nav-actions">
        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['farmer_name']); ?></span>
        <a href="farmer_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="layout">
    <!-- SIDEBAR -->
    <div class="sidebar">
        <h3>Farmer Menu</h3>
        <a href="farmer_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a class="active" href="manage_farms.php"><i class="fas fa-tractor"></i> My Farms</a>
        <a href="soil_data.php"><i class="fas fa-seedling"></i> Soil Data</a>
        <a href="crop_recommendation.php"><i class="fas fa-tractor"></i> Crop Advisory</a>
        <a href="weather.php"><i class="fas fa-cloud-sun"></i> Weather</a>
        <a href="market_prices.php"><i class="fas fa-rupee-sign"></i> Market Prices</a>
        <a href="disease_detection.php"><i class="fas fa-bug"></i> Pest Detection</a>
        <a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a>
    </div>

    <!-- CONTENT -->
    <div class="content">
        <div class="welcome-box">
            <h2><i class="fas fa-tractor"></i> My Farms</h2>
            <p>Manage your farms and their soil data</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- ✅ FIXED: Farm Statistics with null checks -->
        <div class="farm-stats">
            <div class="stat-card">
                <i class="fas fa-tractor"></i>
                <div>
                    <h3>Total Farms</h3>
                    <p><?php echo isset($total_farms) ? $total_farms : 0; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-flask"></i>
                <div>
                    <h3>Soil Tests</h3>
                    <p><?php echo isset($total_tests) ? $total_tests : 0; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-leaf"></i>
                <div>
                    <h3>Active Crops</h3>
                    <p>0</p>
                </div>
            </div>
        </div>

        <!-- Add Farm Form -->
        <div class="farm-form">
            <h3><i class="fas fa-plus-circle" style="color: #27ae60;"></i> Add New Farm</h3>
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Farm Name <span class="required">*</span></label>
                        <input type="text" name="farm_name" placeholder="e.g., Main Farm, Organic Plot" required>
                    </div>
                    <div class="form-group">
                        <label>Location <span class="required">*</span></label>
                        <input type="text" name="location" placeholder="Village/District" required>
                    </div>
                    <div class="form-group">
                        <label>Farm Size (acres) <span class="required">*</span></label>
                        <input type="number" name="farm_size" step="0.01" min="0.01" placeholder="e.g., 5.5" required>
                    </div>
                    <div class="form-group">
                        <label>Soil Type</label>
                        <select name="soil_type">
                            <option value="">Select soil type...</option>
                            <option value="Sandy">Sandy</option>
                            <option value="Clay">Clay</option>
                            <option value="Silt">Silt</option>
                            <option value="Loamy">Loamy</option>
                            <option value="Peaty">Peaty</option>
                            <option value="Chalky">Chalky</option>
                            <option value="Black">Black Soil</option>
                            <option value="Red">Red Soil</option>
                            <option value="Laterite">Laterite</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_farm" class="btn-primary">
                    <i class="fas fa-plus"></i> Add Farm
                </button>
            </form>
        </div>

        <!-- Farms List -->
        <div class="farms-grid">
            <?php if (count($farms) > 0): ?>
                <?php foreach ($farms as $farm): 
                    // Get latest soil data for this farm
                    try {
                        $stmt = $pdo->prepare("SELECT * FROM soil_data WHERE farm_id = ? ORDER BY test_date DESC LIMIT 1");
                        $stmt->execute([$farm['id']]);
                        $latest_soil = $stmt->fetch();
                    } catch(PDOException $e) {
                        $latest_soil = null;
                    }
                ?>
                <div class="farm-card">
                    <div class="farm-header">
                        <h3><i class="fas fa-tractor"></i> <?php echo htmlspecialchars($farm['farm_name']); ?></h3>
                        <div class="farm-actions">
                            <a href="soil_data.php?farm_id=<?php echo $farm['id']; ?>" class="btn-sm btn-add">
                                <i class="fas fa-plus"></i> Add Soil
                            </a>
                            <a href="#" onclick="confirmDelete(<?php echo $farm['id']; ?>)" class="btn-sm btn-delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                    <div class="farm-details">
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($farm['location']); ?></p>
                        <p><i class="fas fa-ruler-combined"></i> <?php echo $farm['farm_size']; ?> acres</p>
                        <?php if ($farm['soil_type']): ?>
                            <p><i class="fas fa-mountain"></i> <?php echo htmlspecialchars($farm['soil_type']); ?> Soil</p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($latest_soil): ?>
                    <div class="farm-soil-summary">
                        <h4>Latest Soil Test (<?php echo date('d M Y', strtotime($latest_soil['test_date'])); ?>)</h4>
                        <div class="soil-values">
                            <span><strong>N:</strong> <?php echo $latest_soil['nitrogen']; ?> ppm</span>
                            <span><strong>P:</strong> <?php echo $latest_soil['phosphorus']; ?> ppm</span>
                            <span><strong>K:</strong> <?php echo $latest_soil['potassium']; ?> ppm</span>
                            <span><strong>pH:</strong> <?php echo $latest_soil['ph']; ?></span>
                        </div>
                        <a href="crop_recommendation.php?farm_id=<?php echo $farm['id']; ?>" class="btn-recommend">
                            <i class="fas fa-tractor"></i> Get Recommendation
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="farm-soil-summary no-soil">
                        <p><i class="fas fa-info-circle"></i> No soil data added yet.</p>
                        <a href="soil_data.php?farm_id=<?php echo $farm['id']; ?>" class="btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add First Soil Test
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-tractor"></i>
                    <p>You haven't added any farms yet.</p>
                    <p style="font-size: 14px; color: #999;">Add your first farm to start tracking soil data.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this farm? All associated soil data will also be deleted.')) {
        window.location.href = '?delete=' + id;
    }
}

// Set today's date as default for any date inputs
document.addEventListener('DOMContentLoaded', function() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value) {
            const today = new Date().toISOString().split('T')[0];
            input.value = today;
        }
    });
});
</script>

</body>
</html>