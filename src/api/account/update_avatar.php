<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$avatar = $data['avatar'] ?? null;

if (!$avatar) {
    echo json_encode(['status' => 'error', 'message' => 'No avatar selected']);
    exit;
}

// update DB
$stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
$stmt->execute([$avatar, $_SESSION['user_id']]);

// update session
$_SESSION['avatar'] = $avatar;

echo json_encode(['status' => 'success']);