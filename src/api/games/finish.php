<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

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

$isGuest = !isset($_SESSION["user_id"]);

$score = 0;
$corrections = [];

foreach ($questions as $qid) {

    $userAnswer = $answers[$qid] ?? null;

    $stmtC = $pdo->prepare("
        SELECT id
        FROM answers
        WHERE question_id = ? AND is_correct = 1
        LIMIT 1
    ");
    $stmtC->execute([$qid]);
    $correct = $stmtC->fetchColumn();

    $isCorrect = ($userAnswer == $correct);

    if ($isCorrect) $score++;

    $corrections[] = [
        "question_id" => $qid,
        "user_answer" => $userAnswer,
        "correct_answer" => $correct,
        "is_correct" => $isCorrect
    ];
}

/* SAVE DB ONLY IF USER */
if (!$isGuest) {

    $stmt = $pdo->prepare("
        INSERT INTO games (user_id, quiz_id, score, total_questions)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $_SESSION["user_id"],
        $quizId,
        $score,
        count($questions)
    ]);

    $gameId = $pdo->lastInsertId();
}

/* RESPONSE */
echo json_encode([
    "status" => "success",
    "guest" => $isGuest,
    "score" => $score,
    "total" => count($questions),
    "game_id" => $gameId ?? null
]);