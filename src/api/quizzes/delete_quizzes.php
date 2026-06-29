<?php
// src/api/quizzes/delete_quizzes.php
session_start();
header('Content-Type: application/json');

// Security perimeter checkpoint: verification matching admin permissions exclusively
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

// Decode JSON format payload to safely parse numeric targeting parameters
$data = json_decode(file_get_contents('php://input'), true);
$id = (int)($data['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit;
}

// Employs a secure parameterized DELETE query to shield against raw string SQL injections
$stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ?");
$stmt->execute([$id]);

echo json_encode(['status' => 'success']);