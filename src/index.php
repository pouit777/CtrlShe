<?php
$page_title = "brainSKwiz - Quizzes";

require_once __DIR__ . '/components/header.php';
require_once __DIR__ . '/config/db.php';

$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

$quizzes = $pdo->query("
    SELECT *
    FROM quizzes
    WHERE is_active = 1
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- HEADER STYLE IDENTIQUE -->
<div class="flex flex-col sm:flex-row justify-between items-center mb-8 bg-gray-800 p-4 sm:p-6 rounded-xl border border-gray-700 shadow-lg gap-4">
    <div>
        <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500">
            brainSKwiz Quizzes
        </h1>

        <p class="text-sm text-gray-400 mt-1">
            Choose a quiz and start playing
        </p>
    </div>

    <div class="flex items-center gap-4">
        <a href="/game.php"
           class="bg-gradient-to-r from-emerald-500 to-teal-600 text-gray-950 font-bold px-5 py-2 rounded-lg text-sm">
            Random Quiz
        </a>
    </div>
</div>

<!-- FILTRE STYLE (OPTIONNEL MAIS GARDÉ UI) -->
<div class="bg-gray-800 p-6 rounded-xl border border-gray-700 shadow-xl mb-8">
    <div class="w-full md:w-64">
        <label class="block text-sm font-medium text-gray-400 mb-1">Filter by category</label>

        <select id="category-select"
                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
            <option value="">All Categories</option>
            <?php foreach($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>">
                    <?= htmlspecialchars($cat['label']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- GRID QUIZZ -->
<div id="quiz-grid" class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <?php foreach($quizzes as $quiz): ?>

        <div class="quiz-card bg-gray-800 p-5 rounded-xl border border-gray-700 hover:border-cyan-500 transition">

            <h2 class="text-xl font-bold text-white">
                <?= htmlspecialchars($quiz['name']) ?>
            </h2>

            <p class="text-gray-400 mt-2">
                <?= htmlspecialchars($quiz['description']) ?>
            </p>

            <div class="mt-4 flex justify-between items-center text-xs text-gray-500">
                <span>Difficulty: <?= htmlspecialchars($quiz['difficulty']) ?></span>
                <span>Questions: <?= (int)$quiz['question_count'] ?: '∞' ?></span>
            </div>

            <a href="/game.php?quiz=<?= (int)$quiz['id'] ?>"
               class="inline-block mt-4 px-4 py-2 bg-cyan-500 rounded-lg text-black font-bold">
                Start Quiz
            </a>

        </div>

    <?php endforeach; ?>

</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>