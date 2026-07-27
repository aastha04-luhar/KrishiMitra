<?php
require_once '../config/database.php';

// Check if admin is logged in
if (!isLoggedIn('admin')) {
    redirect('admin_login.php');
}

$message = '';
$message_type = '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

// Update farmer status (approve/block)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    try {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE farmers SET approved = 1, status = 'active' WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Farmer approved successfully!';
            $message_type = 'success';
        } elseif ($action === 'block') {
            $stmt = $pdo->prepare("UPDATE farmers SET status = 'inactive', approved = 0 WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Farmer blocked successfully!';
            $message_type = 'success';
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM farmers WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Farmer deleted successfully!';
            $message_type = 'success';
        }
    } catch(PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Get farmers with pagination and filters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build WHERE clause for filters
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(name LIKE ? OR location LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Count total records
$count_sql = "SELECT COUNT(*) as total FROM farmers $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetch()['total'];
$total_pages = ceil($total_records / $limit);

// Get farmers
$sql = "SELECT * FROM farmers $where_clause ORDER BY created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$farmers = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM farmers");
$total_farmers = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM farmers WHERE status = 'active'");
$active_farmers = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM farmers WHERE status = 'inactive'");
$inactive_farmers = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM farmers WHERE approved = 0 AND status = 'inactive'");
$pending_approval = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Management - KrishiMitra</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="stylesheet" href="manage_farmers.css">
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
    <div class="nav-links">
        <span><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
        <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="layout">
    <!-- ADMIN SIDEBAR -->
    <div class="sidebar">
        <h3>Admin Controls</h3>
        <a href="admin_dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a>
        <a class="active" href="manage_farmers.php"><i class="fas fa-users"></i> Farmer Management</a>
        <a href="#"><i class="fas fa-seedling"></i> Crop Database</a>
        <a href="#"><i class="fas fa-cloud-sun"></i> Weather System</a>
        <a href="#"><i class="fas fa-rupee-sign"></i> Market Rates</a>
        <a href="#"><i class="fas fa-bug"></i> Pest & Disease Data</a>
        <a href="#"><i class="fas fa-bell"></i> Alerts & Notifications</a>
        <a href="#"><i class="fas fa-file-alt"></i> Reports & Analytics</a>
        <a href="#"><i class="fas fa-database"></i> System Logs</a>
    </div>

    <!-- CONTENT -->
    <div class="content">
        <div class="welcome-box">
            <h2><i class="fas fa-users"></i> Farmer Management</h2>
            <p>Manage all registered farmers on the platform</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-box">
                <i class="fas fa-users" style="color: #3498db;"></i>
                <div class="stat-info">
                    <h3>Total Farmers</h3>
                    <p><?php echo $total_farmers; ?></p>
                </div>
            </div>
            <div class="stat-box">
                <i class="fas fa-check-circle" style="color: #27ae60;"></i>
                <div class="stat-info">
                    <h3>Active</h3>
                    <p><?php echo $active_farmers; ?></p>
                </div>
            </div>
            <div class="stat-box">
                <i class="fas fa-times-circle" style="color: #e74c3c;"></i>
                <div class="stat-info">
                    <h3>Inactive</h3>
                    <p><?php echo $inactive_farmers; ?></p>
                </div>
            </div>
            <div class="stat-box">
                <i class="fas fa-clock" style="color: #f39c12;"></i>
                <div class="stat-info">
                    <h3>Pending Approval</h3>
                    <p><?php echo $pending_approval; ?></p>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <div class="filter-group">
                    <input type="text" name="search" placeholder="Search by name, location, phone..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="manage_farmers.php" class="btn-reset">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Farmers Table -->
        <div class="farmers-table">
            <div class="table-header">
                <h3><i class="fas fa-list"></i> Registered Farmers</h3>
                <span class="record-count"><?php echo $total_records; ?> records found</span>
            </div>

            <?php if (count($farmers) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Location</th>
                                <th>Phone</th>
                                <th>Registered</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = $offset + 1;
                            foreach ($farmers as $farmer): 
                                $status_class = $farmer['status'] === 'active' ? 'active' : 'inactive';
                                $status_label = $farmer['status'] === 'active' ? 'Active' : 'Inactive';
                            ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($farmer['name']); ?></strong>
                                    <?php if (!$farmer['approved'] && $farmer['status'] === 'inactive'): ?>
                                        <span class="badge badge-pending">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($farmer['location']); ?></td>
                                <td><?php echo htmlspecialchars($farmer['phone']); ?></td>
                                <td><?php echo date('d M Y', strtotime($farmer['created_at'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $status_label; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($farmer['status'] === 'inactive' && !$farmer['approved']): ?>
                                            <a href="?action=approve&id=<?php echo $farmer['id']; ?>" 
                                               class="btn-action approve" 
                                               onclick="return confirmAction('approve', '<?php echo htmlspecialchars($farmer['name']); ?>')">
                                                <i class="fas fa-check"></i> Approve
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($farmer['status'] === 'active'): ?>
                                            <a href="?action=block&id=<?php echo $farmer['id']; ?>" 
                                               class="btn-action block" 
                                               onclick="return confirmAction('block', '<?php echo htmlspecialchars($farmer['name']); ?>')">
                                                <i class="fas fa-ban"></i> Block
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($farmer['status'] === 'inactive'): ?>
                                            <a href="?action=approve&id=<?php echo $farmer['id']; ?>" 
                                               class="btn-action approve" 
                                               onclick="return confirmAction('approve', '<?php echo htmlspecialchars($farmer['name']); ?>')">
                                                <i class="fas fa-check"></i> Activate
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="?action=delete&id=<?php echo $farmer['id']; ?>" 
                                           class="btn-action delete" 
                                           onclick="return confirmAction('delete', '<?php echo htmlspecialchars($farmer['name']); ?>')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                        
                                        <a href="farmer_details.php?id=<?php echo $farmer['id']; ?>" 
                                           class="btn-action view">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </div>
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
                    if ($search) $query_params['search'] = $search;
                    if ($status_filter) $query_params['status'] = $status_filter;
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
                    <i class="fas fa-users"></i>
                    <p>No farmers found matching your criteria.</p>
                    <?php if ($search || $status_filter): ?>
                        <a href="manage_farmers.php" class="btn-primary">Clear Filters</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="manage_farmers.js"></script>
</body>
</html>