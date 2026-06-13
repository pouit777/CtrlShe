<?php
// src/api/get_questions.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 

// Relying on core global connection instantiation file to bypass code redundancy
require_once __DIR__ . '/../config/db.php';

try {
    // Combine queries to extract base item structure alongside associated choices within a unified operation
    $baseQuery = "
        SELECT q.*, 
               (SELECT JSON_ARRAYAGG(JSON_OBJECT('id', a.id, 'text', a.answer_text, 'is_correct', a.is_correct))
                FROM (SELECT * FROM answers WHERE question_id = q.id ORDER BY id ASC) a) as answers
        FROM questions q
    ";

    if (!empty($_GET['category_id'])) {
        $stmt = $pdo->prepare($baseQuery . ' WHERE q.category_id = :cat_id ORDER BY q.id DESC');
        $stmt->execute(['cat_id' => intval($_GET['category_id'])]);
    } else {
        $stmt = $pdo->query($baseQuery . ' ORDER BY q.id DESC');
    }

    $questions = $stmt->fetchAll();

    // Parse MySQL-generated JSON aggregate strings into structured native arrays prior to response delivery
    foreach ($questions as &$q) {
        $q['answers'] = json_decode($q['answers'] ?? '[]', true);
    }

    echo json_encode([
        'status' => 'success',
        'data' => $questions
    ]);

} catch (\PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}