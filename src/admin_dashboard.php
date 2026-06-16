<?php
session_start();

// Page Protection Access Guard
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/config/db.php';
$categories = $pdo->query('SELECT * FROM categories')->fetchAll();
$quizzes = $pdo->query("SELECT 
        q.*,
        GROUP_CONCAT(c.id) AS category_ids,
        GROUP_CONCAT(c.label SEPARATOR ', ') AS category_labels
    FROM quizzes q
    LEFT JOIN quiz_categories qc ON q.id = qc.quiz_id
    LEFT JOIN categories c ON c.id = qc.category_id
    GROUP BY q.id
    ORDER BY q.id DESC")->fetchAll();

// Highly-optimized JSON sub-query grouping matching parent entity questions to related child choice arrays
$query = "
    SELECT q.*, c.label as category_label,
           (SELECT JSON_ARRAYAGG(JSON_OBJECT('id', a.id, 'text', a.answer_text, 'is_correct', a.is_correct))
            FROM (SELECT * FROM answers WHERE question_id = q.id ORDER BY id ASC) a) as answers_json
    FROM questions q 
    LEFT JOIN categories c ON q.category_id = c.id
    ORDER BY q.id DESC
";
$questions = $pdo->query($query)->fetchAll();

$page_title = "brainSKwiz - Admin Panel";
require_once __DIR__ . '/components/header.php';
?>

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8 bg-gray-800 p-6 rounded-xl border border-gray-700 shadow-lg">
            <div>
                <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-500 via-blue-400 to-cyan-400 drop-shadow-sm">Admin Dashboard</h1>
                <p class="text-sm text-gray-400 mt-1">
                    Logged in as : <strong class="text-cyan-400"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8'); ?>)</strong>
                </p>
            </div>
            <div class="flex items-center gap-3 w-full md:w-auto justify-end">
                <button id="open-modal-btn" class="bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-gray-950 font-bold py-2 px-4 rounded-lg shadow-md transition transform hover:-translate-y-0.5">
                    + Add New Question
                </button>
                <button id="open-quiz-modal-btn" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg">
                    + Add New Quiz
                </button>
                <a href="/logout.php" class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded-lg font-medium transition text-center">Logout</a>
            </div>
        </div>

        <div class="bg-gray-800 rounded-xl border border-gray-700 shadow-xl overflow-hidden overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-700/50 text-gray-400 uppercase text-xs font-mono border-b border-gray-700">
                        <th class="p-4">ID</th>
                        <th class="p-4">Question Text</th>
                        <th class="p-4">Category</th>
                        <th class="p-4">Difficulty</th>
                        <th class="p-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="questions-table-body">
                    <?php foreach($questions as $q): ?>
                        <tr id="question-row-<?php echo $q['id']; ?>" class="border-b border-gray-700/50 hover:bg-gray-700/30 transition">
                            <td class="p-4 font-mono text-cyan-400">#<?php echo $q['id']; ?></td>
                            <td class="p-4 text-gray-200 font-medium"><?php echo htmlspecialchars($q['question_text'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="p-4"><span class="bg-gray-900 px-2 py-1 rounded text-sm text-gray-400 border border-gray-700"><?php echo htmlspecialchars($q['category_label'] ?? 'No category', ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td class="p-4">
                                <span class="text-xs font-mono uppercase px-2 py-0.5 rounded <?php echo $q['difficulty'] === 'easy' ? 'bg-green-950 text-green-400 border border-green-900' : ($q['difficulty'] === 'medium' ? 'bg-yellow-950 text-yellow-400 border border-yellow-900' : 'bg-red-950 text-red-400 border border-red-900'); ?> border">
                                    <?php echo htmlspecialchars($q['difficulty'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td class="p-4 flex justify-center gap-2">
                                <button 
                                    data-id="<?php echo $q['id']; ?>"
                                    data-category="<?php echo $q['category_id']; ?>"
                                    data-difficulty="<?php echo htmlspecialchars($q['difficulty'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-text="<?php echo htmlspecialchars($q['question_text'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-answers="<?php echo htmlspecialchars($q['answers_json'] ?? '[]', ENT_QUOTES, 'UTF-8'); ?>"
                                    onclick="initEditModal(this)"
                                    class="inline-flex items-center gap-1 bg-blue-900/30 hover:bg-blue-600 border border-blue-800 hover:border-blue-500 text-blue-300 hover:text-white px-2.5 py-1.5 rounded-lg text-xs font-semibold transition duration-150 shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                    Edit
                                </button>

                                <button onclick="confirmDeleteQuestion(<?php echo $q['id']; ?>)" class="inline-flex items-center gap-1 bg-red-900/30 hover:bg-red-600 border border-red-800 hover:border-red-500 text-red-300 hover:text-white px-2.5 py-1.5 rounded-lg text-xs font-semibold transition duration-150 shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    
    <div class="mt-10 bg-gray-800 rounded-xl border border-gray-700 shadow-xl overflow-hidden">
        <div class="p-4 border-b border-gray-700">
            <h2 class="text-xl font-bold text-purple-400">
                Quiz Management
            </h2>
        </div>

        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-700/50">
                    <th class="p-4">Name</th>
                    <th class="p-4">Difficulty</th>
                    <th class="p-4">Questions</th>
                    <th class="p-4">Custom Count</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach($quizzes as $quiz): ?>
                <tr>
                    <td class="p-4">
                        <?= htmlspecialchars($quiz['name']) ?>
                    </td>

                    <td class="p-4">
                        <?= htmlspecialchars($quiz['difficulty']) ?>
                    </td>

                    <td class="p-4">
                        <?= htmlspecialchars($quiz['category_labels'] ?? 'None') ?>
                    </td>

                    <td class="p-4">
                        <?= $quiz['question_count'] ?? 'Variable' ?>
                    </td>

                    <td class="p-4">
                        <?= $quiz['allow_custom_question_count'] ? 'Yes' : 'No' ?>
                    </td>

                    <td class="p-4 flex gap-2">

                        <button
                            onclick="editQuiz(this)"
                            data-id="<?= $quiz['id'] ?>"
                            data-name="<?= htmlspecialchars($quiz['name']) ?>"
                            data-description="<?= htmlspecialchars($quiz['description']) ?>"
                            data-categories="<?= $quiz['category_ids'] ?>"
                            data-difficulty="<?= $quiz['difficulty'] ?>"
                            data-count="<?= $quiz['question_count'] ?>"
                            data-custom="<?= $quiz['allow_custom_question_count'] ?>"
                            class="inline-flex items-center gap-1 bg-blue-900/30 hover:bg-blue-600 border border-blue-800 hover:border-blue-500 text-blue-300 hover:text-white px-2.5 py-1.5 rounded-lg text-xs font-semibold transition duration-150 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                                class="w-3.5 h-3.5">
                               <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                            Edit
                        </button>

                        <button
                            onclick="deleteQuiz(<?= $quiz['id'] ?>)"
                            class="inline-flex items-center gap-1 bg-red-900/30 hover:bg-red-600 border border-red-800 hover:border-red-500 text-red-300 hover:text-white px-2.5 py-1.5 rounded-lg text-xs font-semibold transition duration-150 shadow-sm">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                                class="w-3.5 h-3.5">
                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                            </svg>

                            Delete
                        </button>

                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="question-modal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-gray-800 max-w-lg w-full rounded-2xl border border-gray-700 p-6 shadow-2xl overflow-y-auto max-h-[90vh]">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-emerald-400">Add a New Question</h3>
                <button id="close-modal-btn" class="text-gray-400 hover:text-white text-2xl font-bold">&times;</button>
            </div>
            <form id="add-question-form" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Question Text</label>
                    <textarea id="modal-question-text" required rows="2" placeholder="Ex: What does CSS stand for?" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-emerald-500 transition"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">Category</label>
                        <select id="modal-category" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-emerald-500 transition">
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['label'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">Difficulty</label>
                        <select id="modal-difficulty" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-emerald-500 transition">
                            <option value="easy">Easy</option>
                            <option value="medium" selected>Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>
                </div>
                <hr class="border-gray-700 my-2">
                <div>
                    <label class="block text-sm font-medium text-emerald-400 mb-2">Answers Options (Select the correct one)</label>
                    <div class="space-y-3">
                        <?php for($i = 0; $i < 3; $i++): ?>
                        <div class="flex items-center gap-3">
                            <input type="radio" name="correct_answer" value="<?php echo $i; ?>" <?php echo $i === 0 ? 'checked' : ''; ?> class="w-4 h-4 text-emerald-500 bg-gray-700 border-gray-600 focus:ring-emerald-500">
                            <input type="text" id="answer-<?php echo $i; ?>" required placeholder="Answer option <?php echo $i+1; ?>" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-1.5 text-white focus:outline-none focus:border-emerald-500 transition">
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" id="cancel-modal-btn" class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded-lg font-medium transition">Cancel</button>
                    <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-gray-950 font-bold px-4 py-2 rounded-lg transition">Save Question</button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-question-modal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-gray-800 max-w-lg w-full rounded-2xl border border-gray-700 p-6 shadow-2xl overflow-y-auto max-h-[90vh]">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-blue-400">Edit Question Detail</h3>
                <button id="close-edit-modal-btn" class="text-gray-400 hover:text-white text-2xl font-bold">&times;</button>
            </div>
            <form id="edit-question-form" class="space-y-4">
                <input type="hidden" id="edit-question-id">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Question Text</label>
                    <textarea id="edit-modal-question-text" required rows="2" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-blue-500 transition"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">Category</label>
                        <select id="edit-modal-category" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-blue-500 transition">
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['label'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">Difficulty</label>
                        <select id="edit-modal-difficulty" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-blue-500 transition">
                            <option value="easy">Easy</option>
                            <option value="medium">Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>
                </div>
                <hr class="border-gray-700 my-2">
                <div>
                    <label class="block text-sm font-medium text-blue-400 mb-2">Answers Options (Check the correct one)</label>
                    <div class="space-y-3">
                        <?php for($i = 0; $i < 3; $i++): ?>
                        <div class="flex items-center gap-3">
                            <input type="radio" name="edit_correct_answer" value="<?php echo $i; ?>" id="edit-radio-<?php echo $i; ?>" class="w-4 h-4 text-blue-500 bg-gray-700 border-gray-600 focus:ring-blue-500">
                            <input type="text" id="edit-answer-<?php echo $i; ?>" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-1.5 text-white focus:outline-none focus:border-blue-500 transition">
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" id="cancel-edit-modal-btn" class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded-lg font-medium transition">Cancel</button>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold px-4 py-2 rounded-lg transition">Update Question</button>
                </div>
            </form>
        </div>
    </div>

    <div id="notification-modal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-gray-800 max-w-sm w-full rounded-xl border border-gray-700 p-6 shadow-2xl text-center transform transition-all">
            <div id="notif-icon-container" class="mx-auto flex items-center justify-center h-12 w-12 rounded-full mb-4">
                <svg id="notif-icon" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"></svg>
            </div>
            <h3 id="notif-title" class="text-lg font-bold mb-2"></h3>
            <p id="notif-message" class="text-sm text-gray-400 mb-6"></p>
            <div id="notif-buttons" class="flex justify-center gap-3">
                <button type="button" id="notif-confirm-btn" class="bg-blue-500 hover:bg-blue-600 text-white font-bold px-4 py-2 rounded-lg transition">Confirm</button>
                <button type="button" id="notif-close-btn" class="bg-gray-700 hover:bg-gray-600 text-white font-bold px-4 py-2 rounded-lg transition">Close</button>
            </div>
        </div>
    </div>
    <div id="quiz-modal"
            class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">

            <div class="bg-gray-800 max-w-xl w-full rounded-xl p-6 border border-gray-700">

                <div class="flex justify-between mb-4">
                    <h3 class="text-xl font-bold text-purple-400">
                        Create Quiz
                    </h3>

                    <button type="button" id="close-quiz-modal-btn">
                        ✕
                    </button>
                </div>

                <form id="add-quiz-form" class="space-y-4">

                    <!-- NAME -->
                    <div>
                        <label class="text-gray-300">Name</label>
                        <input type="text"
                               id="quiz-name"
                               required
                               class="w-full bg-gray-700 p-2 rounded">
                    </div>

                    <!-- DESCRIPTION -->
                    <div>
                        <label class="text-gray-300">Description</label>
                        <textarea id="quiz-description"
                                  class="w-full bg-gray-700 p-2 rounded"></textarea>
                    </div>

                    <!-- DIFFICULTY -->
                    <div>
                        <label class="text-gray-300">Difficulty</label>
                        <select id="quiz-difficulty"
                                class="w-full bg-gray-700 p-2 rounded">
                            <option value="easy">Easy</option>
                            <option value="medium">Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>

                    <!-- CATEGORIES -->
                    <div>
                        <label class="text-gray-300">Categories</label>

                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <?php foreach($categories as $cat): ?>
                                <label class="flex items-center gap-2 text-gray-300">
                                    <input type="checkbox"
                                           class="quiz-category"
                                           value="<?= $cat['id'] ?>">
                                    <?= htmlspecialchars($cat['label']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- CUSTOM COUNT TOGGLE -->
                    <div class="flex items-center gap-2 text-gray-300">
                        <input type="checkbox" id="allow-custom-count">
                        <label>User chooses number of questions</label>
                    </div>

                    <!-- QUESTION COUNT -->
                    <div>
                        <label class="text-gray-300">Fixed question count</label>
                        <input type="number"
                               id="quiz-question-count"
                               min="1"
                               class="w-full bg-gray-700 p-2 rounded">
                    </div>

                    <!-- SUBMIT -->
                    <button type="submit"
                           class="bg-purple-600 px-4 py-2 rounded w-full">
                        Save Quiz
                    </button>

                </form>
            </div>
        </div>
    <div id="edit-quiz-modal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50">
        <div class="bg-gray-800 p-6 rounded-xl w-full max-w-lg">
            <h2 class="text-xl text-blue-400 mb-4">Edit Quiz</h2>

            <form id="edit-quiz-form">
                <input type="hidden" id="edit-quiz-id">

                <input id="edit-quiz-name" class="w-full mb-2 p-2 bg-gray-700 rounded">

                <textarea id="edit-quiz-description" class="w-full mb-2 p-2 bg-gray-700 rounded"></textarea>

                <select id="edit-quiz-difficulty" class="w-full mb-2 p-2 bg-gray-700 rounded">
                    <option value="easy">Easy</option>
                    <option value="medium">Medium</option>
                    <option value="hard">Hard</option>
                </select>

                <input type="number" id="edit-quiz-count" class="w-full mb-2 p-2 bg-gray-700 rounded">

                <div class="flex gap-2 mb-2">
                    <?php foreach($categories as $cat): ?>
                        <label>
                            <input type="checkbox" class="edit-quiz-cat" value="<?= $cat['id'] ?>">
                            <?= htmlspecialchars($cat['label']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <button class="bg-blue-600 px-4 py-2 rounded w-full">Save</button>
            </form>
        </div>
    </div>

    <script>
        // Modal State Managers - Create Form Overlay Logic
        const modal = document.getElementById('question-modal');
        const openModalBtn = document.getElementById('open-modal-btn');
        const closeModalBtn = document.getElementById('close-modal-btn');
        const cancelModalBtn = document.getElementById('cancel-modal-btn');
        const addForm = document.getElementById('add-question-form');
        
        const quizModal = document.getElementById('quiz-modal');

        document
        .getElementById('open-quiz-modal-btn')
        .addEventListener('click', () => {

            document
            .getElementById('add-quiz-form')
            .reset();

            quizModal.classList.remove('hidden');
        });

        document
        .getElementById('close-quiz-modal-btn')
        .addEventListener('click', () => {

            quizModal.classList.add('hidden');
        });                    

        openModalBtn.addEventListener('click', () => {
            addForm.reset(); 
            modal.classList.remove('hidden');
        });

        const hideModal = () => modal.classList.add('hidden');
        closeModalBtn.addEventListener('click', hideModal);
        cancelModalBtn.addEventListener('click', hideModal);

        // Modal State Managers - Update Form Overlay Logic
        const editModal = document.getElementById('edit-question-modal');
        const closeEditModalBtn = document.getElementById('close-edit-modal-btn');
        const cancelEditModalBtn = document.getElementById('cancel-edit-modal-btn');
        const editForm = document.getElementById('edit-question-form');

        const hideEditModal = () => editModal.classList.add('hidden');
        closeEditModalBtn.addEventListener('click', hideEditModal);
        cancelEditModalBtn.addEventListener('click', hideEditModal);

        // Toast Configurations & Dynamic Alert Engine Schema Setup
        const notifModal = document.getElementById('notification-modal');
        const notifIconContainer = document.getElementById('notif-icon-container');
        const notifIcon = document.getElementById('notif-icon');
        const notifTitle = document.getElementById('notif-title');
        const notifMessage = document.getElementById('notif-message');
        const notifButtons = document.getElementById('notif-buttons');

        const notifTypes = {
            info: { title: "Information", bg: "bg-blue-900/30", text: "text-blue-400", border: "border-blue-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />' },
            success_create: { title: "Question Created!", bg: "bg-emerald-950", text: "text-emerald-400", border: "border-emerald-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />' },
            success_update: { title: "Question Updated!", bg: "bg-blue-950", text: "text-blue-400", border: "border-blue-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />' },
            success_delete: { title: "Question Deleted!", bg: "bg-orange-950", text: "text-orange-400", border: "border-orange-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />' },
            info_delete: { title: "Delete Confirmation", bg: "bg-blue-900/30", text: "text-blue-400", border: "border-blue-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />' },
            success_quiz_create: {title: "Quiz Created!", bg: "bg-emerald-950", text: "text-purple-400", border: "border-purple-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />'},
            success_quiz_update: {title: "Quiz Updated!", bg: "bg-blue-950", text: "text-blue-400", border: "border-blue-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />' },
            success_quiz_delete: {title: "Quiz Deleted!", bg: "bg-orange-950", text: "text-orange-400", border: "border-orange-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />' },
            error: { title: "An Error Occurred", bg: "bg-red-950", text: "text-red-400", border: "border-red-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />' }
        };

        function showNotification(type, message, onConfirm = null) {
            const config = notifTypes[type] || notifTypes.info;
            
            notifIconContainer.className = `mx-auto flex items-center justify-center h-12 w-12 rounded-full mb-4 ${config.bg} ${config.text} border ${config.border}`;
            notifIcon.innerHTML = config.svg;
            notifTitle.innerText = config.title;
            notifTitle.className = `text-lg font-bold mb-2 ${config.text}`;
            notifMessage.innerText = message;
            notifButtons.innerHTML = '';

            if (onConfirm) {
                const cancelBtn = document.createElement('button');
                cancelBtn.innerText = "Cancel";
                cancelBtn.className = "bg-gray-700 hover:bg-gray-600 text-white font-medium px-4 py-2 rounded-lg transition w-full";
                cancelBtn.onclick = () => notifModal.classList.add('hidden');

                const actionBtn = document.createElement('button');
                actionBtn.innerText = "Confirm";
                actionBtn.className = "bg-red-600 hover:bg-red-700 text-white font-bold px-4 py-2 rounded-lg transition w-full";
                actionBtn.onclick = () => {
                    notifModal.classList.add('hidden');
                    onConfirm();
                };
                notifButtons.appendChild(cancelBtn);
                notifButtons.appendChild(actionBtn);
            } else {
                const okBtn = document.createElement('button');
                okBtn.innerText = "Ok";
                okBtn.className = "bg-gray-700 hover:bg-gray-600 text-white font-medium px-5 py-2 rounded-lg transition w-full";
                okBtn.onclick = () => notifModal.classList.add('hidden');
                notifButtons.appendChild(okBtn);
            }
            notifModal.classList.remove('hidden');
        }

        // Initialize Edit Modal view state with dataset parsing
        function initEditModal(button) {
            const id = button.dataset.id;
            const catId = button.dataset.category;
            const difficulty = button.dataset.difficulty;
            const text = button.dataset.text;
            let answers = [];
            
            try {
                answers = JSON.parse(button.dataset.answers);
            } catch (e) {
                console.error("Error parsing answers JSON", e);
            }

            document.getElementById('edit-question-id').value = id;
            document.getElementById('edit-modal-question-text').value = text;
            document.getElementById('edit-modal-category').value = catId;
            document.getElementById('edit-modal-difficulty').value = difficulty;

            if(Array.isArray(answers)) {
                answers.forEach((ans, index) => {
                    const inputAns = document.getElementById(`edit-answer-${index}`);
                    const radioAns = document.getElementById(`edit-radio-${index}`);
                    if (inputAns) inputAns.value = ans.text;
                    if (radioAns) radioAns.checked = (parseInt(ans.is_correct) === 1);
                });
            }
            editModal.classList.remove('hidden');
        }

        // Submitting Request - Handles the fetch sequence for modifying item sets
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('edit-question-id').value;
            const questionText = document.getElementById('edit-modal-question-text').value;
            const categoryId = document.getElementById('edit-modal-category').value;
            const difficulty = document.getElementById('edit-modal-difficulty').value;

            const answers = [
                document.getElementById('edit-answer-0').value,
                document.getElementById('edit-answer-1').value,
                document.getElementById('edit-answer-2').value
            ];
            
            const checkedRadio = document.querySelector('input[name="edit_correct_answer"]:checked');
            if(!checkedRadio) {
                showNotification('error', 'Please select a correct answer.');
                return;
            }
            const correctIndex = parseInt(checkedRadio.value);

            fetch('/api/questions/update_questions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, question_text: questionText, category_id: categoryId, difficulty, answers, correct_index: correctIndex })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    hideEditModal();
                    showNotification('success_update', 'The question has been updated successfully.');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('error', data.message || 'Error occurred during updating.');
                }
            })
            .catch(() => showNotification('error', 'An unexpected error occurred during update.'));
        });

        // Delete Operational Sequence Handlers
        function confirmDeleteQuestion(id) {
            showNotification('info_delete', 'Are you absolutely sure you want to delete this question? This action cannot be undone.', () => {
                executeDelete(id);
            });
        }

        function executeDelete(id) {
            fetch('/api/questions/delete_questions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const row = document.getElementById(`question-row-${id}`);
                    if (row) row.remove();
                    showNotification('success_delete', 'The question has been deleted successfully.');
                } else {
                    showNotification('error', data.message || 'Error while trying to delete.');
                }
            })
            .catch(() => showNotification('error', 'An error occurred during deletion.'));
        }

        // Submitting Request - Handles creation stream execution
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const questionText = document.getElementById('modal-question-text').value;
            const categoryId = document.getElementById('modal-category').value;
            const difficulty = document.getElementById('modal-difficulty').value;

            const answers = [
                document.getElementById('answer-0').value,
                document.getElementById('answer-1').value,
                document.getElementById('answer-2').value
            ];
            
            const checkedRadio = document.querySelector('input[name="correct_answer"]:checked');
            const correctIndex = checkedRadio ? parseInt(checkedRadio.value) : 0;

            fetch('/api/questions/add_questions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question_text: questionText, category_id: categoryId, difficulty, answers, correct_index: correctIndex })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    hideModal();
                    showNotification('success_create', 'The question and its options were successfully saved.');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('error', data.message || 'Failed to create the question.');
                }
            })
            .catch(() => showNotification('error', 'An error occurred during save.'));
        });

        const quizForm = document.getElementById('add-quiz-form');

        quizForm.addEventListener('submit', function (e) {
            e.preventDefault();

            try {
                const categories = [];
                document.querySelectorAll('.quiz-category:checked')
                    .forEach(c => categories.push(c.value));

                const allowCustom = document.getElementById('allow-custom-count').checked;

                const questionCountInput = document.getElementById('quiz-question-count');

                const payload = {
                    name: document.getElementById('quiz-name').value,
                    description: document.getElementById('quiz-description').value,
                    difficulty: document.getElementById('quiz-difficulty').value,
                    question_count: allowCustom ? null : (questionCountInput.value || null),
                    allow_custom_question_count: allowCustom,
                    categories
                };

                console.log("QUIZ PAYLOAD =>", payload); // 👈 DEBUG IMPORTANT

                fetch('/api/quizzes/add_quizzes.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    console.log("QUIZ RESPONSE =>", data); // 👈 DEBUG IMPORTANT

                    if (data.status === 'success') {
                        quizModal.classList.add('hidden');
                        showNotification('success_quiz_create', 'Quiz created successfully');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification('error', data.message || 'Error creating quiz');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showNotification('error', 'Network error while creating quiz');
                });

            } catch (err) {
                console.error("QUIZ FORM ERROR:", err);
                showNotification('error', 'JS error in quiz form');
            }
        });

        document.getElementById('edit-quiz-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const categories = [];
            document.querySelectorAll('.edit-quiz-cat:checked')
                .forEach(c => categories.push(c.value));

            const payload = {
                id: document.getElementById('edit-quiz-id').value,
                name: document.getElementById('edit-quiz-name').value,
                description: document.getElementById('edit-quiz-description').value,
                difficulty: document.getElementById('edit-quiz-difficulty').value,
                question_count: document.getElementById('edit-quiz-count').value,
                categories: categories
            };

            const res = await fetch('/api/quizzes/update_quizzes.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            });

            const data = await res.json();

            if (data.status === 'success') {
                document.getElementById('edit-quiz-modal').classList.add('hidden');

                showNotification('success_quiz_update', 'Quiz updated successfully.');
                setTimeout(() => location.reload(), 1200);
            } else {
                showNotification('error', data.message || 'Error updating quiz.');
            }
        });

        function editQuiz(btn) {
            document.getElementById('edit-quiz-id').value = btn.dataset.id;
            document.getElementById('edit-quiz-name').value = btn.dataset.name;
            document.getElementById('edit-quiz-description').value = btn.dataset.description;
            document.getElementById('edit-quiz-difficulty').value = btn.dataset.difficulty;
            document.getElementById('edit-quiz-count').value = btn.dataset.count;

            const selected = (btn.dataset.categories || "").split(',');

            document.querySelectorAll('.edit-quiz-cat').forEach(cb => {
                cb.checked = selected.includes(cb.value);
        });

            document.getElementById('edit-quiz-modal').classList.remove('hidden');
        }

        function deleteQuiz(id) {
            showNotification('info_delete', 'Are you sure you want to delete this quiz? This action cannot be undone.',
                async () => {

                    try {
                        const res = await fetch('/api/quizzes/delete_quizzes.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ id })
                        });
                        const data = await res.json();

                        if (data.status === 'success') {
                            showNotification('success_quiz_delete','Quiz deleted successfully.');
                            setTimeout(() => location.reload(), 1200);

                        } else {
                    showNotification('error', data.message || 'Error deleting quiz.');
                        }

                    } catch (err) {
                        showNotification('error', 'Network error while deleting quiz.');
                    }
                }
            );
        }

    </script>

<?php require_once __DIR__ . '/components/footer.php'; ?>