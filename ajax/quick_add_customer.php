<?php
// ajax/quick_add_customer.php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$gid = $_SESSION['garage_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Name is required']);
        exit;
    }

    // Default password for customers is their phone or 'customer123'
    $password = password_hash($phone ?: 'customer123', PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (garage_id, name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, 'Customer', 1)");
        $stmt->execute([$gid, $name, $email, $phone, $password]);
        $newId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true, 
            'customer' => [
                'id' => $newId, 
                'name' => $name
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
