<?php
$page_title = "brainSKwiz - Quizzes";

require_once __DIR__ . '/config/db.php';
include "components/header.php";

$categories = $pdo->query("SELECT * FROM categories ORDER BY label")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="titleBox">
    <h1 class="titleText">
        brainSKwiz Quizzes
    </h1>
    <p class="subTitle">
        Choose a quiz and start playing
    </p>
</div>


<!-- FILTER -->
<div class="bg-gray-800 p-6 rounded-xl border border-gray-700 shadow-xl mb-8">
    <div class="flex flex-col md:flex-row gap-4">

        <input id="search-input"
               class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white"
               placeholder="Quiz name...">

        <select id="category-select"
                class="w-full md:w-64 bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
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
<div id="quiz-grid" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>

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
                    <div class="bg-gray-800 p-5 rounded-xl border border-gray-700">
                        <h2 class="text-white font-bold">${q.name}</h2>
                        <p class="text-gray-400">${q.description ?? ''}</p>

                        <div class="text-xs text-gray-500 mt-3">
                            <span class="${diffColor} uppercase">${q.difficulty}</span> • ${q.question_count ?? '∞'}
                        </div>

                        <a href="/game.php?quiz=${q.id}"
                        class="inline-block mt-3 bg-cyan-500 px-4 py-2 rounded text-black font-bold">
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

<?php require_once __DIR__ . '/components/footer.php'; ?>