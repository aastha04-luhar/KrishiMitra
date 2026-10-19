<?php
require_once '../config/database.php';

// Check if farmer is logged in
if (!isLoggedIn('farmer')) {
    redirect('farmer_login.php');
}

// Get farmer ID
$farmer_id = $_SESSION['farmer_id'];

// Get farmer data from database
$stmt = $pdo->prepare("SELECT * FROM farmers WHERE id = ?");
$stmt->execute([$farmer_id]);
$farmer = $stmt->fetch();

// Safely get farmer information
$farmer_name = htmlspecialchars(
    $farmer['name'] ?? $_SESSION['farmer_name'] ?? 'Farmer'
);

$farmer_location = htmlspecialchars(
    $farmer['location'] ?? $_SESSION['farmer_location'] ?? 'Your Location'
);

$farmer_phone = htmlspecialchars(
    $farmer['phone'] ?? $_SESSION['farmer_phone'] ?? ''
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Farmer Dashboard | KrishiMitra</title>
    <link rel="stylesheet" href="farmer_common.css">
    <link rel="stylesheet" href="farmer_dashboard.css">


    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<!-- ================= NAVBAR ================= -->

<header class="navbar">

    <div class="brand">
        <img src="../pictures/images/logo1.png" alt="KrishiMitra Logo">
        <div>
            <h2>KrishiMitra</h2>
            <small>Smart Farming Assistant</small>
        </div>
    </div>

    <div class="nav-actions">

        <div class="farmer-user">
            <i class="fas fa-user-circle"></i>
            <span><?php echo $farmer_name; ?></span>
        </div>

        <a href="#" onclick="openProfile(); return false;">
            <i class="fas fa-user-cog"></i>
            <span>Profile</span>
        </a>

        <a href="#" onclick="showHelp(); return false;">
            <i class="fas fa-circle-question"></i>
            <span>Help</span>
        </a>

        <a href="../logout.php" class="logout-link">
            <i class="fas fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>

    </div>

</header>


<!-- ================= PAGE LAYOUT ================= -->

<div class="layout">

    <!-- ================= SIDEBAR ================= -->

    <aside class="sidebar">

        <div class="sidebar-heading">
            <span>FARMER PORTAL</span>
        </div>

        <nav>

            <a href="farmer_dashboard.php" class="active">
                <i class="fas fa-house"></i>
                <span>Dashboard</span>
            </a>

            <a href="manage_farms.php">
                <i class="fas fa-tractor"></i>
                <span>My Farms</span>
            </a>

            <a href="soil_data.php">
                <i class="fas fa-seedling"></i>
                <span>Soil Data</span>
            </a>

            <a href="weather.php">
                <i class="fas fa-cloud-sun"></i>
                <span>Weather</span>
            </a>

            <a href="market_prices.php">
                <i class="fas fa-indian-rupee-sign"></i>
                <span>Market Prices</span>
            </a>

            <a href="disease_detection.php">
                <i class="fas fa-bug"></i>
                <span>Pest Detection</span>
            </a>

            <a href="reports.php">
                <i class="fas fa-file-lines"></i>
                <span>Reports</span>
            </a>

        </nav>

    </aside>


    <!-- ================= MAIN CONTENT ================= -->

    <main class="content">

        <?php if (isset($_SESSION['profile_flash'])): ?>
            <div class="alert alert-<?php echo $_SESSION['profile_flash']['type'] === 'success' ? 'success' : 'error'; ?>" style="margin-bottom:18px;">
                <i class="fas fa-<?php echo $_SESSION['profile_flash']['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($_SESSION['profile_flash']['msg']); ?>
            </div>
            <?php unset($_SESSION['profile_flash']); ?>
        <?php endif; ?>

        <!-- ================= HERO SECTION ================= -->

        <section class="hero">

            <div class="hero-content">

                <div class="welcome-label">
                    <i class="fas fa-sun"></i>
                    Welcome to your farming assistant
                </div>

                <h1>
                    Hello, <?php echo $farmer_name; ?>!
                    <span>🌾</span>
                </h1>

                <p class="hero-description">
                    KrishiMitra brings essential farming information and
                    intelligent agricultural tools together in one place.
                    Use the tools below to understand your farm,
                    plan your crops and make informed farming decisions.
                </p>

                <div class="farmer-location">
                    <i class="fas fa-location-dot"></i>

                    <span>
                        <?php echo $farmer_location; ?>
                    </span>
                </div>

                <div class="hero-buttons">

                    <a href="manage_farms.php" class="primary-btn">
                        <i class="fas fa-tractor"></i>
                        Manage My Farm
                    </a>

                    <a href="crop_recommendation.php" class="secondary-btn">
                        Explore Crop Advisory
                        <i class="fas fa-arrow-right"></i>
                    </a>

                </div>

            </div>

            <div class="hero-illustration">

                <div class="hero-circle"></div>

                <div class="hero-icon icon-one">
                    <i class="fas fa-seedling"></i>
                </div>

                <div class="hero-icon icon-two">
                    <i class="fas fa-cloud-sun"></i>
                </div>

                <div class="hero-icon icon-three">
                    <i class="fas fa-tractor"></i>
                </div>

                <div class="main-farm-icon">
                    <i class="fas fa-wheat-awn"></i>
                </div>

            </div>

        </section>


        <!-- ================= SECTION TITLE ================= -->

        <section class="section-heading">

            <div>
                <span class="section-label">
                    FARMING TOOLS
                </span>

                <h2>
                    Everything you need for smarter farming
                </h2>

                <p>
                    Access the tools and information designed to support
                    your day-to-day farming decisions.
                </p>
            </div>

        </section>


        <!-- ================= SERVICES ================= -->

        <section class="services-grid">

            <!-- MY FARMS -->

            <a href="manage_farms.php" class="service-card">

                <div class="service-icon farm-icon">
                    <i class="fas fa-tractor"></i>
                </div>

                <div class="service-content">

                    <h3>My Farms</h3>

                    <p>
                        Add and manage your farm information,
                        land details and farming activities.
                    </p>

                    <span class="service-link">
                        Manage farms
                        <i class="fas fa-arrow-right"></i>
                    </span>

                </div>

            </a>


            <!-- SOIL -->

            <a href="soil_data.php" class="service-card">

                <div class="service-icon soil-icon">
                    <i class="fas fa-seedling"></i>
                </div>

                <div class="service-content">

                    <h3>Soil Data</h3>

                    <p>
                        Record and review soil test information
                        to better understand your soil condition.
                    </p>

                    <span class="service-link">
                        View soil data
                        <i class="fas fa-arrow-right"></i>
                    </span>

                </div>

            </a>


            <!-- CROP ADVISORY -->

            <a href="crop_recommendation.php" class="service-card">

                <div class="service-icon crop-icon">
                    <i class="fas fa-leaf"></i>
                </div>

                <div class="service-content">

                    <h3>Crop Advisory</h3>

                    <p>
                        Get crop-related guidance based on
                        your available farm and soil information.
                    </p>

                    <span class="service-link">
                        Explore advisory
                        <i class="fas fa-arrow-right"></i>
                    </span>

                </div>

            </a>


            <!-- WEATHER -->

            <a href="weather.php" class="service-card">

                <div class="service-icon weather-icon">
                    <i class="fas fa-cloud-sun"></i>
                </div>

                <div class="service-content">

                    <h3>Weather</h3>

                    <p>
                        Check weather information to help plan
                        farming activities and field operations.
                    </p>

                    <span class="service-link">
                        Check weather
                        <i class="fas fa-arrow-right"></i>
                    </span>

                </div>

            </a>


            <!-- MARKET -->

            <a href="market_prices.php" class="service-card">

                <div class="service-icon market-icon">
                    <i class="fas fa-indian-rupee-sign"></i>
                </div>

                <div class="service-content">

                    <h3>Market Prices</h3>

                    <p>
                        View agricultural market information
                        and make better selling decisions.
                    </p>

                    <span class="service-link">
                        View market
                        <i class="fas fa-arrow-right"></i>
                    </span>

                </div>

            </a>


            <!-- PEST -->

            <a href="disease_detection.php" class="service-card">

                <div class="service-icon pest-icon">
                    <i class="fas fa-bug"></i>
                </div>

                <div class="service-content">

                    <h3>Pest Detection</h3>

                    <p>
                        Identify possible crop diseases and
                        pest-related problems using the available tool.
                    </p>

                    <span class="service-link">
                        Detect problems
                        <i class="fas fa-arrow-right"></i>
                    </span>

                </div>

            </a>

        </section>


        <!-- ================= HOW KRISHIMITRA HELPS ================= -->

        <section class="about-section">

            <div class="about-text">

                <span class="section-label">
                    WHY KRISHIMITRA?
                </span>

                <h2>
                    Make better farming decisions with the right information
                </h2>

                <p>
                    Farming decisions often depend on several factors such as
                    soil condition, weather, crop suitability, pest problems
                    and market conditions.
                </p>

                <p>
                    KrishiMitra brings these important resources together
                    so that you can access them from a single platform.
                </p>

            </div>


            <div class="benefits">

                <div class="benefit">

                    <div class="benefit-icon">
                        <i class="fas fa-database"></i>
                    </div>

                    <div>
                        <h4>Centralized Information</h4>
                        <p>
                            Keep your farm-related information organized
                            in one place.
                        </p>
                    </div>

                </div>


                <div class="benefit">

                    <div class="benefit-icon">
                        <i class="fas fa-brain"></i>
                    </div>

                    <div>
                        <h4>Data-Based Guidance</h4>
                        <p>
                            Use available farm and soil information
                            to support your decisions.
                        </p>
                    </div>

                </div>


                <div class="benefit">

                    <div class="benefit-icon">
                        <i class="fas fa-clock"></i>
                    </div>

                    <div>
                        <h4>Easy Access</h4>
                        <p>
                            Access important farming tools from
                            a single dashboard.
                        </p>
                    </div>

                </div>


                <div class="benefit">

                    <div class="benefit-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>

                    <div>
                        <h4>Better Planning</h4>
                        <p>
                            Use relevant information to plan
                            farming activities more effectively.
                        </p>
                    </div>

                </div>

            </div>

        </section>


        <!-- ================= QUICK START ================= -->

        <section class="quick-start">

            <div>

                <span class="section-label">
                    GET STARTED
                </span>

                <h2>
                    Start by setting up your farm
                </h2>

                <p>
                    Add your farm information first. Once your information
                    is available, you can use the different KrishiMitra
                    tools according to your farming needs.
                </p>

            </div>

            <a href="manage_farms.php" class="quick-start-btn">

                Set Up My Farm

                <i class="fas fa-arrow-right"></i>

            </a>

        </section>

    </main>

</div>


<!-- ================= PROFILE MODAL ================= -->

<div class="profile-modal" id="profileModal">

    <div class="profile-box">

        <button class="modal-close" onclick="closeProfile()">
            <i class="fas fa-times"></i>
        </button>

        <div class="profile-header">

            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>

            <div>
                <h3>Update Profile</h3>
                <p>Keep your information up to date</p>
            </div>

        </div>

        <form method="POST" action="update_profile.php">

            <label>
                Full Name
            </label>

            <div class="input-group">
                <i class="fas fa-user"></i>

                <input
                    type="text"
                    name="name"
                    value="<?php echo $farmer_name; ?>"
                    placeholder="Full Name"
                    required>
            </div>


            <label>
                Location
            </label>

            <div class="input-group">
                <i class="fas fa-location-dot"></i>

                <input
                    type="text"
                    name="location"
                    value="<?php echo $farmer_location; ?>"
                    placeholder="Location"
                    required>
            </div>


            <label>
                Phone
            </label>

            <div class="input-group">
                <i class="fas fa-phone"></i>

                <input
                    type="tel"
                    name="phone"
                    value="<?php echo $farmer_phone; ?>"
                    placeholder="Phone Number">
            </div>


            <button type="submit" class="update-btn">

                <i class="fas fa-save"></i>

                Update Profile

            </button>

        </form>

    </div>

</div>


<!-- ================= HELP MODAL ================= -->

<div class="help-modal" id="helpModal">

    <div class="help-box">

        <button class="modal-close" onclick="closeHelp()">
            <i class="fas fa-times"></i>
        </button>

        <div class="help-main-icon">
            <i class="fas fa-circle-question"></i>
        </div>

        <h3>How can KrishiMitra help?</h3>

        <p>
            Use the navigation menu to access farm management,
            soil information, crop advisory, weather, market prices,
            pest detection and reports.
        </p>

        <button onclick="closeHelp()" class="update-btn">
            Got it
        </button>

    </div>

</div>


<script>

function openProfile() {
    document.getElementById('profileModal').style.display = 'flex';
}

function closeProfile() {
    document.getElementById('profileModal').style.display = 'none';
}

function showHelp() {
    document.getElementById('helpModal').style.display = 'flex';
}

function closeHelp() {
    document.getElementById('helpModal').style.display = 'none';
}


// Close modal by clicking outside

window.addEventListener('click', function(event) {

    const profileModal = document.getElementById('profileModal');
    const helpModal = document.getElementById('helpModal');

    if (event.target === profileModal) {
        closeProfile();
    }

    if (event.target === helpModal) {
        closeHelp();
    }

});


// Keyboard ESC support

document.addEventListener('keydown', function(event) {

    if (event.key === 'Escape') {
        closeProfile();
        closeHelp();
    }

});

</script>

</body>
</html>