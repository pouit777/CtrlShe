<?php
header('Content-Type: application/json');

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

$username = trim($data['username']);
$email = trim($data['email']);
$password = $data['password'];
$role = trim($data['role']);

// 1. Validation de la taille du pseudo
if (mb_strlen($username) < 3 || mb_strlen($username) > 25) {
    echo json_encode(['status' => 'error', 'message' => 'Username must be between 3 and 25 characters.']);
    exit;
}

// 2. Validation de l'email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address format.']);
    exit;
}

// 3. Validation de la force du mot de passe
if (strlen($password) < 8 || !preg_match('#[0-9]#', $password) || !preg_match('#[^a-zA-Z0-9]#', $password)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Password must be at least 8 characters long, containing at least one number and one special character.'
    ]);
    exit;
}

// 4. Validation du rôle
if (!in_array($role, ['user', 'admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid role assigned.']);
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    // Par défaut, on attribue l'avatar 'bee.png' à la création
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, avatar) VALUES (?, ?, ?, ?, 'bee.png')");
    $stmt->execute([$username, $email, $hashedPassword, $role]);
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo json_encode(['status' => 'error', 'message' => 'Username or Email already exists.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'An internal database error occurred.']);
    }
}