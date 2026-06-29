<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';

try {

    $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.username,
            u.avatar,
            SUM(g.score) AS total_points,
            COUNT(g.id) AS games_played
        FROM users u
        LEFT JOIN games g ON g.user_id = u.id
        GROUP BY u.id, u.username, u.avatar
        ORDER BY total_points DESC
        LIMIT 100
    ");

    $stmt->execute();

    $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $leaderboard,
        'currentUser' => $_SESSION['user_id']
    ]);

} catch (PDOException $e) {

    echo json_encode([
        'status' => 'error',
        'message' => 'Database error'
    ]);
}