<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

$sql = "
SELECT
    q.*,
    COALESCE(
        JSON_ARRAYAGG(
            JSON_OBJECT('id', c.id, 'label', c.label)
        ),
        JSON_ARRAY()
    ) AS categories
FROM quizzes q
LEFT JOIN quiz_categories qc ON q.id = qc.quiz_id
LEFT JOIN categories c ON c.id = qc.category_id
GROUP BY q.id
ORDER BY q.id DESC
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as &$r) {
    $r['categories'] = json_decode($r['categories'], true);
}

echo json_encode([
    'status' => 'success',
    'data' => $rows
]);