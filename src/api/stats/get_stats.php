<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
SELECT
    MIN(score_users.user_time) AS best_time,
    MAX(score_users.user_score) AS best_score,
    ROUND(AVG(score_users.user_time),2) AS avg_time,
    ROUND(AVG(score_users.user_score / q.question_count) * 100,2) AS success_rate
FROM score_users
JOIN quizzes q ON q.id = score_users.quiz_id
WHERE score_users.user_id = ?
");

$stmt->execute([$userId]);

echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));