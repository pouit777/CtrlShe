<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON received']);
    exit;
}

$id = (int)($data['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit;
}

$name = trim($data['name'] ?? '');
$description = trim($data['description'] ?? '');
$difficulty = $data['difficulty'] ?? 'medium';
$categories = $data['categories'] ?? [];

if ($name === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Quiz name is required']);
    exit;
}

if (empty($categories)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'At least one category is required']);
    exit;
}

if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
    $difficulty = 'medium';
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
        'name' => $name,
        'description' => $description,
        'difficulty' => $difficulty,
        'question_count' => !empty($data['question_count']) ? (int)$data['question_count'] : null,
        'allow_custom' => !empty($data['allow_custom_question_count']) ? 1 : 0
    ]);

    $pdo->prepare("DELETE FROM quiz_categories WHERE quiz_id = ?")->execute([$id]);

    $stmtCat = $pdo->prepare("INSERT INTO quiz_categories (quiz_id, category_id) VALUES (?, ?)");
    foreach ($categories as $catId) {
        $stmtCat->execute([$id, (int)$catId]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to update quiz.']);
}