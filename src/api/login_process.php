<?php
// src/api/login_process.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

$email = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? trim($input['password']) : '';

if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields.']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $userAccount = $stmt->fetch();

    // SECURITY REQUISITE: Enforcing secure algorithmic runtime using password_verify (no hardcoded matches)
    if ($userAccount && password_verify($password, $userAccount['password'])) {
        
        // Define runtime session context mapping
        $_SESSION['user_id'] = $userAccount['id'];
        $_SESSION['username'] = $userAccount['username'];
        $_SESSION['role'] = $userAccount['role'];

        echo json_encode([
            'status' => 'success',
            'role' => $userAccount['role'],
            'message' => 'Login successful!'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
    }

} catch (\PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Query error: ' . $e->getMessage()]);
}