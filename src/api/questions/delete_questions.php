<?php
// src/api/questions/delete_question.php
session_start();
header('Content-Type: application/json');

// 1. Protection Guard: Destructive operations restricted to administrator role only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$question_id = isset($input['id']) ? intval($input['id']) : 0;

if ($question_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid question ID.']);
    exit;
}

try {
    // The ON DELETE CASCADE constraint on SQL engine layer deletes child answer components automatically
    $stmt = $pdo->prepare("DELETE FROM questions WHERE id = :id");
    $success = $stmt->execute(['id' => $question_id]);

    if ($success && $stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Question deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Question not found or already deleted.']);
    }
} catch (\PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}