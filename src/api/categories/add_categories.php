<?php
// src/api/categories/add_category.php
session_start();
header('Content-Type: application/json');

// Protection : Only for admin persons
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access. Admin role required.'
    ]);
    exit;
}

// Link DB 
require_once __DIR__ . '/../../config/db.php';

// Capture and process structural payload data from incoming JSON stream
$input = json_decode(file_get_contents('php://input'), true);
$label = isset($input['label']) ? trim($input['label']) : '';

// Structural emptiness restriction rule validation
if (empty($label)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'The category label is required.'
    ]);
    exit;
}

try {
    // Utilize named parameters (:label) to safely run prepared parameterized updates against SQL injections
    $stmt = $pdo->prepare("INSERT INTO categories (label) VALUES (:label)");
    $stmt->execute(['label' => $label]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Category successfully created!',
        'id' => $pdo->lastInsertId() // Fetch auto-incremented record identifier from current connection context
    ]);
} catch (\PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}