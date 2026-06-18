<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

$category_id = isset($data['category_id']) ? (int)$data['category_id'] : 0;
$question_ids = isset($data['question_ids']) ? $data['question_ids'] : [];

if ($category_id <= 0 || empty($question_ids)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing or invalid data.']);
    exit;
}

try {
    // Prepare the SQL statement with placeholders for the question IDs
    // Generate a string of placeholders for the question IDs
    $placeholders = implode(',', array_fill(0, count($question_ids), '?'));
    
    $query = "UPDATE questions SET category_id = ? WHERE id IN ($placeholders)";
    $stmt = $pdo->prepare($query);
    
    $params = array_merge([$category_id], $question_ids);
    $stmt->execute($params);

    echo json_encode(['status' => 'success', 'message' => 'Questions updated successfully.']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}