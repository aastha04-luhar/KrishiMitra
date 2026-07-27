<?php
require_once '../config/database.php';

// Check if farmer is logged in
if (!isLoggedIn('farmer')) {
    redirect('farmer_login.php');
}

$farmer_id = $_SESSION['farmer_id'];
$message = '';
$message_type = '';

// Get selected farm_id from URL or POST
$selected_farm_id = isset($_GET['farm_id']) ? (int)$_GET['farm_id'] : 0;
$selected_farm_id = isset($_POST['farm_id']) ? (int)$_POST['farm_id'] : $selected_farm_id;

// Delete soil data
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM soil_data WHERE id = ? AND farmer_id = ?");
        $stmt->execute([$id, $farmer_id]);
        $message = 'Soil data deleted successfully!';
        $message_type = 'success';
    } catch(PDOException $e) {
        $message = 'Error deleting data: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Add soil data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_soil'])) {
    $farm_id = (int)$_POST['farm_id'];
    $nitrogen = sanitize($_POST['nitrogen']);
    $phosphorus = sanitize($_POST['phosphorus']);
    $potassium = sanitize($_POST['potassium']);
    $ph = sanitize($_POST['ph']);
    $temperature = sanitize($_POST['temperature']);
    $humidity = sanitize($_POST['humidity']);
    $rainfall = sanitize($_POST['rainfall']);
    $test_date = sanitize($_POST['test_date']);
    
    // Validation
    $errors = [];
    if ($farm_id <= 0) $errors[] = 'Please select a farm!';
    if ($nitrogen < 0 || $nitrogen > 200) $errors[] = 'Nitrogen should be between 0-200 ppm';
    if ($phosphorus < 0 || $phosphorus > 150) $errors[] = 'Phosphorus should be between 0-150 ppm';
    if ($potassium < 0 || $potassium > 200) $errors[] = 'Potassium should be between 0-200 ppm';
    if ($ph < 0 || $ph > 14) $errors[] = 'pH should be between 0-14';
    if ($temperature < -10 || $temperature > 50) $errors[] = 'Temperature should be between -10°C to 50°C';
    if ($humidity < 0 || $humidity > 100) $errors[] = 'Humidity should be between 0-100%';
    if ($rainfall < 0) $errors[] = 'Rainfall cannot be negative';
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO soil_data (farmer_id, farm_id, nitrogen, phosphorus, potassium, ph, temperature, humidity, rainfall, test_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$farmer_id, $farm_id, $nitrogen, $phosphorus, $potassium, $ph, $temperature, $humidity, $rainfall, $test_date]);
            $message = 'Soil data added successfully!';
            $message_type = 'success';
            $selected_farm_id = $farm_id;
        } catch(PDOException $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'error';
    }
}

// Get all farms for this farmer
$stmt = $pdo->prepare("SELECT * FROM farms WHERE farmer_id = ? ORDER BY farm_name");
$stmt->execute([$farmer_id]);
$farms = $stmt->fetchAll();

// Get soil data history with pagination (filtered by farm if selected)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// ✅ FIXED: Build WHERE clause with proper table prefixes
$where_conditions = ["sd.farmer_id = ?"];
$params = [$farmer_id];

if ($selected_farm_id > 0) {
    $where_conditions[] = "sd.farm_id = ?";
    $params[] = $selected_farm_id;
}

$where_clause = implode(" AND ", $where_conditions);

// ✅ FIXED: Count total records with proper table alias
$count_sql = "SELECT COUNT(*) as total FROM soil_data sd WHERE $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetch()['total'];
$total_pages = ceil($total_records / $limit);

// ✅ FIXED: Get records with proper table aliases
$sql = "SELECT sd.*, f.farm_name 
        FROM soil_data sd 
        LEFT JOIN farms f ON sd.farm_id = f.id 
        WHERE $where_clause 
        ORDER BY sd.test_date DESC, sd.created_at DESC 
        LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$soil_data = $stmt->fetchAll();

// ✅ FIXED: Get latest soil data for summary
$sql = "SELECT sd.*, f.farm_name 
        FROM soil_data sd 
        LEFT JOIN farms f ON sd.farm_id = f.id 
        WHERE $where_clause 
        ORDER BY sd.test_date DESC LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$latest = $stmt->fetch();

// Get farm name for display
$farm_name = '';
if ($selected_farm_id > 0) {
    foreach ($farms as $farm) {
        if ($farm['id'] == $selected_farm_id) {
            $farm_name = $farm['farm_name'];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soil Data Management - KrishiMitra</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="farmer_dashboard.css">
    <link rel="stylesheet" href="soil_data.css">
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
        <a href="manage_farms.php"><i class="fas fa-tractor"></i> My Farms</a>
        <a class="active" href="soil_data.php"><i class="fas fa-seedling"></i> Soil Data</a>
        <a href="crop_recommendation.php"><i class="fas fa-tractor"></i> Crop Advisory</a>
        <a href="weather.php"><i class="fas fa-cloud-sun"></i> Weather</a>
        <a href="market_prices.php"><i class="fas fa-rupee-sign"></i> Market Prices</a>
        <a href="disease_detection.php"><i class="fas fa-bug"></i> Pest Detection</a>
        <a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a>
    </div>

    <!-- CONTENT -->
    <div class="content">
        <div class="welcome-box">
            <h2><i class="fas fa-seedling"></i> Soil Data Management</h2>
            <p>Add and track soil test results for your farms</p>
            <?php if ($farm_name): ?>
                <p style="color: #27ae60; font-weight: 500;">
                    <i class="fas fa-tractor"></i> Currently viewing: <?php echo htmlspecialchars($farm_name); ?>
                </p>
            <?php endif; ?>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Farm Filter -->
        <?php if (count($farms) > 0): ?>
        <div class="farm-filter">
            <form method="GET" action="">
                <div class="filter-group">
                    <label><i class="fas fa-tractor"></i> Select Farm:</label>
                    <select name="farm_id" onchange="this.form.submit()">
                        <option value="0">All Farms</option>
                        <?php foreach ($farms as $farm): ?>
                            <option value="<?php echo $farm['id']; ?>" <?php echo $selected_farm_id == $farm['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($farm['farm_name']); ?> (<?php echo $farm['location']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($selected_farm_id > 0): ?>
                        <a href="soil_data.php" class="btn-clear">Clear Filter</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Soil Summary -->
        <?php if ($latest): ?>
        <div class="soil-summary">
            <div class="stat-card">
                <div class="label">Latest Test</div>
                <div class="value"><?php echo date('d M Y', strtotime($latest['test_date'])); ?></div>
                <?php if (isset($latest['farm_name'])): ?>
                    <small><?php echo htmlspecialchars($latest['farm_name']); ?></small>
                <?php endif; ?>
            </div>
            <div class="stat-card">
                <div class="label">Nitrogen (N)</div>
                <div class="value"><?php echo $latest['nitrogen']; ?> ppm</div>
            </div>
            <div class="stat-card">
                <div class="label">Phosphorus (P)</div>
                <div class="value"><?php echo $latest['phosphorus']; ?> ppm</div>
            </div>
            <div class="stat-card">
                <div class="label">Potassium (K)</div>
                <div class="value"><?php echo $latest['potassium']; ?> ppm</div>
            </div>
            <div class="stat-card">
                <div class="label">Soil pH</div>
                <div class="value"><?php echo $latest['ph']; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Total Tests</div>
                <div class="value"><?php echo $total_records; ?></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Add Soil Data Form -->
        <div class="soil-form">
            <h3><i class="fas fa-plus-circle" style="color: #27ae60;"></i> Add New Soil Test</h3>
            
            <?php if (count($farms) == 0): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    You need to <a href="manage_farms.php" style="color: #27ae60; font-weight: 600;">add a farm</a> first before adding soil data.
                </div>
            <?php else: ?>
                <form method="POST" action="" id="soilForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Select Farm <span class="required">*</span></label>
                            <select name="farm_id" required>
                                <option value="">-- Select Farm --</option>
                                <?php foreach ($farms as $farm): ?>
                                    <option value="<?php echo $farm['id']; ?>" <?php echo ($selected_farm_id == $farm['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($farm['farm_name']); ?> (<?php echo $farm['location']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nitrogen (N) <span class="required">*</span></label>
                            <input type="number" name="nitrogen" step="0.01" min="0" max="200" placeholder="0-200 ppm" required>
                        </div>
                        <div class="form-group">
                            <label>Phosphorus (P) <span class="required">*</span></label>
                            <input type="number" name="phosphorus" step="0.01" min="0" max="150" placeholder="0-150 ppm" required>
                        </div>
                        <div class="form-group">
                            <label>Potassium (K) <span class="required">*</span></label>
                            <input type="number" name="potassium" step="0.01" min="0" max="200" placeholder="0-200 ppm" required>
                        </div>
                        <div class="form-group">
                            <label>Soil pH <span class="required">*</span></label>
                            <input type="number" name="ph" step="0.1" min="0" max="14" placeholder="0-14" required>
                        </div>
                        <div class="form-group">
                            <label>Temperature (°C) <span class="required">*</span></label>
                            <input type="number" name="temperature" step="0.1" min="-10" max="50" placeholder="-10 to 50°C" required>
                        </div>
                        <div class="form-group">
                            <label>Humidity (%) <span class="required">*</span></label>
                            <input type="number" name="humidity" step="0.1" min="0" max="100" placeholder="0-100%" required>
                        </div>
                        <div class="form-group">
                            <label>Rainfall (mm) <span class="required">*</span></label>
                            <input type="number" name="rainfall" step="0.01" min="0" placeholder="mm" required>
                        </div>
                        <div class="form-group">
                            <label>Test Date <span class="required">*</span></label>
                            <input type="date" name="test_date" required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="add_soil" class="btn-primary">
                            <i class="fas fa-save"></i> Save Soil Data
                        </button>
                        <button type="reset" class="btn-secondary">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <!-- Soil Data History -->
        <div class="soil-table">
            <h3><i class="fas fa-history" style="color: #27ae60;"></i> Soil Test History</h3>
            <?php if (count($soil_data) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Farm</th>
                                <th>Date</th>
                                <th>N (ppm)</th>
                                <th>P (ppm)</th>
                                <th>K (ppm)</th>
                                <th>pH</th>
                                <th>Temp (°C)</th>
                                <th>Rainfall (mm)</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = $offset + 1;
                            foreach ($soil_data as $data):
                                $status = 'healthy';
                                if ($data['nitrogen'] < 20 || $data['phosphorus'] < 15 || $data['potassium'] < 20) {
                                    $status = 'danger';
                                } elseif ($data['nitrogen'] < 40 || $data['phosphorus'] < 30 || $data['potassium'] < 40) {
                                    $status = 'warning';
                                }
                                $status_label = $status === 'healthy' ? 'Good' : ($status === 'warning' ? 'Needs Attention' : 'Deficient');
                            ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><?php echo htmlspecialchars($data['farm_name']); ?></td>
                                <td><?php echo date('d M Y', strtotime($data['test_date'])); ?></td>
                                <td><?php echo $data['nitrogen']; ?></td>
                                <td><?php echo $data['phosphorus']; ?></td>
                                <td><?php echo $data['potassium']; ?></td>
                                <td><?php echo $data['ph']; ?></td>
                                <td><?php echo $data['temperature']; ?></td>
                                <td><?php echo $data['rainfall']; ?></td>
                                <td><span class="status-badge <?php echo $status; ?>"><?php echo $status_label; ?></span></td>
                                <td>
                                    <a href="crop_recommendation.php?soil_id=<?php echo $data['id']; ?>" class="btn-sm btn-recommend">
                                        <i class="fas fa-tractor"></i> Recommend
                                    </a>
                                    <a href="#" onclick="confirmDelete(<?php echo $data['id']; ?>)" class="btn-sm btn-delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php 
                    $query_params = [];
                    if ($selected_farm_id > 0) $query_params['farm_id'] = $selected_farm_id;
                    $query_string = http_build_query($query_params);
                    ?>
                    
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&<?php echo $query_string; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&<?php echo $query_string; ?>" 
                           class="<?php echo $page == $i ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&<?php echo $query_string; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-seedling"></i>
                    <p>No soil data added yet. Add your first soil test above!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
// Confirm delete
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this soil data entry? This action cannot be undone.')) {
        window.location.href = '?delete=' + id;
    }
}

// Set today's date as default
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.querySelector('input[name="test_date"]');
    if (dateInput && !dateInput.value) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.value = today;
    }
});

// Real-time validation for form inputs
document.querySelectorAll('.soil-form input').forEach(input => {
    input.addEventListener('input', function() {
        if (this.value && !isNaN(this.value) && this.value >= 0) {
            this.classList.remove('invalid');
            this.classList.add('valid');
        } else if (this.value) {
            this.classList.remove('valid');
            this.classList.add('invalid');
        } else {
            this.classList.remove('valid', 'invalid');
        }
    });
});

// Form validation before submit
document.getElementById('soilForm')?.addEventListener('submit', function(e) {
    let hasError = false;
    const inputs = this.querySelectorAll('input[required]');
    const select = this.querySelector('select[required]');
    
    inputs.forEach(input => {
        if (!input.value || input.value.trim() === '') {
            input.classList.add('invalid');
            hasError = true;
        }
    });
    
    if (select && !select.value) {
        select.style.borderColor = '#e74c3c';
        hasError = true;
    }
    
    if (hasError) {
        e.preventDefault();
        alert('Please fill in all required fields with valid values.');
    }
});
</script>

</body>
</html>