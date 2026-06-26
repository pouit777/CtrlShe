<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';

// 1. Vérification de la session active
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Not logged in'
    ]);
    exit;
}

// 2. Récupération des données JSON envoyées par fetch()
$data = json_decode(file_get_contents('php://input'), true);
$password = $data['password'] ?? '';

// 3. Validation de la longueur minimale côté serveur
if (strlen($password) < 8) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Password must be at least 8 characters long.'
    ]);
    exit;
}

try {
    // 4. Sécurité : Hachage du nouveau mot de passe
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // 5. Mise à jour dans la base de données (uniquement le champ password)
    $stmt = $pdo->prepare("
        UPDATE users
        SET password = ?
        WHERE id = ?
    ");

    $success = $stmt->execute([
        $hashedPassword,
        $_SESSION['user_id']
    ]);

    if ($success) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Password updated successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database update failed'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}