<?php
$page_title = "brainSKwiz - Quizzes";

require_once __DIR__ . '/config/db.php';
include "components/header.php";

$categories = $pdo->query("SELECT * FROM categories ORDER BY label")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-index">

<div class="titleBoxAdmin">
    <h1 class="bigTitle">
        brain<span class="skTitle">SK</span>wiz Quizzes
    </h1>
    <p class="subTitle">
        Choose a quiz and start playing
    </p>
</div>

<!-- FILTER -->
<div class="titleBoxAdmin w-full">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-[2fr_1fr] gap-4 items-center w-full">
        <input id="search-input"
               class="inputField"
               placeholder="🔍 Quiz name...">

        <select id="category-select"
                class="inputField">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>">
                    <?= htmlspecialchars($cat['label']) ?>
                </option>
            <?php endforeach; ?>
        </select>

    </div>
</div>

<!-- GRID -->
<div id="quiz-grid" class="quizzes"></div>

<script>
const searchInput = document.getElementById('search-input');
const categorySelect = document.getElementById('category-select');
const quizGrid = document.getElementById('quiz-grid');

let timeout;

function loadQuizzes() {

    const search = searchInput.value;
    const category = categorySelect.value;

    fetch(`/api/quizzes/search_quizzes.php?search=${encodeURIComponent(search)}&category=${category}`)
        .then(r => r.json())
        .then(data => {

            quizGrid.innerHTML = '';

            if (!data.data || data.data.length === 0) {
                quizGrid.innerHTML = `<div class="text-gray-400 col-span-2 text-center">No quiz found</div>`;
                return;
            }

            data.data.forEach(q => {
                const diffColor = q.difficulty.toLowerCase() === 'hard' ? 'text-red-500 font-bold' : 
                  q.difficulty.toLowerCase() === 'medium' ? 'text-amber-400 font-semibold' : 
                  'text-emerald-400 font-semibold';

                quizGrid.innerHTML += `
                    <div class="quizCard">
                        <h2 class="titleCard mb-2">${q.name}</h2>
                        <p class="midTitle">${q.description ?? ''}</p>

                        <div class="text-sm text-gray-100 m-auto mt-3">
                            <span class="${diffColor} uppercase">${q.difficulty}</span> • ${q.question_count ?? '∞'}
                        </div>

                        <a href="/game.php?quiz=${q.id}"
                        class="btn m-auto" style="width: fit-content; margin-top: 1rem;">
                            Start
                        </a>
                    </div>
                `;
        });

    });
}

searchInput.addEventListener('input', () => {
    clearTimeout(timeout);
    timeout = setTimeout(loadQuizzes, 250);
});

categorySelect.addEventListener('change', loadQuizzes);

loadQuizzes();
</script>

</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>