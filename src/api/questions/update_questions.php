<?php
// src/api/questions/update_questions.php
session_start();
header('Content-Type: application/json');

// API Protection Guard: Restrict destructive updates to admin sessions only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

// Fetch incoming payload sent via HTTP POST Request
$input = json_decode(file_get_contents('php://input'), true);

$question_id   = isset($input['id']) ? intval($input['id']) : 0;
$question_text = isset($input['question_text']) ? trim($input['question_text']) : '';
$category_id   = isset($input['category_id']) && intval($input['category_id']) > 0 ? intval($input['category_id']) : null;
$difficulty    = isset($input['difficulty']) ? trim($input['difficulty']) : '';
$answers       = isset($input['answers']) ? $input['answers'] : [];
$correct_index = isset($input['correct_index']) ? intval($input['correct_index']) : -1;

$total_answers = count($answers);

if (
    $question_id <= 0 || 
    empty($question_text) || 
    $category_id <= 0 || 
    !in_array($difficulty, ['easy', 'medium', 'hard']) || 
    $total_answers < 2 || 
    $total_answers > 7 || 
    $correct_index < 0 || 
    $correct_index >= $total_answers
) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or incomplete input data.']);
    exit;
}

try {
    // Initiate Atomic Transaction block to guarantee complete execution updates
    $pdo->beginTransaction();

    // Core update on the target question record matching the ID
    $stmt = $pdo->prepare("UPDATE questions SET question_text = :txt, category_id = :cat, difficulty = :diff WHERE id = :id");
    $stmt->execute([
        'txt'  => $question_text,
        'cat'  => $category_id,
        'diff' => $difficulty,
        'id'   => $question_id
    ]);

    $stmtDelete = $pdo->prepare("DELETE FROM answers WHERE question_id = :q_id");
    $stmtDelete->execute(['q_id' => $question_id]);

    $stmtInsertAns = $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (:q_id, :txt, :correct)");
    
    foreach ($answers as $index => $answer_text) {
        $is_correct = ($index === $correct_index) ? 1 : 0;
        
        $stmtInsertAns->execute([
            'q_id'    => $question_id,
            'txt'     => trim($answer_text),
            'correct' => $is_correct
        ]);
    }

    // Commit all modified statements safely to disk storage
    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Question and dynamic answers updated successfully.']);

} catch (Exception $e) {
    // Roll back execution states instantly if any unexpected failure triggers
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}