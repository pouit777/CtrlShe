<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "best_time" => 0,
        "avg_time" => 0,
        "avg_time_per_question" => 0,
        "best_score" => 0
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

// NULLIF(duration, 0) avoids capturing unfinished/broken games (duration = 0) in averages and minimums.
// COALESCE(..., 0) ensures a fallback value of 0 is returned if the user has no recorded games yet.
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(MIN(NULLIF(duration, 0)), 0) AS best_time,
        COALESCE(ROUND(AVG(NULLIF(duration, 0)), 2), 0) AS avg_time,
        COALESCE(
            ROUND(
                SUM(NULLIF(duration, 0)) / NULLIF(SUM(total_questions), 0),
                2
            ),
            0
        ) AS avg_time_per_question,
        COALESCE(
            ROUND(MAX((score * 100.0) / NULLIF(total_questions, 0)), 2),
            0
        ) AS best_score
    FROM games
    WHERE user_id = ?
");

$stmt->execute([$userId]);

echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));