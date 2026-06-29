<?php
// src/api/users/add_users.php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Gatekeeper restriction validating high-level admin scope access tokens
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

$username = trim($data['username']);
$email = trim($data['email']);
$password = $data['password'];
$role = trim($data['role']);

// 1. Multi-byte string boundary constraint validation for Username handles
if (mb_strlen($username) < 3 || mb_strlen($username) > 25) {
    echo json_encode(['status' => 'error', 'message' => 'Username must be between 3 and 25 characters.']);
    exit;
}

// 2. Structural syntax pattern confirmation via dedicated email validation filters
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address format.']);
    exit;
}

// 3. Robust Passphrase Complexity Rule Check (Length >= 8, containing 1 digit and 1 symbol minimum)
if (strlen($password) < 8 || !preg_match('#[0-9]#', $password) || !preg_match('#[^a-zA-Z0-9]#', $password)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Password must be at least 8 characters long, containing at least one number and one special character.'
    ]);
    exit;
}

// 4. Verification protecting system context from illegal Role privilege assignment hijacking
if (!in_array($role, ['user', 'admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid role assigned.']);
    exit;
}

// Applies standard secure one-way hashing algorithm (bcrypt) before database storage
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    // Persistence mapping with fallback to default placeholder profile icon layout assets
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, avatar) VALUES (?, ?, ?, ?, 'bee.png')");
    $stmt->execute([$username, $email, $hashedPassword, $role]);
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    // Intercept ANSI SQL Duplicate Key Error Code 23000 protecting unique resource constraints
    if ($e->getCode() == 23000) {
        echo json_encode(['status' => 'error', 'message' => 'Username or Email already exists.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'An internal database error occurred.']);
    }
}