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

// JSON Payload recuperation
$input = json_decode(file_get_contents('php://input'), true);
$label = isset($input['label']) ? trim($input['label']) : '';

// Validation
if (empty($label)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'The category label is required.'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO categories (label) VALUES (:label)");
    $stmt->execute(['label' => $label]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Category successfully created!',
        'id' => $pdo->lastInsertId()
    ]);
} catch (\PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}