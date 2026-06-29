<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification stricte du rôle Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Admins only.']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id']) || empty($data['username']) || empty($data['email']) || empty($data['role']) || empty($data['avatar'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing fields']);
    exit;
}

$id = (int)$data['id'];
$username = trim($data['username']);
$email = trim($data['email']);
$role = trim($data['role']);
$avatar = basename($data['avatar']); // Évite les injections de chemin (Path Traversal)
$password = isset($data['password']) ? $data['password'] : '';

// 1. Validation du Pseudo (3 à 25 caractères)
if (mb_strlen($username) < 3 || mb_strlen($username) > 25) {
    echo json_encode(['status' => 'error', 'message' => 'Username must be between 3 and 25 characters.']);
    exit;
}

// 2. Validation du format de l'email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address format.']);
    exit;
}

// 3. Validation du rôle autorisé
if (!in_array($role, ['user', 'admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid role assigned.']);
    exit;
}

try {
    if (!empty($password)) {
        // Validation de force du mot de passe UNIQUE_MENT s'il est fourni
        if (strlen($password) < 8 || !preg_match('#[0-9]#', $password) || !preg_match('#[^a-zA-Z0-9]#', $password)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'New password must be at least 8 characters long, with 1 number and 1 special character.'
            ]);
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ?, avatar = ? WHERE id = ?");
        $stmt->execute([$username, $email, $hashedPassword, $role, $avatar, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, avatar = ? WHERE id = ?");
        $stmt->execute([$username, $email, $role, $avatar, $id]);
    }

    // Mise à jour de la session de l'admin s'il s'auto-édite
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
        $_SESSION['username'] = $username;
        $_SESSION['avatar']   = $avatar;
        $_SESSION['role']     = $role; 
    }

    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo json_encode(['status' => 'error', 'message' => 'Username or Email already exists.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'An internal database error occurred.']);
    }
}