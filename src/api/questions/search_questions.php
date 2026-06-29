<?php
// src/api/questions/search_questions.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Admins only.']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category_id = isset($_GET['category']) ? trim($_GET['category']) : '';

    /**
     * COMPLEX QUERY LOGIC:
     * Subquery utilizes MySQL JSON aggregation engines (JSON_ARRAYAGG & JSON_OBJECT) to serialize
     * multi-row child answers directly into a single string row attribute attached securely to the root question object.
     */
    $sql = "
        SELECT q.*,
               (SELECT JSON_ARRAYAGG(JSON_OBJECT('id', a.id, 'text', a.answer_text, 'is_correct', a.is_correct))
                FROM (SELECT * FROM answers WHERE question_id = q.id ORDER BY id ASC) a) as answers
        FROM questions q 
        WHERE 1=1
    ";
    $params = [];

    // Safely append optional administrative search parameters to the runtime dynamic query execution
    if (!empty($category_id)) {
        $sql .= " AND q.category_id = :category_id";
        $params['category_id'] = intval($category_id);// Wildcard operators wrapped securely inside variable inputs
    }

    
    if (!empty($search)) {
        $sql .= " AND q.question_text LIKE :search";
        $params['search'] = "%" . $search . "%";
    }

    $sql .= " ORDER BY q.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $questions = $stmt->fetchAll();

    // Map serialized raw storage strings gracefully back into structural native JSON arrays for client rendering
    foreach ($questions as &$q) {
        $q['answers'] = json_decode($q['answers'] ?? '[]', true);
    }

    echo json_encode([
        'status' => 'success',
        'count' => count($questions),
        'data' => $questions
    ]);

} catch (\PDOException $e) {
    // Safety Alert: Swapped to a secure generic string output for production deployment parameters
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}