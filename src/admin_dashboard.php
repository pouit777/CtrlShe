<?php
session_start();

// Page Protection Access Guard
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /index.php');
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

        <div class="titleBoxAdmin">
            <div>
                <h1 class="titleText">Quiz Management</h1>
            </div>
            <div class="modal-btn">
                <button id="open-quiz-modal-btn" class="btn">
                    + Add New Quiz
                </button>
            </div>
        </div>
    
    <div class="table w-full overflow-x-auto">
        <table class="whitespace-nowrap">
            <thead class="tableTitle">
                <tr>
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
                    <td>
                        <?= htmlspecialchars($quiz['name']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($quiz['difficulty']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($quiz['category_labels'] ?? 'None') ?>
                    </td>

                    <td>
                        <?= $quiz['question_count'] ?? 'Variable' ?>
                    </td>

                    <td>
                        <?= $quiz['allow_custom_question_count'] ? 'Yes' : 'No' ?>
                    </td>

                    <td>

                        <button
                            onclick="editQuiz(this)"
                            data-id="<?= $quiz['id'] ?>"
                            data-name="<?= htmlspecialchars($quiz['name']) ?>"
                            data-description="<?= htmlspecialchars($quiz['description']) ?>"
                            data-categories="<?= $quiz['category_ids'] ?>"
                            data-difficulty="<?= $quiz['difficulty'] ?>"
                            data-count="<?= $quiz['question_count'] ?>"
                            data-custom="<?= $quiz['allow_custom_question_count'] ?>"
                            class="editBtn">
                            <span class="material-icons">edit</span>
                        </button>

                        <button
                            onclick="deleteQuiz(<?= $quiz['id'] ?>)"
                            class="deleteBtn">
                            <span class="material-icons">delete</span>
                        </button>

                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="question-modal" class="modal hidden">
        <div class="modal-content">
            <div class="titleText modal-header">
                <h3>Add a New Question</h3>
                <button id="close-modal-btn" class="closeBtn">&times;</button>
            </div>
            <form id="add-question-form">
                <div>
                    <label>Question Text</label>
                    <textarea id="modal-question-text" required rows="2" placeholder="Ex: What does CSS stand for?" class="inputField"></textarea>
                </div>
                <div>
                    <div>
                        <label>Category</label>
                        <select id="modal-category" required class="inputField">
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['label'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Difficulty</label>
                        <select id="modal-difficulty" required class="inputField">
                            <option value="easy">Easy</option>
                            <option value="medium" selected>Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>
                </div>
                <hr class="border-gray-700 my-2">
                <div>
                    <label>Answers Options (Select the correct one)</label>
                    <div>
                        <?php for($i = 0; $i < 3; $i++): ?>
                        <div class="answer-row">
                            <input type="radio" name="correct_answer" value="<?php echo $i; ?>" <?php echo $i === 0 ? 'checked' : ''; ?>>
                            <input type="text" id="answer-<?php echo $i; ?>" required placeholder="Answer option <?php echo $i+1; ?>" class="inputField">
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="modal-btn">
                    <button type="button" id="cancel-modal-btn" class="inputField">Cancel</button>
                    <button type="submit" class="btn">Save Question</button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-question-modal" class="modal hidden">
        <div class="modal-content">
            <div class="titleText modal-header">
                <h3>Edit Question Detail</h3>
                <button id="close-edit-modal-btn" class="closeBtn">&times;</button>
            </div>
            <form id="edit-question-form">
                <input type="hidden" id="edit-question-id">
                <div>
                    <label>Question Text</label>
                    <textarea id="edit-modal-question-text" required rows="2" class="inputField"></textarea>
                </div>
                <div>
                    <div>
                        <label>Category</label>
                        <select id="edit-modal-category" required class="inputField">
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['label'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Difficulty</label>
                        <select id="edit-modal-difficulty" required class="inputField">
                            <option value="easy">Easy</option>
                            <option value="medium">Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>
                </div>
                <hr class="border-gray-700 my-2">
                <div>
                    <label>Answers Options (Check the correct one)</label>
                    <div>
                        <?php for($i = 0; $i < 3; $i++): ?>
                        <div class="answer-row">
                            <input type="radio" name="edit_correct_answer" value="<?php echo $i; ?>" id="edit-radio-<?php echo $i; ?>">
                            <input type="text" id="edit-answer-<?php echo $i; ?>" required class="inputField">
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="modal-btn">
                    <button type="button" id="cancel-edit-modal-btn" class="inputField">Cancel</button>
                    <button type="submit" class="btn">Update Question</button>
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

    <div id="quiz-modal"class="modal hidden">
        <div class="modal-content">

            <div class="titleText modal-header">
                <h3>Create Quiz</h3>
                <button id="close-quiz-modal-btn" class="closeBtn">&times;</button>
            </div>

            <form id="add-quiz-form">

                <!-- NAME -->
                <div>
                    <label>Name</label>
                    <input type="text"
                            id="quiz-name"
                            required
                            class="inputField">
                </div>

                <!-- DESCRIPTION -->
                <div>
                    <label>Description</label>
                    <textarea id="quiz-description"
                                class="inputField"></textarea>
                </div>

                <!-- DIFFICULTY -->
                <div>
                    <label>Difficulty</label>
                    <select id="quiz-difficulty"
                            class="inputField">
                        <option value="easy">Easy</option>
                        <option value="medium">Medium</option>
                        <option value="hard">Hard</option>
                    </select>
                </div>

                <!-- CATEGORIES -->
                <div>
                    <label>Categories</label>

                    <div class="categories-container">
                        <?php foreach($categories as $cat): ?>
                            <label class="category-item">
                                <input type="checkbox"
                                        class="quiz-category"
                                        value="<?= $cat['id'] ?>">
                                <?= htmlspecialchars($cat['label']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- CUSTOM COUNT TOGGLE -->
                <div class="modal-btn">
                    <input type="checkbox" id="allow-custom-count">
                    <label>User chooses number of questions</label>
                </div>

                <!-- QUESTION COUNT -->
                <div style="margin-bottom: 1rem;">
                    <label>Fixed question count</label>
                    <input type="number"
                            id="quiz-question-count"
                            min="1"
                            class="inputField">
                </div>

                <!-- SUBMIT -->
                <button type="submit" class="btn">Save Quiz</button>
            </form>
        </div>
    </div>

    <div id="edit-quiz-modal" class="modal hidden">
        <div class="modal-content">
            <div class="titleText modal-header">
                <h3>Edit Quiz</h3>
                <button id="close-edit-quiz-modal-btn" class="closeBtn">&times;</button>
            </div>

            <form id="edit-quiz-form">
                <input type="hidden" id="edit-quiz-id">

                <input id="edit-quiz-name" class="inputField">
                <textarea id="edit-quiz-description" class="inputField"></textarea>

                <select id="edit-quiz-difficulty" class="inputField">
                    <option value="easy">Easy</option>
                    <option value="medium">Medium</option>
                    <option value="hard">Hard</option>
                </select>

                <input type="number" id="edit-quiz-count" class="inputField">

                <div class="categories-container">
                    <?php foreach($categories as $cat): ?>
                        <label class="category-item">
                            <input type="checkbox" class="edit-quiz-cat" value="<?= $cat['id'] ?>">
                            <?= htmlspecialchars($cat['label']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <button class="btn">Save</button>
            </form>
        </div>
    </div>

<script>
    // Modal State Managers - Quiz Overlay Logic
    const quizModal = document.getElementById('quiz-modal');

    document.getElementById('open-quiz-modal-btn').addEventListener('click', () => {
        document.getElementById('add-quiz-form').reset();
        quizModal.classList.remove('hidden');
    });

    document.getElementById('close-quiz-modal-btn').addEventListener('click', () => {
        quizModal.classList.add('hidden');
    });
    
    document.getElementById('close-edit-quiz-modal-btn').addEventListener('click', () => {
        document.getElementById('edit-quiz-modal').classList.add('hidden');
    });  

    // Toast Configurations & Dynamic Alert Engine Schema Setup
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

    // Submitting Request - Handles quiz creation stream execution
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
                console.error(err);
                showNotification('error', 'Network error while creating quiz');
            });

        } catch (err) {
            console.error("QUIZ FORM ERROR:", err);
            showNotification('error', 'JS error in quiz form');
        }
    });

    // Submitting Request - Handles quiz update execution
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

    // Initialize Edit Quiz Modal view state
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

    // Delete Quiz Operational Sequence Handler
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