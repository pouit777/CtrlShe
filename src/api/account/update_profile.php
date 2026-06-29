<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';

// 1. Vérification de la session
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$username = trim($data['username'] ?? '');
$avatar = trim($data['avatar'] ?? '');

// 2. Validation stricte du pseudo (min 3 et max 25)
if (mb_strlen($username) < 3 || mb_strlen($username) > 25) {
    echo json_encode(['status' => 'error', 'message' => 'Username must be between 3 and 25 characters.']);
    exit;
}

if ($avatar === '') {
    echo json_encode(['status' => 'error', 'message' => 'No avatar selected']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET username = ?, avatar = ? WHERE id = ?");
    $success = $stmt->execute([$username, $avatar, $_SESSION['user_id']]);

    if (!$success) {
        echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
        exit;
    }

    $_SESSION['username'] = $username;
    $_SESSION['avatar'] = $avatar;

    echo json_encode([
        'status' => 'success',
        'username' => $username,
        'avatar' => $avatar
    ]);

} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo json_encode(['status' => 'error', 'message' => 'Username already used']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
    }
}