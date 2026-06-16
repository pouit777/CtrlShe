<?php

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

$stmt = $pdo->prepare("
    SELECT *
    FROM quizzes
    WHERE id = ?
      AND is_active = 1
");

$stmt->execute([$quizId]);

$quiz = $stmt->fetch();

if (!$quiz) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Quiz not found'
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT DISTINCT q.*
    FROM questions q
    INNER JOIN quiz_categories qc
        ON qc.category_id = q.category_id
    WHERE qc.quiz_id = ?
");

$stmt->execute([$quizId]);

$questions = $stmt->fetchAll();

foreach ($questions as &$question) {

    $stmtAnswers = $pdo->prepare("
        SELECT id, answer_text
        FROM answers
        WHERE question_id = ?
        ORDER BY id ASC
    ");

    $stmtAnswers->execute([$question['id']]);

    $question['answers'] = $stmtAnswers->fetchAll();
}

$quiz['questions'] = $questions;

echo json_encode([
    'status' => 'success',
    'data' => $quiz
]);