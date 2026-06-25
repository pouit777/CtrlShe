<?php
// src/api/account/login_process.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';

// Decode incoming raw JSON payload
$input = json_decode(file_get_contents('php://input'), true);

$email = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? trim($input['password']) : '';
$csrfToken = isset($input['csrf_token']) ? $input['csrf_token'] : '';

// 1. Mandatory CSRF token structural verification
if (empty($csrfToken) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    echo json_encode(['status' => 'error', 'message' => 'Security token invalid or expired.']);
    exit;
}

// 2. Structural input emptiness validation
if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields.']);
    exit;
}

try {
    // Query parameters explicitly matching provided structures
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $userAccount = $stmt->fetch();

    // SECURITY REQUISITE: Enforcing secure algorithmic runtime checks (No string matches)
    if ($userAccount && password_verify($password, $userAccount['password'])) {
        
        // Generate new session ID state to completely prevent fixation exposures
        session_regenerate_id(true);
        
        // Establish state bindings for the active user context
        $_SESSION['user_id']  = $userAccount['id'];
        $_SESSION['username'] = $userAccount['username'];
        $_SESSION['role']     = $userAccount['role'];
        $_SESSION['avatar']   = !empty($userAccount['avatar']) ? $userAccount['avatar'] : 'bee.png';

        echo json_encode([
            'status'  => 'success',
            'role'    => $userAccount['role'],
            'message' => 'Login successful!'
        ]);
    } else {
        // Generic failure feedback boundary mapping (Does not disclose if email or password was wrong)
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
    }

} catch (\PDOException $e) {
    // Hidden database error mitigation boundary to stop leaking environmental schemas
    echo json_encode(['status' => 'error', 'message' => 'An internal query exception occurred.']);
}