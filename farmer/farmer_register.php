<?php
require_once '../config/database.php';

// If already logged in, redirect to dashboard
if (isLoggedIn('farmer')) {
    redirect('farmer_dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $location = sanitize($_POST['location']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($name) || empty($location) || empty($phone) || empty($password)) {
        $error = 'All fields are required!';
    } elseif (strlen($name) < 2) {
        $error = 'Name must be at least 2 characters!';
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
        $error = 'Name should only contain letters and spaces!';
    } elseif (!validatePhone($phone)) {
        $error = 'Please enter a valid 10-digit phone number!';
    } elseif (!validatePassword($password)) {
        $error = 'Password must be at least 6 characters long!';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } else {
        try {
            // Check if phone already exists
            $stmt = $pdo->prepare("SELECT id FROM farmers WHERE phone = ?");
            $stmt->execute([$phone]);
            if ($stmt->rowCount() > 0) {
                $error = 'Phone number already registered!';
            } else {
                // 🔥 STORE PASSWORD AS PLAIN TEXT (NO HASHING)
                $stmt = $pdo->prepare("INSERT INTO farmers (name, location, phone, password) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $location, $phone, $password]);
                $success = 'Registration successful! Please login.';
            }
        } catch(PDOException $e) {
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Registration - KrishiMitra</title>
    <link rel="stylesheet" href="farmer_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="container">
    <img src="../pictures/images/logo1.png" alt="KrishiMitra Logo" class="form-logo">
    <h2>Farmer Registration</h2>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <i class="fas fa-user"></i>
            <input type="text" id="name" name="name" placeholder="Full Name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <i class="fas fa-map-marker-alt"></i>
            <input type="text" id="location" name="location" placeholder="Location (City/District)" value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <i class="fas fa-phone"></i>
            <input type="tel" id="phone" name="phone" placeholder="Phone Number (10 digits)" maxlength="10" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <i class="fas fa-lock"></i>
            <input type="password" id="password" name="password" placeholder="Password (min 6 characters)" required>
        </div>
        
        <div class="form-group">
            <i class="fas fa-check-circle"></i>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
        </div>

        <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="farmer_login.php">Login Here</a></p>
</div>

<script src="farmer_script.js"></script>
</body>
</html>