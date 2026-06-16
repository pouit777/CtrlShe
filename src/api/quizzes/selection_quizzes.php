<?php

require_once __DIR__ . '/config/db.php';

$quizzes = $pdo->query("
    SELECT *
    FROM quizzes
    WHERE is_active = 1
    ORDER BY id DESC
")->fetchAll();

$page_title = "Choose a quiz";

require_once __DIR__ . '/components/header.php';
?>

<div class="grid gap-4">

<?php foreach($quizzes as $quiz): ?>

<div class="bg-gray-800 p-5 rounded-xl">

    <h2 class="text-xl font-bold">
        <?= htmlspecialchars($quiz['name']) ?>
    </h2>

    <p class="text-gray-400">
        <?= htmlspecialchars($quiz['description']) ?>
    </p>

    <a
        href="/game.php?quiz=<?= $quiz['id'] ?>"
        class="inline-block mt-4 px-4 py-2 bg-cyan-500 rounded-lg"
    >
        Start Quiz
    </a>

</div>

<?php endforeach; ?>

</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>