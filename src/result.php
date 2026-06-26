<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: /login.php");
    exit;
}

require_once __DIR__ . "/config/db.php";
include __DIR__ . "/components/header.php";

$gameId = (int)($_GET["game"] ?? 0);

$stmt = $pdo->prepare("
SELECT
    g.score,
    g.total_questions,
    g.quiz_id,
    g.played_at,
    q.name

FROM games g

LEFT JOIN quizzes q
ON q.id = g.quiz_id

WHERE g.id = ?
AND g.user_id = ?
");

$stmt->execute([
    $gameId,
    $_SESSION["user_id"]
]);

$game = $stmt->fetch();

if (!$game) {
    die("Game not found");
}

$percent = round(($game["score"] / $game["total_questions"]) * 100);
?>

<div class="max-w-xl mx-auto mt-10 bg-gray-800 p-8 rounded-xl text-center">

    <h1 class="text-4xl font-bold text-cyan-400 mb-4">
        Quiz Finished!
    </h1>

    <h2 class="text-xl text-white mb-6">
        <?= htmlspecialchars($game["name"]) ?>
    </h2>

    <div class="text-6xl font-bold text-green-400 mb-4">
        <?= $game["score"] ?> / <?= $game["question_count"] ?>
    </div>

    <div class="text-2xl text-gray-300 mb-8">
        <?= $percent ?>%
    </div>

    <div class="flex justify-center gap-4">

        <a href="/game.php?quiz=<?= $_GET["quiz"] ?? "" ?>"
           class="bg-cyan-500 px-5 py-3 rounded-lg text-black font-bold">

            Replay

        </a>

        <a href="/history.php"
           class="bg-gray-700 px-5 py-3 rounded-lg">

            History

        </a>

        <a href="/index.php"
           class="bg-green-500 px-5 py-3 rounded-lg text-black font-bold">

            Home

        </a>

    </div>

</div>

<?php include __DIR__."/components/footer.php"; ?>