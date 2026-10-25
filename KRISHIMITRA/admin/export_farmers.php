<?php
require_once '../config/database.php';

// Only logged-in admins
if (!isLoggedIn('admin')) {
    redirect('admin_login.php');
}

// Fetch all farmers
$stmt = $pdo->query("SELECT id, name, location, phone, status, approved, district, created_at FROM farmers ORDER BY created_at DESC");
$farmers = $stmt->fetchAll();

// Output as CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="farmers_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// CSV header
fputcsv($output, ['ID', 'Name', 'Location', 'Phone', 'Status', 'Approved', 'District', 'Registered']);

foreach ($farmers as $farmer) {
    fputcsv($output, [
        $farmer['id'],
        $farmer['name'],
        $farmer['location'],
        $farmer['phone'],
        $farmer['status'],
        $farmer['approved'],
        $farmer['district'],
        $farmer['created_at'],
    ]);
}

fclose($output);
exit();
