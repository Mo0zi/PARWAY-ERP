<?php
require_once __DIR__ . '/../../config/db.php';
require_login();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$q = trim($_GET['q'] ?? '');

if ($action === 'search_items') {
    $stmt = $pdo->prepare("SELECT id, item_name, default_price, unit FROM items WHERE item_name LIKE ? ORDER BY item_name ASC LIMIT 10");
    $stmt->execute(['%' . $q . '%']);
    echo json_encode($stmt->fetchAll());
    exit();
}

if ($action === 'search_customers') {
    $stmt = $pdo->prepare("SELECT id, name, phone, address FROM customers WHERE name LIKE ? OR phone LIKE ? ORDER BY name ASC LIMIT 10");
    $stmt->execute(['%' . $q . '%', '%' . $q . '%']);
    echo json_encode($stmt->fetchAll());
    exit();
}

if ($action === 'search_workers') {
    $stmt = $pdo->prepare("SELECT id, name, phone, default_rate FROM workers WHERE name LIKE ? OR phone LIKE ? ORDER BY name ASC LIMIT 10");
    $stmt->execute(['%' . $q . '%', '%' . $q . '%']);
    echo json_encode($stmt->fetchAll());
    exit();
}

echo json_encode([]);
exit();
