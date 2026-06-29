<?php
// src/api/categories/bind_questions.php
session_start();
header('Content-Type: application/json');

// Enforce strict Admin role validation checkpoint
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

// Extract and decode the incoming raw HTTP input stream as an associative array
$data = json_decode(file_get_contents('php://input'), true);

$category_id = isset($data['category_id']) ? (int)$data['category_id'] : 0;
$question_ids = isset($data['question_ids']) ? $data['question_ids'] : [];

// Input sanitization boundary check
if ($category_id <= 0 || empty($question_ids)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing or invalid data.']);
    exit;
}

try {
    /**
     * COMPLEX LOGIC EXPLANATION:
     * 1. array_fill(0, count(...), '?') creates an array filled with '?' matching the number of targeted questions.
     * 2. implode(',', ...) converts that array into a flat string sequence (e.g., "?,?,?").
     * This safely allows a variable dynamic range inside the SQL "IN (...)" clause without compromising security.
     */
    $placeholders = implode(',', array_fill(0, count($question_ids), '?'));
    
    // Construct the query injecting the safely decoupled placeholder list dynamically
    $query = "UPDATE questions SET category_id = ? WHERE id IN ($placeholders)";
    $stmt = $pdo->prepare($query);
    
    /**
     * COMPLEX LOGIC EXPLANATION:
     * Prepares positional parameter array tracking. The target category ID must bind to the 1st parameter,
     * followed sequentially by each individual question ID mapped using array_merge().
     */
    $params = array_merge([$category_id], $question_ids);
    $stmt->execute($params);

    echo json_encode(['status' => 'success', 'message' => 'Questions updated successfully.']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}