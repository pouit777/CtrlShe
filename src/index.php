<?php
$page_title = "brainSKwiz - Quizzes";

require_once __DIR__ . '/config/db.php';
include "components/header.php";

// Fetch all categories sorted alphabetically to feed the filter component drop-down menu
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
               placeholder="Quiz name...">

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
// Cache target DOM element references for fast interactions
const searchInput = document.getElementById('search-input');
const categorySelect = document.getElementById('category-select');
const quizGrid = document.getElementById('quiz-grid');

// Variable allocated to hold the execution ID state of the input debounce timer
let timeout;

/**
 * Dispatches an asynchronous API lookup to query and render filtered quiz catalogs
 */
function loadQuizzes() {

    const search = searchInput.value;
    const category = categorySelect.value;

    /**
     * encodeURIComponent() sanitizes special input symbols (like &, ?, or spaces) typed by users.
     * This converts them into a valid format so they don't corrupt the structural layout of the HTTP GET URL query.
     */
    fetch(`/api/quizzes/search_quizzes.php?search=${encodeURIComponent(search)}&category=${category}`)
        .then(r => r.json())
        .then(data => {

            // Wipe out existing cards before painting the new filtered list
            quizGrid.innerHTML = '';

            // Guard clause handling empty array responses or missing operational parameters
            if (!data.data || data.data.length === 0) {
                quizGrid.innerHTML = `<div class="text-gray-400 col-span-2 text-center">No quiz found</div>`;
                return;
            }

            // Loop sequentially across matching results to generate visual UI components
            data.data.forEach(q => {
                /**
                 * COMPLEX LOGIC EXPLANATION (Nested Ternary Operator):
                 * Acts as a compact conditional chain matching difficulty strings to dynamic CSS styles:
                 * If 'hard' -> apply red, else if 'medium' -> apply amber, else default -> apply emerald.
                 */
                const diffColor = q.difficulty.toLowerCase() === 'hard' ? 'text-red-500 font-bold' : 
                  q.difficulty.toLowerCase() === 'medium' ? 'text-amber-400 font-semibold' : 
                  'text-emerald-400 font-semibold';
                
                // Safely append the structural HTML blueprint template into the container grid
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

/**
 * Listening to 'input' fires every single time a single key is hit. To avoid spamming the
 * database with server requests, we reset (clearTimeout) the timer on each stroke and delay 
 * the actual API execution (loadQuizzes) until the user stops typing for 250 milliseconds.
 */
searchInput.addEventListener('input', () => {
    clearTimeout(timeout);
    timeout = setTimeout(loadQuizzes, 250);
});

// Dropdown changes trigger instant database operations without delay required
categorySelect.addEventListener('change', loadQuizzes);

// Launch initial component painting cycle immediately when the page finishes loading
loadQuizzes();
</script>

</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>