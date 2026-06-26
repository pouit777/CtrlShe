<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$page_title = "Quiz Game";
require_once __DIR__ . '/components/header.php';
require_once __DIR__ . '/config/db.php';

$quiz_id = (int)($_GET['quiz'] ?? 0);

if ($quiz_id <= 0) {
    die("Quiz invalide");
}

/* Quiz */
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ? AND is_active = 1");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    die("Quiz introuvable");
}

/* Questions */
$stmt = $pdo->prepare("
    SELECT q.*
    FROM questions q
    INNER JOIN quiz_questions qq ON qq.question_id = q.id
    WHERE qq.quiz_id = ?
");
$stmt->execute([$quiz_id]);

$questions = $stmt->fetchAll();

if (!$questions) {
    die("Aucune question trouvée");
}

/* Answers */
foreach ($questions as &$q) {

    $stmtA = $pdo->prepare("
        SELECT id, answer_text, is_correct
        FROM answers
        WHERE question_id = ?
        ORDER BY id ASC
    ");

    $stmtA->execute([$q['id']]);
    $q['answers'] = $stmtA->fetchAll();
}
unset($q);

$gameData = [
    "quiz" => [
        "id" => $quiz['id'],
        "name" => $quiz['name'],
        "description" => $quiz['description']
    ],
    "questions" => $questions
];
?>

<!-- GAME DATA -->
<script>
    const GAME_DATA = <?= json_encode($gameData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP); ?>;
</script>

<!-- GAME UI -->
<div class="max-w-4xl mx-auto mt-10">

    <!-- HEADER PLAYER -->
    <div class="flex justify-between items-center bg-gray-800 p-4 rounded-xl border border-gray-700 mb-6">

        <div class="flex items-center gap-3">
            <img src="/public/avatars/<?= $_SESSION['avatar'] ?? 'bee.png' ?>"
                 class="w-12 h-12 rounded-full">

            <div>
                <p class="text-white font-bold">
                    <?= htmlspecialchars($_SESSION['username'] ?? 'Player') ?>
                </p>
                <p class="text-gray-400 text-sm">
                    <?= htmlspecialchars($quiz['name']) ?>
                </p>
            </div>
        </div>

        <div class="text-right">
            <p class="text-sm text-gray-400">Score</p>
            <p id="score" class="text-2xl font-bold text-green-400">0</p>
        </div>

    </div>

    <!-- PROGRESS -->
    <div class="mb-4">
        <div class="flex justify-between text-gray-300 mb-1">
            <span id="progressText">Question 1</span>
            <span id="timer">15</span>
        </div>

        <div class="w-full bg-gray-700 rounded-full h-3">
            <div id="progressBar" class="bg-cyan-500 h-3 rounded-full w-0"></div>
        </div>
    </div>

    <!-- QUESTION BOX -->
    <div class="bg-gray-900 p-6 rounded-xl border border-gray-700">

        <h2 id="questionText" class="text-xl font-bold text-white mb-6"></h2>

        <div id="answers" class="grid gap-3"></div>

        <button id="nextBtn"
                class="mt-6 w-full bg-cyan-500 hover:bg-cyan-600 text-black font-bold py-3 rounded-lg hidden">
            Next
        </button>

    </div>

</div>

<!-- GAME SCRIPT -->
<script src="/assets/js/game.js"></script>

<?php require_once __DIR__ . '/components/footer.php'; ?>