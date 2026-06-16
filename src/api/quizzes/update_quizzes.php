<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

$id = (int)($data['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE quizzes SET
            name = :name,
            description = :description,
            difficulty = :difficulty,
            question_count = :question_count,
            allow_custom_question_count = :allow_custom
        WHERE id = :id
    ");

    $stmt->execute([
        'id' => $id,
        'name' => $data['name'],
        'description' => $data['description'],
        'difficulty' => $data['difficulty'],
        'question_count' => $data['question_count'] ?: null,
        'allow_custom' => !empty($data['allow_custom_question_count']) ? 1 : 0
    ]);

    // reset categories
    $pdo->prepare("DELETE FROM quiz_categories WHERE quiz_id = ?")
        ->execute([$id]);

    $stmtCat = $pdo->prepare("
        INSERT INTO quiz_categories (quiz_id, category_id)
        VALUES (?, ?)
    ");

    foreach ($data['categories'] as $catId) {
        $stmtCat->execute([$id, (int)$catId]);
    }

    $pdo->commit();

    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}