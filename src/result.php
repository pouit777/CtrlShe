<?php
session_start();

require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/components/header.php";

$guestMode = isset($_GET["guest"]) && $_GET["guest"] == 1;

/* GUEST MODE : Evaluate Guest State Isolation Routing Path */
if ($guestMode) {

    $game = [
        "name" => "Guest Quiz",
        "score" => 0,
        "total_questions" => 0,
        "quiz_id" => 0
    ];

} else {

    // Session state verification perimeter guard
    if (!isset($_SESSION["user_id"])) {
        header("Location: /login.php");
        exit;
    }

    $gameId = (int)($_GET["game"] ?? 0);

    if ($gameId <= 0) {
        die("Invalid game");
    }

    // Parameterized lookup preventing information leaking across distinct user environments
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

// Compute percentage metrics to determine UI color mappings safely
$percent = (!$guestMode && $game["total_questions"] > 0)
    ? round(($game["score"] / $game["total_questions"]) * 100)
    : null;

$pointsEarned = !$guestMode ? (int)$game["score"] : 0;

/*  SCORE COLOR LOGIC */
$scoreClass = "";
if ($percent !== null) {
    if ($percent >= 80) {
        $scoreClass = "score-high";
    } elseif ($percent >= 50) {
        $scoreClass = "score-mid";
    } else {
        $scoreClass = "score-low";
    }
}

$corrections = [];

/* Fetch Historical Review Modifications for Authenticated Users */
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
    <div class="p-6 rounded-xl text-center page-result">

        <h1 style="color: var(--color-primary);"
            class="text-3xl font-bold mb-2">
            Quiz Finished
        </h1>

        <h2 class="text-xl text-white mb-4">
            <?= htmlspecialchars($game["name"] ?? "Guest Quiz") ?>
        </h2>

        <div class="text-5xl font-bold <?= $scoreClass ?>">
            <?= (int)$game["score"] ?> / <?= (int)$game["total_questions"] ?>
        </div>

        <?php if (!$guestMode): ?>
            <div style="color: rgba(255,255,255,0.75);"
                 class="text-xl mt-2">
                <?= $percent ?>%
            </div>
            
            <div class="mt-3 text-lg font-semibold text-yellow-400">
                +<?= $pointsEarned ?> point<?= $pointsEarned > 1 ? "s" : "" ?> gagné<?= $pointsEarned > 1 ? "s" : "" ?>
            </div>
        <?php endif; ?>

    </div>

    <!-- BUTTONS -->
    <div class="flex justify-center gap-4">

        <?php if (!$guestMode): ?>
            <a href="/game.php?quiz=<?= $game["quiz_id"] ?>"
               style="background: var(--color-primary);"
               class="px-5 py-3 rounded-lg text-black font-bold">
                Replay
            </a>
        <?php else: ?>
            <a id="guestReplay"
               href="#"
               style="background: var(--color-primary);"
               class="px-5 py-3 rounded-lg text-black font-bold">
                Replay
            </a>
        <?php endif; ?>

        <?php if (!$guestMode): ?>
            <a href="/history.php"
               style="background: #1f2937;"
               class="px-5 py-3 rounded-lg text-white">
                History
            </a>
        <?php endif; ?>

        <a href="/index.php"
           style="background: var(--color-warning);"
           class="px-5 py-3 rounded-lg text-black font-bold">
            Home
        </a>

    </div>

    <?php if ($guestMode): ?>
        <div class="text-center mt-4"
             style="color: rgba(255,255,255,0.6);">
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