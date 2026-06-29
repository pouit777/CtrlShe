<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/db.php';

// Reads raw input payload sent via JavaScript fetch body rather than standard form parameters
$data = json_decode(file_get_contents("php://input"), true);

$quizId = (int)($data["quiz_id"] ?? 0);
$answers = $data["answers"] ?? [];
$duration = (int)($data["duration"] ?? 0); 

if ($quizId <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid quiz"
    ]);
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

// FETCH_COLUMN converts resource into a flat indexed array containing only question IDs
$questions = $stmt->fetchAll(PDO::FETCH_COLUMN);

$isGuest = !isset($_SESSION["user_id"]);

$score = 0;

// Loops through each legal question in the quiz to fetch and compare its database truth-value against the submitted data
foreach ($questions as $qid) {

    $userAnswer = $answers[$qid] ?? null;

    $stmtC = $pdo->prepare("
        SELECT id
        FROM answers
        WHERE question_id = ?
        AND is_correct = 1
        LIMIT 1
    ");
    $stmtC->execute([$qid]);

    $correct = $stmtC->fetchColumn();

    if ($userAnswer == $correct) {
        $score++;
    }
}

$pointsEarned = $score;
$gameId = null;

/* SAVE USER GAME */
if (!$isGuest) {

    // Opens transaction block to prevent partial execution hazards if any internal database command breaks down
    $pdo->beginTransaction();

    try {

        $stmt = $pdo->prepare("
            INSERT INTO games (
                user_id,
                quiz_id,
                score,
                total_questions,
                duration,
                points_earned
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_SESSION["user_id"],
            $quizId,
            $score,
            count($questions),
            $duration,
            $pointsEarned
        ]);

        // Retains generated auto-increment record identity to act as the relational bridge key for detail items
        $gameId = $pdo->lastInsertId();

        $stmtInsert = $pdo->prepare("
            INSERT INTO game_answers (
                game_id,
                question_id,
                answer_id,
                is_correct
            )
            VALUES (?, ?, ?, ?)
        ");

        // Loop execution tracking details using the persistent shared prepared statement configuration
        foreach ($questions as $qid) {

            $userAnswer = $answers[$qid] ?? null;

            $stmtC = $pdo->prepare("
                SELECT id
                FROM answers
                WHERE question_id = ?
                AND is_correct = 1
                LIMIT 1
            ");
            $stmtC->execute([$qid]);

            $correct = $stmtC->fetchColumn();

            $stmtInsert->execute([
                $gameId,
                $qid,
                $userAnswer,
                $userAnswer == $correct ? 1 : 0
            ]);
        }

        $stmt = $pdo->prepare("
            UPDATE users
            SET total_points = total_points + ?
            WHERE id = ?
        ");

        $stmt->execute([
            $pointsEarned,
            $_SESSION["user_id"]
        ]);

        // Safely applies write operations to the physical storage space following complete loop verification
        $pdo->commit();

    } catch (Exception $e) {
        // Automatically reverts state variables to their pre-transaction values if anything crashes inside the try-catch ecosystem
        $pdo->rollBack();

        echo json_encode([
            "status" => "error",
            "message" => "Database error."
        ]);
        exit;
    }
}

/* RESPONSE */
echo json_encode([
    "status" => "success",
    "guest" => $isGuest,
    "score" => $score,
    "total" => count($questions),
    "points_earned" => $pointsEarned,
    "game_id" => $gameId
]);