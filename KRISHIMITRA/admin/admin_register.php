<?php
require_once '../config/database.php';

// If already logged in, redirect to dashboard
if (isLoggedIn('admin')) {
    redirect('admin_dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $passkey = sanitize($_POST['passkey']);
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($passkey)) {
        $error = 'All fields are required!';
    } elseif (!validateEmail($email)) {
        $error = 'Please enter a valid email address!';
    } elseif (!validatePassword($password)) {
        $error = 'Password must be at least 6 characters long!';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } elseif ($passkey !== ADMIN_PASSKEY) {
        $error = 'Invalid Admin Passkey! Please contact administrator.';
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $error = 'Email already registered!';
            } else {
                // Store password as plain text (as per your requirement)
                $stmt = $pdo->prepare("INSERT INTO admins (name, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email, $password]);
                $success = 'Admin Registration successful! Please login.';
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
    <title>Admin Registration - KrishiMitra</title>
    <link rel="stylesheet" href="admin_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="container">
    <img src="../pictures/images/logo1.png" alt="KrishiMitra Logo" class="form-logo">
    <h2>Admin Registration</h2>
    
    <div class="info-box">
        <i class="fas fa-info-circle"></i>
        <small>Contact administrator for the passkey</small>
    </div>

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
            <i class="fas fa-envelope"></i>
            <input type="email" id="email" name="email" placeholder="Email Address" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <i class="fas fa-lock"></i>
            <input type="password" id="password" name="password" placeholder="Password (min 6 characters)" required>
        </div>
        
        <div class="form-group">
            <i class="fas fa-check-circle"></i>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
        </div>
        
        <!-- 🔐 PASSKEY FIELD -->
        <div class="form-group" style="border: 2px solid #27ae60; border-radius: 8px; padding: 2px;">
            <i class="fas fa-key" style="color: #e67e22;"></i>
            <input type="password" id="passkey" name="passkey" placeholder="🔑 Admin Passkey" required>
        </div>

        <button type="submit">
            <i class="fas fa-user-shield"></i> Register as Admin
        </button>
    </form>

    <p>Already have an account? <a href="admin_login.php">Login Here</a></p>
    <p style="margin-top: 5px; font-size: 12px; color: #999;">
        <a href="../farmer/farmer_register.php">Register as Farmer instead</a>
    </p>
</div>

<script src="admin_script.js"></script>
</body>
</html>