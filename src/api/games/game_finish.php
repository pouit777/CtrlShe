<?php

session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        "status" => "error",
        "message" => "Not logged in"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$quizId = (int)$data["quiz_id"];
$userAnswers = $data["answers"] ?? [];

$score = 0;

foreach ($userAnswers as $questionId => $answerId) {

    $stmt = $pdo->prepare("
        SELECT is_correct
        FROM answers
        WHERE id = ?
        AND question_id = ?
    ");

    $stmt->execute([
        $answerId,
        $questionId
    ]);

    $answer = $stmt->fetch();

    if ($answer && $answer["is_correct"]) {
        $score++;
    }

}

$stmt = $pdo->prepare("
    SELECT question_count
    FROM quizzes
    WHERE id = ?
");

$stmt->execute([$quizId]);

$quiz = $stmt->fetch();

$totalQuestions = (int)$quiz["question_count"];

$stmt = $pdo->prepare("
INSERT INTO games
(
    user_id,
    quiz_id,
    score,
    total_questions
)
VALUES
(
    ?,
    ?,
    ?,
    ?
)
");

$stmt->execute([
    $_SESSION["user_id"],
    $quizId,
    $score,
    $totalQuestions
]);

echo json_encode([
    "status" => "success",
    "game_id" => $pdo->lastInsertId()
]);

<?php include __DIR__."/components/footer.php"; ?>