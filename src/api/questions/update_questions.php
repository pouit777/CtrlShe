<?php
// src/api/questions/update_questions.php
session_start();
header('Content-Type: application/json');

// 1. API Protection Guard: Restrict destructive updates to admin sessions only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

// Fetch incoming payload sent via HTTP POST Request
$input = json_decode(file_get_contents('php://input'), true);

$question_id   = isset($input['id']) ? intval($input['id']) : 0;
$question_text = isset($input['question_text']) ? trim($input['question_text']) : '';
$category_id = isset($input['category_id']) && intval($input['category_id']) > 0 ? intval($input['category_id']) : null;
$difficulty    = isset($input['difficulty']) ? trim($input['difficulty']) : '';
$answers       = isset($input['answers']) ? $input['answers'] : [];
$correct_index = isset($input['correct_index']) ? intval($input['correct_index']) : -1;

// Global validation block checking payload structure, valid difficulty limits, and index boundaries
if ($question_id <= 0 || empty($question_text) || $category_id <= 0 || !in_array($difficulty, ['easy', 'medium', 'hard']) || count($answers) !== 3 || $correct_index < 0 || $correct_index > 2) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or incomplete input data.']);
    exit;
}

try {
    // Initiate Atomic Transaction block to guarantee complete execution updates
    $pdo->beginTransaction();

    // 1. Core update on the target question record matching the ID
    $stmt = $pdo->prepare("UPDATE questions SET question_text = :txt, category_id = :cat, difficulty = :diff WHERE id = :id");
    $stmt->execute([
        'txt'  => $question_text,
        'cat'  => $category_id,
        'diff' => $difficulty,
        'id'   => $question_id
    ]);

    // 2. Fetch existing primary key IDs of the answers bound to this question (ordered to preserve indexed correlation)
    $stmtAnswers = $pdo->prepare("SELECT id FROM answers WHERE question_id = :q_id ORDER BY id ASC");
    $stmtAnswers->execute(['q_id' => $question_id]);
    $existing_answers = $stmtAnswers->fetchAll(PDO::FETCH_COLUMN);

    // Enforce data consistency validation check
    if (count($existing_answers) !== 3) {
        throw new Exception("Integrity error: Expected exactly 3 answers for this question.");
    }

    // 3. Sequentially update each linked option individually
    for ($i = 0; $i < 3; $i++) {
        $ans_id = $existing_answers[$i];
        $is_correct = ($i === $correct_index) ? 1 : 0;
        
        $stmtUpdateAns = $pdo->prepare("UPDATE answers SET answer_text = :txt, is_correct = :correct WHERE id = :id");
        $stmtUpdateAns->execute([
            'txt'     => trim($answers[$i]),
            'correct' => $is_correct,
            'id'      => $ans_id
        ]);
    }

    // Commit all modified statements safely to disk storage
    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Question updated successfully.']);

} catch (Exception $e) {
    // Roll back execution states instantly if any unexpected failure triggers
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}