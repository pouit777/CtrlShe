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

$quizId = (int)($data["quiz_id"] ?? 0);
$clientAnswers = $data["answers"] ?? [];

if ($quizId <= 0 || empty($clientAnswers)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid payload"
    ]);
    exit;
}

// check answers in db

$stmt = $pdo->prepare("
    SELECT q.id AS question_id, a.id AS answer_id, a.is_correct
    FROM questions q
    INNER JOIN quiz_questions qq ON qq.question_id = q.id
    INNER JOIN answers a ON a.question_id = q.id
    WHERE qq.quiz_id = ?
");

$stmt->execute([$quizId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map correct answers
$correctMap = [];
foreach ($rows as $row) {
    if ($row["is_correct"]) {
        $correctMap[$row["question_id"]] = $row["answer_id"];
    }
}

// create game

$totalQuestions = count(array_unique(array_keys($correctMap)));

$stmt = $pdo->prepare("
    INSERT INTO games (user_id, quiz_id, score, total_questions)
    VALUES (?, ?, 0, ?)
");

$stmt->execute([
    $_SESSION["user_id"],
    $quizId,
    $totalQuestions
]);

$gameId = $pdo->lastInsertId();

// Calcul score + insert game_answers
$score = 0;

$stmtInsert = $pdo->prepare("
    INSERT INTO game_answers (game_id, question_id, answer_id, is_correct)
    VALUES (?, ?, ?, ?)
");

foreach ($correctMap as $questionId => $correctAnswerId) {

    $userAnswerId = $clientAnswers[$questionId] ?? null;

    $isCorrect = ($userAnswerId == $correctAnswerId) ? 1 : 0;

    if ($isCorrect) {
        $score++;
    }

    $stmtInsert->execute([
        $gameId,
        $questionId,
        $userAnswerId,
        $isCorrect
    ]);
}

// Update score final

$stmt = $pdo->prepare("
    UPDATE games
    SET score = ?
    WHERE id = ?
");

$stmt->execute([
    $score,
    $gameId
]);

// Response 

echo json_encode([
    "status" => "success",
    "game_id" => $gameId,
    "score" => $score,
    "total_questions" => $totalQuestions
]);