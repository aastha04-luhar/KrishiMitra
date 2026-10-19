<?php
require_once '../config/database.php';

// Only logged-in admins
if (!isLoggedIn('admin')) {
    redirect('admin_login.php');
}

$admin_id = $_SESSION['admin_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);

    if (empty($name) || empty($email)) {
        $message = 'All fields are required!';
    } elseif (!validateEmail($email)) {
        $message = 'Please enter a valid email address!';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
            $stmt->execute([$email, $admin_id]);
            if ($stmt->rowCount() > 0) {
                $message = 'Email already used by another admin!';
            } else {
                $stmt = $pdo->prepare("UPDATE admins SET name = ?, email = ? WHERE id = ?");
                $stmt->execute([$name, $email, $admin_id]);

                $_SESSION['admin_name'] = $name;
                $_SESSION['admin_email'] = $email;

                $message = 'Profile updated successfully!';
            }
        } catch (PDOException $e) {
            $message = 'Update failed: ' . $e->getMessage();
        }
    }
}

$_SESSION['profile_flash'] = $message;
redirect('admin_dashboard.php');
