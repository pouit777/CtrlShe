<?php
// src/api/users/search_users.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/db.php';

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $role = isset($_GET['role']) ? trim($_GET['role']) : '';

    $sql = "SELECT id, username, email, role, created_at FROM users WHERE 1=1";
    $params = [];

    if (!empty($role)) {
        $sql .= " AND role = :role";
        $params['role'] = $role;
    }

    // Dynamic pattern mapping execution searching against user handle OR structural email fragments
    if (!empty($search)) {
        $sql .= " AND (username LIKE :search OR email LIKE :search)";
        $params['search'] = "%" . $search . "%";
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'count' => count($users),
        'data' => $users
    ]);

} catch (\PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}