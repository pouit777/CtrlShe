<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

try {
    $search = trim($_GET['search'] ?? '');
    $category = (int)($_GET['category'] ?? 0);

    $sql = "
        SELECT DISTINCT q.id, q.name, q.description, q.difficulty, q.question_count
        FROM quizzes q
        INNER JOIN quiz_categories qc ON qc.quiz_id = q.id
        WHERE q.is_active = 1
    ";

    $params = [];

    if ($search !== '') {
        $sql .= " AND q.name LIKE :search";
        $params['search'] = '%' . $search . '%';
    }

    if ($category > 0) {
        $sql .= " AND qc.category_id = :category";
        $params['category'] = $category;
    }

    $sql .= " ORDER BY q.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        'status' => 'success',
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred during search.'
    ]);
}