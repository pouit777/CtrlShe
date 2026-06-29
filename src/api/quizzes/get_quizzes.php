<?php
// src/api/quizzes/get_quizzes.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

$quizId = (int)($_GET['id'] ?? 0);

if ($quizId <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid quiz id'
    ]);
    exit;
}

/* 1. Fetch Quiz Details */
// Query bounded parameters isolate lookups strictly against validated active entities
$stmt = $pdo->prepare("
    SELECT *
    FROM quizzes
    WHERE id = ?
      AND is_active = 1
");

$stmt->execute([$quizId]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Quiz not found'
    ]);
    exit;
}

/* 2. Fetch Associated Questions */
// INNER JOIN fetches multi-to-multi dynamic dependencies across joining schema records
$stmt = $pdo->prepare("
    SELECT q.*
    FROM questions q
    INNER JOIN quiz_questions qq ON qq.question_id = q.id
    WHERE qq.quiz_id = ?
");

$stmt->execute([$quizId]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* 3. Fetch Answer Options Sequentially */
// Loops through child indices to bind related multiple-choice representations
foreach ($questions as &$question) {

    $stmtAnswers = $pdo->prepare("
        SELECT id, answer_text, is_correct
        FROM answers
        WHERE question_id = ?
        ORDER BY id ASC
    ");

    $stmtAnswers->execute([$question['id']]);
    $question['answers'] = $stmtAnswers->fetchAll(PDO::FETCH_ASSOC);
}

// Re-package nested relational records back into the structural parent data map
$quiz['questions'] = $questions;

echo json_encode([
    'status' => 'success',
    'data' => $quiz
]);