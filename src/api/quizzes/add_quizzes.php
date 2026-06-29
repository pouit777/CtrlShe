<?php
// src/api/quizzes/add_quizzes.php
session_start();
header('Content-Type: application/json');

// RBAC: Strict access control ensuring only authenticated administrators can proceed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

try {

    // Read raw data stream from HTTP request body
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    // Validate JSON structure to prevent malformed payload processing
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        throw new Exception('Invalid JSON received');
    }

    // Sanitize and map input variables with fallback defaults
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $difficulty = $data['difficulty'] ?? 'medium';
    $categories = $data['categories'] ?? [];
    $questions = $data['questions'] ?? []; 
    $allowCustom = !empty($data['allow_custom_question_count']) ? 1 : 0;
    $questionCount = $data['question_count'] ?? null;

    // Normalize empty question count inputs to null
    if ($questionCount === '' || $questionCount === null) {
        $questionCount = null;
    } else {
        $questionCount = (int)$questionCount;
    }

    // Input boundary validations
    if ($name === '') {
        http_response_code(400);
        throw new Exception('Quiz name is required');
    }

    if (empty($categories)) {
        http_response_code(400);
        throw new Exception('At least one category is required');
    }

    // Whitelist validation for Enum difficulty values to prevent unexpected injections
    if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
        $difficulty = 'medium';
    }

    // Initiate atomic ACID transaction block to ensure structural database integrity
    $pdo->beginTransaction();

    // Secure parameterized insert for the core quiz entity
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

    // Retrieve generated foreign key ID for child table mappings
    $quizId = $pdo->lastInsertId();

    // Bulk-insert intermediate relationship records for categories
    $stmtCat = $pdo->prepare("INSERT INTO quiz_categories (quiz_id, category_id) VALUES (?, ?)");
    foreach ($categories as $catId) {
        $stmtCat->execute([(int)$quizId, (int)$catId]);
    }

    // Bulk-insert relationship mappings for specific pre-selected questions
    if (!empty($questions)) {
        $stmtQ = $pdo->prepare("INSERT INTO quiz_questions (quiz_id, question_id) VALUES (?, ?)");
        foreach ($questions as $qId) {
            $stmtQ->execute([(int)$quizId, (int)$qId]);
        }
    } else {
        $limit = $questionCount ?? 10;
        $placeholders = implode(',', array_fill(0, count($categories), '?'));
        $stmtQ = $pdo->prepare("
            SELECT id FROM questions 
            WHERE category_id IN ($placeholders)
            ORDER BY RAND()
            LIMIT $limit
        ");
        $stmtQ->execute(array_map('intval', $categories));
        $autoQuestions = $stmtQ->fetchAll(PDO::FETCH_COLUMN);

        $stmtInsert = $pdo->prepare("INSERT INTO quiz_questions (quiz_id, question_id) VALUES (?, ?)");
        foreach ($autoQuestions as $qId) {
            $stmtInsert->execute([(int)$quizId, (int)$qId]);
        }
    }

    // Persist all staging records safely to disk storage
    $pdo->commit();
    echo json_encode(['status' => 'success', 'quiz_id' => $quizId]);

} catch (Throwable $e) {
    // Rollback operations to intercept incomplete mutations on system failure
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(http_response_code() === 200 ? 500 : http_response_code());
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred while processing your request.' 
    ]);
}