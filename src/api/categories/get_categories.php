<?php
// src/api/categories/get_categories.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 

require_once __DIR__ . '/../../config/db.php';

try {
    // Get all categories sorted by alphabetical order
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY label ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $categories
    ]);
} catch (\PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}