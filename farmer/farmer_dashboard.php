<?php
require_once '../config/database.php';

// Check if farmer is logged in
if (!isLoggedIn('farmer')) {
    redirect('farmer_login.php');
}

// Get farmer data
$farmer_id = $_SESSION['farmer_id'];
$stmt = $pdo->prepare("SELECT * FROM farmers WHERE id = ?");
$stmt->execute([$farmer_id]);
$farmer = $stmt->fetch();

// Get advisory count (placeholder)
$advisory_count = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Dashboard - KrishiMitra</title>
    <link rel="stylesheet" href="farmer_dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <div class="brand">
        <img src="../pictures/images/logo1.png" alt="KrishiMitra Logo" />
        <h2>KrishiMitra</h2>
    </div>

    <div class="nav-actions">
        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['farmer_name']); ?></span>
        <a href="#" onclick="openProfile()"><i class="fas fa-user-cog"></i> Profile</a>
        <a href="#"><i class="fas fa-question-circle"></i> Help</a>
        <a href="../logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- MAIN LAYOUT -->
<div class="layout">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <h3>Farmer Menu</h3>
        <a class="active" href="#"><i class="fas fa-home"></i> Dashboard</a>
        <a href="#"><i class="fas fa-seedling"></i> Soil Data</a>
        <a href="#"><i class="fas fa-tractor"></i> Crop Advisory</a>
        <a href="#"><i class="fas fa-cloud-sun"></i> Weather</a>
        <a href="#"><i class="fas fa-rupee-sign"></i> Market Prices</a>
        <a href="#"><i class="fas fa-bug"></i> Pest Detection</a>
        <a href="#"><i class="fas fa-file-alt"></i> Reports</a>
    </div>

    <!-- CONTENT -->
    <div class="content">

        <!-- Welcome Message -->
        <div class="welcome-box">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['farmer_name']); ?>! 🌾</h2>
            <p>Location: <?php echo htmlspecialchars($_SESSION['farmer_location']); ?></p>
        </div>

        <!-- KPI CARDS -->
        <div class="kpi-container">
            <div class="kpi-card">
                <i class="fas fa-leaf" style="font-size: 28px; color: #27ae60;"></i>
                <h3>Soil Health</h3>
                <p class="kpi">Good</p>
            </div>
            <div class="kpi-card">
                <i class="fas fa-cloud-sun" style="font-size: 28px; color: #27ae60;"></i>
                <h3>Weather</h3>
                <p class="kpi">28°C | Sunny</p>
            </div>
            <div class="kpi-card">
                <i class="fas fa-file-alt" style="font-size: 28px; color: #27ae60;"></i>
                <h3>Advisories</h3>
                <p class="kpi"><?php echo $advisory_count; ?> Generated</p>
            </div>
        </div>

        <!-- DASHBOARD CARDS -->
        <div class="dashboard-container">

            <div class="card" onclick="alert('Soil Data - Coming Soon!')">
                <i class="fas fa-seedling card-icon"></i>
                <h3>Soil Data</h3>
                <p>pH: 6.8 | Nitrogen: Medium</p>
            </div>

            <div class="card" onclick="alert('Crop Advisory - Coming Soon!')">
                <i class="fas fa-tractor card-icon"></i>
                <h3>Crop Advisory</h3>
                <p>Recommended: Wheat, Mustard</p>
            </div>

            <div class="card" onclick="alert('Weather Forecast - Coming Soon!')">
                <i class="fas fa-cloud-sun card-icon"></i>
                <h3>Weather Forecast</h3>
                <p>Rainfall expected in 3 days</p>
            </div>

            <div class="card" onclick="alert('Market Prices - Coming Soon!')">
                <i class="fas fa-rupee-sign card-icon"></i>
                <h3>Market Prices</h3>
                <p>Wheat: ₹2200 / quintal</p>
            </div>

            <div class="card" onclick="alert('Pest Detection - Coming Soon!')">
                <i class="fas fa-bug card-icon"></i>
                <h3>Pest Detection</h3>
                <p>No disease detected</p>
            </div>

            <div class="card" onclick="alert('Reports - Coming Soon!')">
                <i class="fas fa-file-alt card-icon"></i>
                <h3>Reports</h3>
                <p>Download advisory PDF</p>
            </div>

        </div>
    </div>
</div>

<!-- PROFILE MODAL -->
<div class="profile-modal" id="profileModal">
    <div class="profile-box">
        <h3><i class="fas fa-user-circle"></i> Update Profile</h3>
        <form method="POST" action="update_profile.php">
            <input type="text" name="name" placeholder="Full Name" value="<?php echo htmlspecialchars($_SESSION['farmer_name']); ?>">
            <input type="text" name="location" placeholder="Location" value="<?php echo htmlspecialchars($_SESSION['farmer_location']); ?>">
            <input type="tel" name="phone" placeholder="Phone" value="<?php echo htmlspecialchars($_SESSION['farmer_phone']); ?>">
            <button type="submit"><i class="fas fa-save"></i> Update</button>
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

// Simple logout function
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../logout.php';
    }
}
</script>

</body>
</html>