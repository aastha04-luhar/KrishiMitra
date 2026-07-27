<?php
require_once '../config/database.php';

// If already logged in, redirect to dashboard
if (isLoggedIn('farmer')) {
    redirect('farmer_dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    
    if (empty($phone) || empty($password)) {
        $error = 'Please fill in all fields!';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM farmers WHERE phone = ?");
            $stmt->execute([$phone]);
            $farmer = $stmt->fetch();
            
            // 🔥 DIRECT PLAIN TEXT PASSWORD COMPARISON (NO HASH)
            if ($farmer && $password === $farmer['password']) {
                $_SESSION['farmer_id'] = $farmer['id'];
                $_SESSION['farmer_name'] = $farmer['name'];
                $_SESSION['farmer_location'] = $farmer['location'];
                $_SESSION['farmer_phone'] = $farmer['phone'];
                redirect('farmer_dashboard.php');
            } else {
                $error = 'Invalid phone number or password!';
            }
        } catch(PDOException $e) {
            $error = 'Login failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Login - KrishiMitra</title>
    <link rel="stylesheet" href="farmer_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="container">
    <img src="../pictures/images/logo1.png" alt="KrishiMitra Logo" class="form-logo">
    <h2>Farmer Login</h2>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <i class="fas fa-phone"></i>
            <input type="tel" id="phone" name="phone" placeholder="Phone Number" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <i class="fas fa-lock"></i>
            <input type="password" id="password" name="password" placeholder="Password" required>
        </div>

        <button type="submit">Login</button>
    </form>

    <p>Don't have an account? <a href="farmer_register.php">Register Here</a></p>
</div>

<script src="farmer_script.js"></script>
</body>
</html>