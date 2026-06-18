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

if (empty($data['id']) || empty($data['username']) || empty($data['email']) || empty($data['role'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing fields']);
    exit;
}

try {
    // if password is provided, update it, otherwise keep the old one
    if (!empty($data['password'])) {
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?");
        $stmt->execute([$data['username'], $data['email'], $hashedPassword, $data['role'], $data['id']]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
        $stmt->execute([$data['username'], $data['email'], $data['role'], $data['id']]);
    }
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Update failed (duplicate unique entry).']);
}