<?php
// src/api/account/update_password.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';

// 1. Enforce active authentication state verification
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

// Fetch structural input stream parameters
$data = json_decode(file_get_contents('php://input'), true);
$password = $data['password'] ?? '';

// 2. Server-side password entropy verification strategy
if (strlen($password) < 8 || !preg_match('#[0-9]#', $password) || !preg_match('#[^a-zA-Z0-9]#', $password)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Password must be at least 8 characters long, containing at least one number and one special character.'
    ]);
    exit;
}

try {
    // Re-encrypt updated parameters safely
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $success = $stmt->execute([$hashedPassword, $_SESSION['user_id']]);

    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Password updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'A server error occurred.']);
}