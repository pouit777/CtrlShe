<?php
// src/api/users/get_users.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Admins only.']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

try {
    // Explicit projection targeting: intentionally omits sensitive password hash footprints from transit contexts
    $baseQuery = "SELECT id, username, email, role, created_at FROM users";

    // Conditional path separation based on target role parameters parsing
    if (!empty($_GET['role'])) {
        $stmt = $pdo->prepare($baseQuery . ' WHERE role = :role ORDER BY id DESC');
        $stmt->execute(['role' => $_GET['role']]);
    } else {
        $stmt = $pdo->query($baseQuery . ' ORDER BY id DESC');
    }

    $users = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'data' => $users
    ]);

} catch (\PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}