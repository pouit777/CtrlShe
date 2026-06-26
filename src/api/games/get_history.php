<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

try {

    $stmt = $pdo->prepare("
        SELECT 
            g.id,
            g.score,
            g.played_at,
            q.name AS quiz_name,
            q.difficulty,
            q.question_count
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

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}