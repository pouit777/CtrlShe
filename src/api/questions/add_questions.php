<?php
// src/api/questions/add_question.php
session_start();
header('Content-Type: application/json');

// 1. API Protection Guard: Only authenticated administrators are allowed to create items
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access. Admin role required.'
    ]);
    exit;
}

// 2. Import database layer configuration
require_once __DIR__ . '/../../config/db.php';

// 3. Fetch incoming payload sent via HTTP POST Request (JS Fetch Content)
$input = json_decode(file_get_contents('php://input'), true);

$question_text = isset($input['question_text']) ? trim($input['question_text']) : '';
$category_id = isset($input['category_id']) && intval($input['category_id']) > 0 ? intval($input['category_id']) : null;
$difficulty    = isset($input['difficulty']) ? trim($input['difficulty']) : '';
$answers       = isset($input['answers']) ? $input['answers'] : [];
$correct_index = isset($input['correct_index']) ? intval($input['correct_index']) : 0;

// 4. Global request validation checking required schema integrity
if (empty($question_text) || empty($category_id) || empty($difficulty) || empty($answers) || count($answers) < 3) {
    echo json_encode([
        'status' => 'error',
        'message' => 'All fields, including 3 answers options, are required.'
    ]);
    exit;
}

// Ensure the provided difficulty value respects ENUM definition
$allowed_difficulties = ['easy', 'medium', 'hard'];
if (!in_array($difficulty, $allowed_difficulties)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid difficulty level.'
    ]);
    exit;
}

try {
    // 5. Initiate Atomic Transaction block to maintain state consistency
    $pdo->beginTransaction();

    // Prepare and insert the core question object
    $sqlQuestion = "INSERT INTO questions (category_id, question_text, difficulty) 
                    VALUES (:category_id, :question_text, :difficulty)";
    
    $stmtQuestion = $pdo->prepare($sqlQuestion);
    $stmtQuestion->execute([
        'category_id'   => $category_id,
        'question_text' => $question_text,
        'difficulty'    => $difficulty
    ]);

    // Retain the generated foreign key ID reference from last statement execution
    $question_id = $pdo->lastInsertId();

    // Process multiple-choice related answer configurations
    $sqlAnswer = "INSERT INTO answers (question_id, answer_text, is_correct) 
                  VALUES (:question_id, :answer_text, :is_correct)";
    $stmtAnswer = $pdo->prepare($sqlAnswer);

    foreach ($answers as $index => $answer_text) {
        // Evaluate if current sequence maps to the selected target valid position (0, 1, or 2)
        $is_correct = ($index === $correct_index) ? 1 : 0;

        $stmtAnswer->execute([
            'question_id' => $question_id,
            'answer_text' => trim($answer_text),
            'is_correct'  => $is_correct
        ]);
    }

    // Persist all data within transaction pipeline to disk storage
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Question and its answers options successfully added!'
    ]);

} catch (\PDOException $e) {
    // Revert state to original configuration parameters on catch block trigger
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}