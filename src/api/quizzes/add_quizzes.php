<?php

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);

    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized'
    ]);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

try {

    $raw = file_get_contents('php://input');

    $data = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON received');
    }

    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $difficulty = $data['difficulty'] ?? 'medium';

    $categories = $data['categories'] ?? [];

    $allowCustom = !empty($data['allow_custom_question_count']) ? 1 : 0;

    $questionCount = $data['question_count'] ?? null;

    if ($questionCount === '' || $questionCount === null) {
        $questionCount = null;
    } else {
        $questionCount = (int)$questionCount;
    }

    if ($name === '') {
        throw new Exception('Quiz name is required');
    }

    if (empty($categories)) {
        throw new Exception('At least one category is required');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO quizzes (
            name,
            description,
            difficulty,
            question_count,
            allow_custom_question_count,
            is_active
        )
        VALUES (
            :name,
            :description,
            :difficulty,
            :question_count,
            :allow_custom,
            1
        )
    ");

    $stmt->execute([
        ':name' => $name,
        ':description' => $description,
        ':difficulty' => $difficulty,
        ':question_count' => $questionCount,
        ':allow_custom' => $allowCustom
    ]);

    $quizId = $pdo->lastInsertId();

    $stmtCat = $pdo->prepare("
        INSERT INTO quiz_categories (
            quiz_id,
            category_id
        )
        VALUES (
            :quiz_id,
            :category_id
        )
    ");

    foreach ($categories as $catId) {

        $stmtCat->execute([
            ':quiz_id' => $quizId,
            ':category_id' => (int)$catId
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'quiz_id' => $quizId
    ]);

} catch (Throwable $e) {

    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}