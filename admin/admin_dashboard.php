<?php
require_once '../config/database.php';

// Check if admin is logged in
if (!isLoggedIn('admin')) {
    redirect('admin_login.php');
}

// Get total farmers count
$stmt = $pdo->query("SELECT COUNT(*) as total FROM farmers");
$total_farmers = $stmt->fetch()['total'];

// Get recent farmers (last 5)
$stmt = $pdo->query("SELECT * FROM farmers ORDER BY created_at DESC LIMIT 5");
$recent_farmers = $stmt->fetchAll();

// Get total admins count
$stmt = $pdo->query("SELECT COUNT(*) as total FROM admins");
$total_admins = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - KrishiMitra</title>
    <!-- ✅ CORRECT CSS PATH -->
    <link rel="stylesheet" href="admin_dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <div class="brand">
        <!-- ✅ CORRECT IMAGE PATH -->
        <img src="../assets/images/logo1.png" alt="KrishiMitra Logo" onerror="this.src='../pictures/images/logo1.png'">
        <h2>KrishiMitra</h2>
    </div>

    <div class="nav-links">
        <span><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
        <a href="#" onclick="openProfile()"><i class="fas fa-user-cog"></i> Profile</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<!-- MAIN LAYOUT -->
<div class="layout">

    <!-- ADMIN SIDEBAR -->
    <div class="sidebar">
        <h3>Admin Controls</h3>
        <a class="active" href="#"><i class="fas fa-chart-pie"></i> Dashboard</a>
        <a href="#"><i class="fas fa-users"></i> Farmer Management</a>
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

        <!-- Welcome Message -->
        <div class="welcome-box">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>! 🌾</h2>
            <p>Here's an overview of your KrishiMitra platform</p>
        </div>

        <!-- KPI -->
        <div class="admin-kpi">
            <div class="box">
                <i class="fas fa-users" style="font-size: 32px; color: #27ae60;"></i>
                <h3>Total Farmers</h3>
                <p><?php echo $total_farmers; ?></p>
            </div>
            <div class="box">
                <i class="fas fa-user-shield" style="font-size: 32px; color: #27ae60;"></i>
                <h3>Total Admins</h3>
                <p><?php echo $total_admins; ?></p>
            </div>
            <div class="box">
                <i class="fas fa-seedling" style="font-size: 32px; color: #27ae60;"></i>
                <h3>Active Advisories</h3>
                <p>0</p>
            </div>
            <div class="box">
                <i class="fas fa-check-circle" style="font-size: 32px; color: #27ae60;"></i>
                <h3>System Status</h3>
                <p class="status">Operational</p>
            </div>
        </div>

        <!-- Recent Farmers -->
        <div class="recent-farmers">
            <h3><i class="fas fa-users"></i> Recently Registered Farmers</h3>
            <?php if (count($recent_farmers) > 0): ?>
                <table class="farmer-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Phone</th>
                            <th>Registered On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($recent_farmers as $farmer): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($farmer['name']); ?></td>
                            <td><?php echo htmlspecialchars($farmer['location']); ?></td>
                            <td><?php echo htmlspecialchars($farmer['phone']); ?></td>
                            <td><?php echo date('d M Y', strtotime($farmer['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">No farmers registered yet.</p>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- PROFILE MODAL -->
<div class="profile-modal" id="profileModal">
    <div class="profile-box">
        <h3><i class="fas fa-user-cog"></i> Admin Profile</h3>
        <form method="POST" action="update_profile.php">
            <input type="text" name="name" placeholder="Full Name" value="<?php echo htmlspecialchars($_SESSION['admin_name']); ?>">
            <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($_SESSION['admin_email']); ?>">
            <button type="submit"><i class="fas fa-save"></i> Update Profile</button>
            <button type="button" onclick="closeProfile()" class="close-btn"><i class="fas fa-times"></i> Close</button>
        </form>
    </div>
</div>

<script>
// Profile Modal Functions
function openProfile() {
    document.getElementById('profileModal').style.display = 'flex';
}

function closeProfile() {
    document.getElementById('profileModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    let modal = document.getElementById('profileModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

</body>
</html>