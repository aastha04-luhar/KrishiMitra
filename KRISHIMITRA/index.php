<?php
session_start();

// If logged in, redirect to appropriate dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: admin/admin_dashboard.php");
    exit();
} elseif (isset($_SESSION['farmer_id'])) {
    header("Location: farmer/farmer_dashboard.php");
    exit();
}

// Otherwise, show landing page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KrishiMitra - Smart Crop Advisory</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #eafaf1 0%, #d5f5e3 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .landing {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            max-width: 600px;
        }
        .landing img {
            height: 120px;
            margin-bottom: 20px;
        }
        .landing h1 {
            color: #1e8449;
            font-size: 36px;
            margin-bottom: 10px;
        }
        .landing p {
            color: #555;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-admin {
            background: #1e8449;
            color: white;
        }
        .btn-admin:hover {
            background: #145a32;
            transform: translateY(-2px);
        }
        .btn-farmer {
            background: #27ae60;
            color: white;
        }
        .btn-farmer:hover {
            background: #1e8449;
            transform: translateY(-2px);
        }
        .btn-outline {
            border: 2px solid #27ae60;
            color: #27ae60;
            background: transparent;
        }
        .btn-outline:hover {
            background: #27ae60;
            color: white;
        }
        .features {
            margin-top: 30px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        .feature {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .feature i {
            font-size: 24px;
            color: #27ae60;
            margin-bottom: 8px;
        }
        .feature p {
            font-size: 12px;
            margin: 0;
        }
        @media (max-width: 600px) {
            .landing { margin: 20px; padding: 20px; }
            .features { grid-template-columns: 1fr; }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="landing">
        <img src="pictures/images/logo1.png" alt="KrishiMitra Logo">
        <h1>🌾 KrishiMitra</h1>
        <p>Smart Crop Advisory System for Small & Marginal Farmers</p>
        
        <div class="btn-group">
            <a href="admin/admin_login.php" class="btn btn-admin"><i class="fas fa-user-shield"></i> Admin</a>
            <a href="farmer/farmer_login.php" class="btn btn-farmer"><i class="fas fa-user"></i> Farmer</a>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="admin/admin_register.php" class="btn btn-outline">Register as Admin</a>
            <a href="farmer/farmer_register.php" class="btn btn-outline">Register as Farmer</a>
        </div>
        
        <div class="features">
            <div class="feature">
                <i class="fas fa-seedling"></i>
                <p>Crop Advisory</p>
            </div>
            <div class="feature">
                <i class="fas fa-cloud-sun"></i>
                <p>Weather Updates</p>
            </div>
            <div class="feature">
                <i class="fas fa-rupee-sign"></i>
                <p>Market Prices</p>
            </div>
        </div>
    </div>
</body>
</html>