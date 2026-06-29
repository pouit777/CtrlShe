<?php
// src/api/categories/update_categories.php
session_start();
header('Content-Type: application/json');

// Check active session bounds for admin status clearance
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

// Link DB 
require_once __DIR__ . '/../../config/db.php';

// JSON Payload recuperation
$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? intval($input['id']) : 0;
$label = isset($input['label']) ? trim($input['label']) : '';

// Combined condition validation parameters (ensure non-empty fields and positive ID constraints)
if ($id <= 0 || empty($label)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or incomplete input data.']);
    exit;
}

try {
    // Bind updates securely to isolate inputs safely away from the logical SQL framework execution
    $stmt = $pdo->prepare("UPDATE categories SET label = :label WHERE id = :id");
    $stmt->execute([
        'label' => $label,
        'id' => $id
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Category updated successfully.'
    ]);
} catch (\PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}