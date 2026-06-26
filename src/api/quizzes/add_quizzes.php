<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

try {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        throw new Exception('Invalid JSON received');
    }

    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $difficulty = $data['difficulty'] ?? 'medium';
    $categories = $data['categories'] ?? [];
    $questions = $data['questions'] ?? []; 
    $allowCustom = !empty($data['allow_custom_question_count']) ? 1 : 0;
    $questionCount = $data['question_count'] ?? null;

    if ($questionCount === '' || $questionCount === null) {
        $questionCount = null;
    } else {
        $questionCount = (int)$questionCount;
    }

    if ($name === '') {
        http_response_code(400);
        throw new Exception('Quiz name is required');
    }

    if (empty($categories)) {
        http_response_code(400);
        throw new Exception('At least one category is required');
    }

    // Validation stricte de la difficulté pour éviter les valeurs bizarres
    if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
        $difficulty = 'medium';
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO quizzes (name, description, difficulty, question_count, allow_custom_question_count, is_active)
        VALUES (:name, :description, :difficulty, :question_count, :allow_custom, 1)
    ");

    $stmt->execute([
        ':name' => $name,
        ':description' => $description,
        ':difficulty' => $difficulty,
        ':question_count' => $questionCount,
        ':allow_custom' => $allowCustom
    ]);

    $quizId = $pdo->lastInsertId();

    $stmtCat = $pdo->prepare("INSERT INTO quiz_categories (quiz_id, category_id) VALUES (?, ?)");
    foreach ($categories as $catId) {
        $stmtCat->execute([(int)$quizId, (int)$catId]);
    }

    if (!empty($questions)) {
        $stmtQ = $pdo->prepare("INSERT INTO quiz_questions (quiz_id, question_id) VALUES (?, ?)");
        foreach ($questions as $qId) {
            $stmtQ->execute([(int)$quizId, (int)$qId]);
        }
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'quiz_id' => $quizId]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(http_response_code() === 200 ? 500 : http_response_code());
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred while processing your request.' 
    ]);
}