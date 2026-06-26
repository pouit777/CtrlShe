<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
    exit;
}

$quizId = (int)($data["quiz_id"] ?? 0);
$answers = $data["answers"] ?? [];

if ($quizId <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid quiz"]);
    exit;
}

/* QUESTIONS */
$stmt = $pdo->prepare("
    SELECT q.id
    FROM questions q
    INNER JOIN quiz_questions qq ON qq.question_id = q.id
    WHERE qq.quiz_id = ?
");
$stmt->execute([$quizId]);
$questions = $stmt->fetchAll(PDO::FETCH_COLUMN);

/* CREATE GAME */
$stmt = $pdo->prepare("
    INSERT INTO games (user_id, quiz_id, score, total_questions)
    VALUES (?, ?, 0, ?)
");

$stmt->execute([
    $_SESSION["user_id"],
    $quizId,
    count($questions)
]);

$gameId = $pdo->lastInsertId();

$score = 0;

/* GAME ANSWERS */
$stmtInsert = $pdo->prepare("
    INSERT INTO game_answers (game_id, question_id, answer_id, is_correct)
    VALUES (?, ?, ?, ?)
");

foreach ($questions as $questionId) {

    $userAnswer = $answers[$questionId] ?? null;

    $stmtC = $pdo->prepare("
        SELECT id
        FROM answers
        WHERE question_id = ? AND is_correct = 1
        LIMIT 1
    ");
    $stmtC->execute([$questionId]);
    $correct = $stmtC->fetchColumn();

    $isCorrect = ($userAnswer == $correct) ? 1 : 0;

    if ($isCorrect) $score++;

    $stmtInsert->execute([
        $gameId,
        $questionId,
        $userAnswer,
        $isCorrect
    ]);
}

/* UPDATE SCORE */
$stmt = $pdo->prepare("UPDATE games SET score = ? WHERE id = ?");
$stmt->execute([$score, $gameId]);

echo json_encode([
    "status" => "success",
    "game_id" => $gameId
]);
exit;