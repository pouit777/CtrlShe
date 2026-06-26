<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';

// 1. Vérification de la session
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Not logged in'
    ]);
    exit;
}

// 2. Récupération des données JSON
$data = json_decode(file_get_contents('php://input'), true);

$username = trim($data['username'] ?? '');
$avatar = trim($data['avatar'] ?? '');

// 3. Validations des données
if (strlen($username) < 3) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Username too short (minimum 3 characters)'
    ]);
    exit;
}

if ($avatar === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'No avatar selected'
    ]);
    exit;
}

try {
    // 4. Une seule requête SQL pour mettre à jour les deux champs
    $stmt = $pdo->prepare("
        UPDATE users
        SET username = ?, avatar = ?
        WHERE id = ?
    ");

    $success = $stmt->execute([
        $username,
        $avatar,
        $_SESSION['user_id']
    ]);

    if (!$success) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database update failed'
        ]);
        exit;
    }

    // 5. Mise à jour des variables de session pour le Header
    $_SESSION['username'] = $username;
    $_SESSION['avatar'] = $avatar;

    // 6. Réponse de succès globale
    echo json_encode([
        'status' => 'success',
        'username' => $username,
        'avatar' => $avatar
    ]);

} catch (PDOException $e) {
    // Gestion spécifique du cas où le pseudo est déjà pris (si tu as une contrainte UNIQUE en BDD)
    if ($e->getCode() == 23000) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Username already used'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}