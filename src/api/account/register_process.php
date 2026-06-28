<?php
// src/api/account/register_process.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

$username  = isset($input['username']) ? trim($input['username']) : '';
$email     = isset($input['email']) ? trim($input['email']) : '';
$password  = isset($input['password']) ? $input['password'] : '';
$csrfToken = isset($input['csrf_token']) ? $input['csrf_token'] : '';

// 1. Vérification du token CSRF
if (empty($csrfToken) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    echo json_encode(['status' => 'error', 'message' => 'Security token invalid or expired.']);
    exit;
}

// 2. Vérification des champs vides
if (empty($username) || empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}

// 3. Validation de la taille du pseudo (3 à 25 caractères)
if (mb_strlen($username) < 3 || mb_strlen($username) > 25) {
    echo json_encode(['status' => 'error', 'message' => 'Username must be between 3 and 25 characters.']);
    exit;
}

// 4. Validation du format de l'email et taille max standard (255)
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address format.']);
    exit;
}

// 5. Validation de la force du mot de passe (Min 8, 1 chiffre, 1 caractère spécial)
if (strlen($password) < 8 || !preg_match('#[0-9]#', $password) || !preg_match('#[^a-zA-Z0-9]#', $password)) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Password must be at least 8 characters long, containing at least one number and one special character.'
    ]);
    exit;
}

// 6. Cryptage BCRYPT (PASSWORD_DEFAULT utilise les standards modernes)
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, avatar) VALUES (?, ?, ?, 'user', 'bee.png')");
    $stmt->execute([$username, $email, $hashedPassword]);

    $userId = $pdo->lastInsertId();

    // Régénération de session contre la fixation de session
    session_regenerate_id(true);

    $_SESSION['user_id']  = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role']     = 'user';
    $_SESSION['avatar']   = 'bee.png';

    echo json_encode(['status' => 'success', 'message' => 'Registration successful.']);
} catch (\PDOException $e) {
    // Erreur de clé dupliquée (User ou Email existant)
    if ($e->getCode() == 23000) {
        echo json_encode(['status' => 'error', 'message' => 'Username or Email already exists.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'An internal database error occurred.']);
    }
}