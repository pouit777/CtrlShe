<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
SELECT
    MIN(s.user_time) AS best_time,
    MAX(s.user_score) AS best_score,
    ROUND(AVG(s.user_time),2) AS avg_time,
    ROUND(AVG(s.user_score / q.question_count) * 100,2) AS success_rate
FROM score_users s
JOIN quizzes q ON q.id = s.quiz_id
WHERE s.user_id = ?
");

$stmt->execute([$userId]);

echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));