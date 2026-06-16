<?php

$page_title = "brainSKwiz - Play!";
require_once __DIR__ . '/components/header.php';
require_once __DIR__ . '/config/db.php';

$quiz_id = (int)($_GET['quiz'] ?? 0);

if ($quiz_id <= 0) {
    die("Quiz invalide");
}

/* Quiz */

$stmt = $pdo->prepare("
    SELECT *
    FROM quizzes
    WHERE id = ?
      AND is_active = 1
");

$stmt->execute([$quiz_id]);

$quiz = $stmt->fetch();

if (!$quiz) {
    die("Quiz introuvable");
}

/* Questions du quiz */

$stmt = $pdo->prepare("
    SELECT DISTINCT q.*
    FROM questions q
    INNER JOIN quiz_categories qc
        ON qc.category_id = q.category_id
    WHERE qc.quiz_id = ?
");

$stmt->execute([$quiz_id]);

$questions = $stmt->fetchAll();

/* Réponses */

foreach ($questions as &$question) {

    $stmtAnswers = $pdo->prepare("
        SELECT id, answer_text
        FROM answers
        WHERE question_id = ?
        ORDER BY id ASC
    ");

    $stmtAnswers->execute([$question['id']]);

    $question['answers'] = $stmtAnswers->fetchAll();
}

unset($question);

/* Mélange */

shuffle($questions);

/* Limitation nombre de questions */

if (!empty($quiz['question_count'])) {

    $questions = array_slice(
        $questions,
        0,
        (int)$quiz['question_count']
    );
}
?>

<div class="max-w-4xl mx-auto bg-gray-800 p-6 rounded-xl border border-gray-700 shadow-xl my-8">

    <h1 class="text-3xl font-bold text-cyan-400 mb-2">
        <?= htmlspecialchars($quiz['name']) ?>
    </h1>

    <p class="text-gray-400 mb-6">
        <?= htmlspecialchars($quiz['description']) ?>
    </p>

    <div class="space-y-6">

        <?php foreach ($questions as $index => $question): ?>

            <div class="bg-gray-900 p-4 rounded-lg border border-gray-700">

                <h3 class="font-semibold text-lg mb-4">
                    <?= ($index + 1) . '. ' . htmlspecialchars($question['question_text']) ?>
                </h3>

                <div class="space-y-2">

                    <?php foreach ($question['answers'] as $answer): ?>

                        <label class="flex items-center gap-3 p-3 bg-gray-700 rounded cursor-pointer hover:bg-gray-600">

                            <input
                                type="radio"
                                name="question_<?= $question['id'] ?>"
                                value="<?= $answer['id'] ?>"
                            >

                            <span>
                                <?= htmlspecialchars($answer['answer_text']) ?>
                            </span>

                        </label>

                    <?php endforeach; ?>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>