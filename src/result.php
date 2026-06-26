<?php
session_start();

require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/components/header.php";

$guestMode = isset($_GET["guest"]) && $_GET["guest"] == 1;

/* GUEST MODE */
if ($guestMode) {

    $game = [
        "name" => "Guest Quiz",
        "score" => 0,
        "total_questions" => 0,
        "quiz_id" => 0
    ];

} else {

    if (!isset($_SESSION["user_id"])) {
        header("Location: /login.php");
        exit;
    }

    $gameId = (int)($_GET["game"] ?? 0);

    if ($gameId <= 0) {
        die("Invalid game");
    }

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
}

/* PERCENT (ONLY FOR LOGGED USER)*/
$percent = (!$guestMode && $game["total_questions"] > 0)
    ? round(($game["score"] / $game["total_questions"]) * 100)
    : null;

/* CORRECTIONS (ONLY DB USERS)*/
$corrections = [];

if (!$guestMode) {

    $stmt = $pdo->prepare("
        SELECT 
            ga.question_id,
            ga.answer_id,
            ga.is_correct,
            q.question_text,
            ua.answer_text AS user_answer,
            ca.answer_text AS correct_answer
        FROM game_answers ga
        INNER JOIN questions q ON q.id = ga.question_id
        LEFT JOIN answers ua ON ua.id = ga.answer_id
        LEFT JOIN answers ca ON ca.question_id = q.id AND ca.is_correct = 1
        WHERE ga.game_id = ?
    ");

    $stmt->execute([$gameId]);
    $corrections = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="max-w-4xl mx-auto mt-10 space-y-6">

    <!-- HEADER -->
    <div class="bg-gray-800 p-6 rounded-xl text-center">

        <h1 class="text-3xl font-bold text-cyan-400 mb-2">
            Quiz Finished
        </h1>

        <h2 class="text-xl text-white mb-4">
            <?= htmlspecialchars($game["name"] ?? "Guest Quiz") ?>
        </h2>

        <!-- SCORE -->
        <div class="text-5xl font-bold text-green-400">
            <?= (int)$game["score"] ?> / <?= (int)$game["total_questions"] ?>
        </div>

        <!-- PERCENT ONLY FOR REGISTERED USERS -->
        <?php if (!$guestMode): ?>
            <div class="text-xl text-gray-300 mt-2">
                <?= $percent ?>%
            </div>
        <?php endif; ?>

    </div>

    <!-- BUTTONS -->
    <div class="flex justify-center gap-4">

        <?php if (!$guestMode): ?>

            <a href="/game.php?quiz=<?= $game["quiz_id"] ?>"
                class="bg-cyan-500 px-5 py-3 rounded-lg text-black font-bold">
                Replay
            </a>

        <?php else: ?>

            <a id="guestReplay"
               href="#"
                class="bg-cyan-500 px-5 py-3 rounded-lg text-black font-bold">
                Replay
            </a>

        <?php endif; ?>

        <!-- HISTORY BUTTON ONLY FOR REGISTERED USERS -->
        <?php if (!$guestMode): ?>
            <a href="/history.php"
               class="bg-gray-700 px-5 py-3 rounded-lg text-white">
                History
            </a>
        <?php endif; ?>

        <a href="/index.php"
           class="bg-green-500 px-5 py-3 rounded-lg text-black font-bold">
            Home
        </a>

    </div>

    <!-- GUEST INFO -->
    <?php if ($guestMode): ?>
        <div class="text-center text-gray-400 mt-4">
            Guest mode - score is not saved
        </div>

        <script>
            const data = JSON.parse(localStorage.getItem("guest_result"));

            if (data) {

                document.querySelector(".text-5xl").innerText =
                   data.score + " / " + data.total;

                const replayBtn = document.getElementById("guestReplay");

                if (replayBtn) {
                    const params = new URLSearchParams(window.location.search);
                    replayBtn.href = "/game.php?quiz=" + params.get("quiz");

                }  
            }
        </script>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . "/components/footer.php"; ?>