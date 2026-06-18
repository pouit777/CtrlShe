<?php
header('Content-Type: application/json');

// Admin verification
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Admins only.']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing user ID']);
    exit;
}

// Bycrypt securisation password
if (intval($data['id']) === intval($_SESSION['user_id'] ?? 0)) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own admin account.']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$data['id']]);
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Deletion failed.']);
}