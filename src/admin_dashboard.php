<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /index.php');
    exit;
}

require_once __DIR__ . '/config/db.php';

// Retrieve all product categories for selection fields
$categories = $pdo->query('SELECT * FROM categories')->fetchAll();

// Fetch quizzes with their aggregated category IDs and category labels via many-to-many relationship
$quizzes = $pdo->query("SELECT 
        q.*,
        GROUP_CONCAT(c.id) AS category_ids,
        GROUP_CONCAT(c.label SEPARATOR ', ') AS category_labels
    FROM quizzes q
    LEFT JOIN quiz_categories qc ON q.id = qc.quiz_id
    LEFT JOIN categories c ON c.id = qc.category_id
    GROUP BY q.id
    ORDER BY q.id DESC")->fetchAll();

// Retrieve all questions alongside their structured answers payload inside a single JSON array
$query = "
    SELECT q.*, c.label as category_label,
           (SELECT JSON_ARRAYAGG(JSON_OBJECT('id', a.id, 'text', a.answer_text, 'is_correct', a.is_correct))
            FROM (SELECT * FROM answers WHERE question_id = q.id ORDER BY id ASC) a) as answers_json
    FROM questions q 
    LEFT JOIN categories c ON q.category_id = c.id
    ORDER BY q.id DESC
";
$questions = $pdo->query($query)->fetchAll();

// --- VIEW CONFIGURATION ---
$page_title = "brainSKwiz - Admin Panel";
require_once __DIR__ . '/components/header.php';
?>

<div class="titleBoxAdmin">
    <div>
        <h1 class="bigTitle">Quiz management</h1>
        <div class="modal-btn">
            <button id="open-quiz-modal-btn" class="btn m-auto">+ Add New Quiz</button>
        </div>
    </div>
</div>

<div class="table-wrapper">
    <table class="whitespace-normal">
        <thead class="tableTitle">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Difficulty</th>
                <th>Questions</th>
                <th>Custom Count</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($quizzes as $quiz): ?>    
            <tr>
                <td>#<?= (int)$quiz['id']; ?></td>
                <td><?= htmlspecialchars($quiz['name']) ?></td>
                <td>
                    <span class="<?= htmlspecialchars($quiz['difficulty']) ?>">
                        <?= htmlspecialchars($quiz['difficulty']) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($quiz['category_labels'] ?? 'None') ?></td>
                <td><?= htmlspecialchars($quiz['question_count'] ?? 'Variable') ?></td>
                <td><?= $quiz['allow_custom_question_count'] ? 'Yes' : 'No' ?></td>
                <td class="align-middle">
                    <div class="flex items-center gap-2 justify-start h-full">
                        <button
                            onclick="editQuiz(this)"
                            data-id="<?= (int)$quiz['id'] ?>"
                            data-name="<?= htmlspecialchars($quiz['name'], ENT_QUOTES, 'UTF-8') ?>"
                            data-description="<?= htmlspecialchars($quiz['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-categories="<?= htmlspecialchars($quiz['category_ids'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-difficulty="<?= htmlspecialchars($quiz['difficulty'], ENT_QUOTES, 'UTF-8') ?>"
                            data-count="<?= htmlspecialchars($quiz['question_count'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            data-custom="<?= htmlspecialchars($quiz['allow_custom_question_count'], ENT_QUOTES, 'UTF-8') ?>"
                            class="editBtn">
                            <span class="material-icons">edit</span>
                        </button>

                        <button onclick="deleteQuiz(<?= (int)$quiz['id'] ?>)" class="deleteBtn">
                            <span class="material-icons">delete</span>
                        </button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="notification-modal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-gray-800 max-w-sm w-full rounded-xl border border-gray-700 p-6 shadow-2xl text-center transform transition-all">
        <div id="notif-icon-container" class="mx-auto flex items-center justify-center h-12 w-12 rounded-full mb-4">
            <svg id="notif-icon" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"></svg>
        </div>
        <h3 id="notif-title" class="text-lg font-bold mb-2"></h3>
        <p id="notif-message" class="text-sm text-gray-400 mb-6"></p>
        <div id="notif-buttons" class="flex justify-center gap-3"></div>
    </div>
</div>

<div id="quiz-modal" class="modal hidden">
    <div class="modal-content">
        <div class="titleText modal-header">
            <h3>Create Quiz</h3>
            <button id="close-quiz-modal-btn" class="closeBtn closeBtn font-large text-secondary">&times;</button>
        </div>
        <form id="add-quiz-form" class="flex flex-col gap-2">
            <div>
                <label>Name</label>
                <input type="text" id="quiz-name" required class="inputField">
            </div>
            <div>
                <label>Description</label>
                <textarea id="quiz-description" class="inputField"></textarea>
            </div>
            <div>
                <label>Difficulty</label>
                <select id="quiz-difficulty" class="inputField">
                    <option value="easy">Easy</option>
                    <option value="medium">Medium</option>
                    <option value="hard">Hard</option>
                </select>
            </div>
            <div>
                <label>Categories</label>
                <div class="categories-container">
                    <?php foreach($categories as $cat): ?>
                        <label class="category-item">
                            <input type="checkbox" class="quiz-category" value="<?= (int)$cat['id'] ?>">
                            <?= htmlspecialchars($cat['label']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="allow-custom-count">
                <label>User chooses number of questions</label>
            </div>
            <div style="margin-bottom: 1rem;">
                <label>Fixed question count</label>
                <input type="number" id="quiz-question-count" min="1" class="inputField">
            </div>
            <button type="submit" class="btn">Save Quiz</button>
        </form>
    </div>
</div>

<div id="edit-quiz-modal" class="modal hidden">
    <div class="modal-content">
        <div class="titleText modal-header">
            <h3>Edit Quiz</h3>
            <button id="close-edit-quiz-modal-btn" class="closeBtn font-large text-secondary">&times;</button>
        </div>
        <form id="edit-quiz-form">
            <input type="hidden" id="edit-quiz-id">
            <div style="margin-bottom: 1rem;">
                <label>Name</label>
                <input id="edit-quiz-name" class="inputField">
            </div>
            <div style="margin-bottom: 1rem;">
                <label>Description</label>
                <textarea id="edit-quiz-description" class="inputField"></textarea>
            </div>
            <div style="margin-bottom: 1rem;">
                <label>Difficulty</label>
                <select id="edit-quiz-difficulty" class="inputField">
                    <option value="easy">Easy</option>
                    <option value="medium">Medium</option>
                    <option value="hard">Hard</option>
                </select>
            </div>
            <div style="margin-bottom: 1rem;">
                <label>Fixed question count</label>
                <input type="number" id="edit-quiz-count" class="inputField">
            </div>
            <div>
                <label>Categories</label>
                <div class="categories-container">
                    <?php foreach($categories as $cat): ?>
                        <label class="category-item">
                            <input type="checkbox" class="edit-quiz-cat" value="<?= (int)$cat['id'] ?>">
                            <?= htmlspecialchars($cat['label']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-btn">
                <button class="btn">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
    // --- MODAL POPUP VISIBILITY MANAGERS ---
    const quizModal = document.getElementById('quiz-modal');
    document.getElementById('open-quiz-modal-btn').addEventListener('click', () => {
        document.getElementById('add-quiz-form').reset();
        quizModal.classList.remove('hidden');
    });
    document.getElementById('close-quiz-modal-btn').addEventListener('click', () => { quizModal.classList.add('hidden'); });
    document.getElementById('close-edit-quiz-modal-btn').addEventListener('click', () => { document.getElementById('edit-quiz-modal').classList.add('hidden'); });  

    // --- POPUP NOTIFICATION CONFIGURATIONS ---
    const notifModal = document.getElementById('notification-modal');
    const notifIconContainer = document.getElementById('notif-icon-container');
    const notifIcon = document.getElementById('notif-icon');
    const notifTitle = document.getElementById('notif-title');
    const notifMessage = document.getElementById('notif-message');
    const notifButtons = document.getElementById('notif-buttons');

    const notifTypes = {
        info: { title: "Information", bg: "bg-blue-900/30", text: "text-blue-400", border: "border-blue-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />' },
        info_delete: { title: "Delete Confirmation", bg: "bg-blue-900/30", text: "text-blue-400", border: "border-blue-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />' },
        success_quiz_create: {title: "Quiz Created!", bg: "bg-emerald-950", text: "text-purple-400", border: "border-purple-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />'},
        success_quiz_update: {title: "Quiz Updated!", bg: "bg-blue-950", text: "text-blue-400", border: "border-blue-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />' },
        success_quiz_delete: {title: "Quiz Deleted!", bg: "bg-orange-950", text: "text-orange-400", border: "border-orange-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />' },
        error: { title: "An Error Occurred", bg: "bg-red-950", text: "text-red-400", border: "border-red-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />' }
    };

    // Global utility function displaying success, failure notifications or dual-button context prompts
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

    // --- SUBMIT COMPONENT: ADD QUIZ BACKEND CALL ---
    document.getElementById('add-quiz-form').addEventListener('submit', function (e) {
        e.preventDefault();
        try {
            const categories = [];
            document.querySelectorAll('.quiz-category:checked').forEach(c => categories.push(c.value));
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

            fetch('/api/quizzes/add_quizzes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    quizModal.classList.add('hidden');
                    showNotification('success_quiz_create', 'Quiz created successfully');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('error', data.message || 'Error creating quiz');
                }
            })
            .catch(err => {
                showNotification('error', 'Network error while creating quiz');
            });
        } catch (err) {
            showNotification('error', 'JS error in quiz form');
        }
    });

    // --- SUBMIT COMPONENT: UPDATE QUIZ BACKEND CALL ---
    document.getElementById('edit-quiz-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = document.getElementById('edit-quiz-name').value.trim();
        if (name === '') {
            showNotification('error', 'Quiz name is required');
            return;
        }

        const categories = [];
        document.querySelectorAll('.edit-quiz-cat:checked').forEach(c => categories.push(c.value));
        if (categories.length === 0) {
            showNotification('error', 'At least one category is required');
            return;
        }

        const payload = {
            id: document.getElementById('edit-quiz-id').value,
            name: name,
            description: document.getElementById('edit-quiz-description').value,
            difficulty: document.getElementById('edit-quiz-difficulty').value,
            question_count: document.getElementById('edit-quiz-count').value || null,
            categories: categories
        };

        try {
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
        } catch (err) {
            showNotification('error', 'Network error while updating quiz.');
        }
    });

    // --- INTERACTION COMPONENT: EDIT DATA POPULATION ---
    // Extract row configuration data directly from element properties to assign into update fields
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

    // --- INTERACTION COMPONENT: DELETION HANDLING ---
    function deleteQuiz(id) {
        showNotification('info_delete', 'Are you sure you want to delete this quiz? This action cannot be undone.',
            async () => {
                try {
                    const res = await fetch('/api/quizzes/delete_quizzes.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
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