<?php
require_once '../config/database.php';

// Only logged-in farmers
if (!isLoggedIn('farmer')) {
    redirect('farmer_login.php');
}

$farmer_id = $_SESSION['farmer_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $location = sanitize($_POST['location'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');

    // Validate
    if (empty($name) || empty($location) || empty($phone)) {
        $error = 'All fields are required!';
    } elseif (strlen($name) < 2) {
        $error = 'Name must be at least 2 characters!';
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
        $error = 'Name should only contain letters and spaces!';
    } elseif (!validatePhone($phone)) {
        $error = 'Please enter a valid 10-digit phone number!';
    } else {
        try {
            // Check the phone is not used by another farmer
            $stmt = $pdo->prepare("SELECT id FROM farmers WHERE phone = ? AND id != ?");
            $stmt->execute([$phone, $farmer_id]);
            if ($stmt->rowCount() > 0) {
                $error = 'Phone number already registered with another account!';
            } else {
                $stmt = $pdo->prepare("UPDATE farmers SET name = ?, location = ?, phone = ? WHERE id = ?");
                $stmt->execute([$name, $location, $phone, $farmer_id]);

                // Update the session values too
                $_SESSION['farmer_name'] = $name;
                $_SESSION['farmer_location'] = $location;
                $_SESSION['farmer_phone'] = $phone;

                $success = 'Profile updated successfully!';
            }
        } catch (PDOException $e) {
            $error = 'Update failed: ' . $e->getMessage();
        }
    }
}

// Redirect back with a flash message stored in session
if ($success) {
    $_SESSION['profile_flash'] = ['type' => 'success', 'msg' => $success];
} elseif ($error) {
    $_SESSION['profile_flash'] = ['type' => 'error', 'msg' => $error];
}

redirect('farmer_dashboard.php');
