<?php
// ajax/quick_add_supplier.php
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
    $contact = trim($_POST['contact_person'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Supplier name is required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO suppliers (garage_id, name, contact_person, phone) VALUES (?, ?, ?, ?)");
        $stmt->execute([$gid, $name, $contact, $phone]);
        $newId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true, 
            'supplier' => [
                'id' => $newId, 
                'name' => $name
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
