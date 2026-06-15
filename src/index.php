<?php
// src/index.php

// Define the page title used by the global header component
$page_title = "brainSKwiz - Search & Filters";

// Include core layout configurations and database connection ($pdo)
require_once __DIR__ . '/components/header.php';

/**
 * Fetch all available categories from the database.
 * These are required to populate the asynchronous filter dropdown menu.
 */
$categories = $pdo->query('SELECT * FROM categories')->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex flex-col sm:flex-row justify-between items-center mb-8 bg-gray-800 p-4 sm:p-6 rounded-xl border border-gray-700 shadow-lg gap-4">
    <div>
        <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500 drop-shadow-sm">
            brainSKwiz API
        </h1>
        <p class="text-sm text-gray-400 mt-1">
            <?php if(isset($_SESSION['username'])): ?>
                Logged in as: <strong class="text-cyan-400"><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8'); ?>)</strong>
            <?php else: ?>
                Mode: <span class="text-gray-300 font-medium">Guest Mode</span>
            <?php endif; ?>
        </p>
    </div>
    <div class="flex items-center gap-4">
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="/admin_questions.php" class="bg-gradient-to-r from-yellow-500 to-amber-600 hover:from-yellow-600 hover:to-amber-700 text-gray-950 font-bold py-2 px-4 rounded-lg shadow-md transition transform hover:-translate-y-0.5 text-sm">Admin Panel</a>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="/logout.php" class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded-lg text-sm font-medium transition">Sign Out</a>
        <?php else: ?>
            <a href="/login.php" class="bg-gradient-to-r from-cyan-500 to-blue-600 text-gray-950 font-bold px-5 py-2 rounded-lg text-sm transition hover:opacity-90">Sign In</a>
        <?php endif; ?>
    </div>
</div>

<div class="flex justify-center mb-8">
    <a href="/game.php" id="play-btn" class="w-full text-center bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-gray-950 font-extrabold text-lg px-8 py-4 rounded-xl shadow-lg transition transform hover:-translate-y-1 flex items-center justify-center gap-2">
        Play a SKwiz !
    </a>
</div>

<div class="bg-gray-800 p-6 rounded-xl border border-gray-700 shadow-xl mb-8 space-y-4">
    <div class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <label for="search-input" class="block text-sm font-medium text-gray-400 mb-1">Search a question</label>
            <input type="text" id="search-input" placeholder="Type keywords (e.g., CSS, protocol...)" 
                   class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400 focus:outline-none focus:border-cyan-500 transition">
        </div>

        <div class="w-full md:w-64">
            <label for="category-select" class="block text-sm font-medium text-gray-400 mb-1">Category</label>
            <select id="category-select" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-cyan-500 transition">
                <option value="">All Categories</option>
                <?php foreach($categories as $cat): ?>
                    <option value="<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['label'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<div id="questions-grid" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>

<script>
    // Extract DOM references for live interaction handling
    const searchInput = document.getElementById('search-input');
    const categorySelect = document.getElementById('category-select');
    const questionsGrid = document.getElementById('questions-grid');
    const playBtn = document.getElementById('play-btn');
    let searchTimeout = null; // Used to handle the text input debounce functionality

    /**
     * Sanitizes raw strings to mitigate Cross-Site Scripting (XSS) vulnerabilities.
     * Replaces HTML control characters with safe equivalent HTML entities.
     */
    function escapeHTML(str) {
        if (!str) return '';
        return str.replace(/[&<>'"]/g, 
            tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag)
        );
    }

    /**
     * Sends an AJAX request via Fetch API to retrieve filtered question feeds.
     * Dynamically builds structural HTML templates and injects them into the grid element.
     */
    function loadQuestions() {
        const searchValue = searchInput.value;
        const categoryValue = categorySelect.value;
        const url = `/api/questions/search_questions.php?search=${encodeURIComponent(searchValue)}&category=${categoryValue}`;

        // Visual feedback indicator: dim grid container opacity while request completes
        questionsGrid.style.opacity = "0.6";

        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error('Network error');
                return response.json();
            })
            .then(response => {
                questionsGrid.style.opacity = "1"; // Restore container visibility context
                
                if (response.status === 'success') {
                    questionsGrid.innerHTML = ''; // Clear previous iterations from feed

                    // Edge Case: Handle empty query states matching filters gracefully
                    if (!response.data || response.data.length === 0) {
                        questionsGrid.innerHTML = `<p class="text-gray-400 italic col-span-1 md:col-span-2 text-center py-8">No questions match your criteria.</p>`;
                        return;
                    }

                    // Iterate over questions array and append processed nodes
                    response.data.forEach(question => {
                        const card = document.createElement('div');
                        card.className = "bg-gray-800 p-5 rounded-xl border border-gray-700 hover:border-cyan-500/50 transition duration-200 flex flex-col justify-between shadow-md";
                        
                        // Select styling tags matching defined difficulty parameters
                        let diffClass = "bg-yellow-950 text-yellow-400 border-yellow-900";
                        if(question.difficulty === 'easy') diffClass = "bg-green-950 text-green-400 border-green-900";
                        if(question.difficulty === 'hard') diffClass = "bg-red-950 text-red-400 border-red-900";

                        // Build inner associative loops rendering nested options
                        let answersHTML = '<div class="mt-4 space-y-2">';
                        if(question.answers && question.answers.length > 0) {
                            question.answers.forEach(ans => {
                                answersHTML += `
                                    <div class="p-2.5 rounded bg-gray-700/40 border border-gray-600/60 text-sm text-gray-200">
                                        ${escapeHTML(ans.text)}
                                    </div>
                                `;
                            });
                        } else {
                            answersHTML += `<p class="text-xs text-gray-500 italic">No options available for this question.</p>`;
                        }
                        answersHTML += '</div>';

                        // Set the dynamic HTML payload safely with custom configurations inside template blocks
                        card.innerHTML = `
                            <div>
                                <span class="text-xs font-mono px-2 py-0.5 rounded uppercase border ${diffClass}">
                                    ${escapeHTML(question.difficulty)}
                                </span>
                                <p class="text-gray-100 text-lg font-medium mt-3">${escapeHTML(question.question_text)}</p>
                                ${answersHTML}
                            </div>
                            <div class="text-xs text-gray-500 mt-4 font-mono pt-3 border-t border-gray-700/50 flex justify-between">
                                <span>ID: #${parseInt(question.id, 10)}</span>
                                <span>Category ID: ${parseInt(question.category_id, 10)}</span>
                            </div>
                        `;
                        questionsGrid.appendChild(card);
                    });
                }
            })
            .catch(err => {
                // Error boundary fallback logic to notify client gracefully
                questionsGrid.style.opacity = "1";
                questionsGrid.innerHTML = `<p class="text-red-400 italic col-span-1 md:col-span-2 text-center py-8">Failed to sync data from API. Please try again.</p>`;
                console.error("Error fetching data:", err);
            });
    }

    /**
     * Event Listener: Watches for category mutations.
     * Alters the main 'Play Button' pathing target and updates the query parameters immediately.
     */
    categorySelect.addEventListener('change', () => {
        if (categorySelect.value) {
            playBtn.href = `/game.php?category=${categorySelect.value}`;
            playBtn.innerText = `Play the category : ${categorySelect.options[categorySelect.selectedIndex].text}`;
        } else {
            playBtn.href = '/game.php';
            playBtn.innerText = 'Play a SKwiz !';
        }
        loadQuestions();
    });

    /**
     * Event Listener Debounce Pattern: Watches for keyword string mutations.
     * Postpones API requests by 300ms intervals during continuous typing sequences 
     * to protect backend engines from server overload.
     */
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(loadQuestions, 300);
    });

    // Bootstrapping phase: Retrieve the baseline dataset once document elements are available
    document.addEventListener('DOMContentLoaded', loadQuestions);
</script>

<?php require_once __DIR__ . '/components/footer.php'; ?>