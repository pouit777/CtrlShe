<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: /login.php");
    exit;
}

require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/components/header.php";

$gameId = (int)($_GET["game"] ?? 0);

if ($gameId <= 0) {
    die("Invalid game");
}

/* 1. GAME INFO */

$stmt = $pdo->prepare("
    SELECT g.*, q.name
    FROM games g
    INNER JOIN quizzes q ON q.id = g.quiz_id
    WHERE g.id = ? AND g.user_id = ?
");

$stmt->execute([$gameId, $_SESSION["user_id"]]);
$game = $stmt->fetch();

if (!$game) {
    die("Game not found");
}

$percent = $game["total_questions"] > 0
    ? round(($game["score"] / $game["total_questions"]) * 100)
    : 0;

/* CORRECTION DETAILS */

$stmt = $pdo->prepare("
    SELECT 
        ga.question_id,
        ga.answer_id AS user_answer_id,
        ga.is_correct,

        q.question_text,

        ua.answer_text AS user_answer,
        ca.answer_text AS correct_answer

    FROM game_answers ga

    INNER JOIN questions q 
        ON q.id = ga.question_id

    LEFT JOIN answers ua 
        ON ua.id = ga.answer_id

    LEFT JOIN answers ca 
        ON ca.question_id = q.id 
        AND ca.is_correct = 1

    WHERE ga.game_id = ?
");

$stmt->execute([$gameId]);
$corrections = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- RESULT UI -->

<div class="max-w-4xl mx-auto mt-10 space-y-6">

    <!-- HEADER RESULT -->
    <div class="bg-gray-800 p-6 rounded-xl border border-gray-700 text-center">

        <h1 class="text-3xl font-bold text-cyan-400 mb-2">
            Quiz Finished
        </h1>

        <h2 class="text-xl text-white mb-4">
            <?= htmlspecialchars($game["name"]) ?>
        </h2>

        <div class="text-5xl font-bold text-green-400 mb-2">
            <?= $game["score"] ?> / <?= $game["total_questions"] ?>
        </div>

        <div class="text-xl text-gray-300">
            <?= $percent ?>%
        </div>

    </div>

    <!-- BUTTONS -->
    <div class="flex justify-center gap-4">

        <a href="/game.php?quiz=<?= $game["quiz_id"] ?>"
           class="bg-cyan-500 px-5 py-3 rounded-lg text-black font-bold">
            Replay
        </a>
        
        <a href="/history.php"
           class="bg-gray-700 px-5 py-3 rounded-lg text-white">
            History
        </a>

        <a href="/index.php"
           class="bg-green-500 px-5 py-3 rounded-lg text-black font-bold">
            Home
        </a>

    </div>

    <!-- CORRECTION LIST -->

    <div class="space-y-4 mt-6">

        <?php foreach ($corrections as $index => $c): ?>

            <?php
                $isCorrect = (int)$c["is_correct"] === 1;
            ?>

            <div class="p-5 rounded-xl border
                <?= $isCorrect ? 'bg-green-900 border-green-500' : 'bg-red-900 border-red-500' ?>
            ">

                <!-- QUESTION -->
                <h3 class="text-lg font-bold text-white mb-3">
                    <?= ($index + 1) ?>. <?= htmlspecialchars($c["question_text"]) ?>
                </h3>

                <!-- USER ANSWER -->
                <p class="text-gray-300">
                    Your answer:
                    <span class="font-bold <?= $isCorrect ? 'text-green-300' : 'text-red-300' ?>">
                        <?= htmlspecialchars($c["user_answer"] ?? "No answer") ?>
                    </span>
                </p>

                <!-- CORRECT ANSWER -->
                <?php if (!$isCorrect): ?>
                    <p class="text-gray-300 mt-1">
                        Correct answer:
                        <span class="font-bold text-green-300">
                            <?= htmlspecialchars($c["correct_answer"]) ?>
                        </span>
                    </p>
                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    </div>

</div>

<?php require_once __DIR__ . "/components/footer.php"; ?>