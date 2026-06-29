<?php
// src/api/games/get_history.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';

// 1. Session authentication state enforcement check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

// 2. Anti-CSRF verification check: Matches incoming standard custom HTTP headers
$headers = getallheaders();
$receivedToken = $headers['X-CSRF-Token'] ?? '';

// Employs a time-constant string comparison algorithm to block timing side-channel exploits
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $receivedToken)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Parameterized syntax mapping safely isolates user contexts away from structural SQL elements
    $stmt = $pdo->prepare("
        SELECT
            g.id,
            g.score,
            g.total_questions,
            g.played_at,
            q.name AS quiz_name,
            q.difficulty
        FROM games g
        LEFT JOIN quizzes q ON q.id = g.quiz_id
        WHERE g.user_id = ?
        ORDER BY g.played_at DESC
    ");

    $stmt->execute([$userId]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $history
    ]);

} catch (PDOException $e) {
    // Production security mitigation strategy: generic string output suppresses detailed database schema leaks
    echo json_encode([
        'status' => 'error',
        'message' => 'A database error occurred.' 
    ]);
}