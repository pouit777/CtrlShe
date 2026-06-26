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

if (empty($data['id']) || empty($data['username']) || empty($data['email']) || empty($data['role']) || empty($data['avatar'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing fields']);
    exit;
}

$avatar = basename($data['avatar']);

try {
    // If password is provided, update it, otherwise keep the old one
    if (!empty($data['password'])) {
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ?, avatar = ? WHERE id = ?");
        $stmt->execute([$data['username'], $data['email'], $hashedPassword, $data['role'], $avatar, $data['id']]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, avatar = ? WHERE id = ?");
        $stmt->execute([$data['username'], $data['email'], $data['role'], $avatar, $data['id']]);
    }

    // Si l'admin est en train de modifier son PROPRE compte, on met à jour sa session
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $data['id']) {
        $_SESSION['username'] = $data['username'];
        $_SESSION['avatar']   = $avatar;
        $_SESSION['role']     = $data['role']; // Au cas où tu t'enlèves les droits d'admin par accident !
    }
    // ===========================================

    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Update failed (duplicate unique entry).']);
}