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

if (empty($data['username']) || empty($data['email']) || empty($data['password']) || empty($data['role'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing fields']);
    exit;
}

// Bycrypt securisation password
$hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

try {
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$data['username'], $data['email'], $hashedPassword, $data['role']]);
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Username or Email already exists.']);
}