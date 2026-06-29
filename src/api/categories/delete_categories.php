<?php
// src/api/categories/delete_category.php
session_start();
header('Content-Type: application/json');

// Protection : Only for admin persons
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

// Link DB
require_once __DIR__ . '/../../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? intval($input['id']) : 0;

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid category ID.']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
    $success = $stmt->execute(['id' => $id]);

    /**
     * COMPLEX LOGIC EXPLANATION:
     * $stmt->rowCount() explicitly checks how many rows were modified/removed by the query.
     * This distinguishes between a successful query statement and a case where the record ID simply does not exist.
     */
    if ($success && $stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Category deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Category not found or already deleted.']);
    }
} catch (\PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}