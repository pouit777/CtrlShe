<?php
// src/api/users/get_users.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 

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

try {
    // Data selection (without the password field)
    $baseQuery = "SELECT id, username, email, role, created_at FROM users";

    // Filter by role if provided in the query parameters
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