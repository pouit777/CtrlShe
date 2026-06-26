<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /header.php');
    exit;
}

require_once __DIR__ . '/config/db.php';
$categories = $pdo->query('SELECT * FROM categories')->fetchAll();

$query = "
    SELECT q.*, c.label as category_label,
           (SELECT JSON_ARRAYAGG(JSON_OBJECT('id', a.id, 'text', a.answer_text, 'is_correct', a.is_correct))
            FROM (SELECT * FROM answers WHERE question_id = q.id ORDER BY id ASC) a) as answers_json
    FROM questions q 
    LEFT JOIN categories c ON q.category_id = c.id
    ORDER BY q.id DESC
";
$questions = $pdo->query($query)->fetchAll();

$page_title = "brainSKwiz - Admin Questions";
require_once __DIR__ . '/components/header.php';
?>

    <div class="titleBoxAdmin">
        <div>
            <h1 class="titleText">Question management</h1>
            <button id="open-modal-btn" class="btn">
                + Add New Question
            </button>
        </div>
    </div>

    <div class="table">
        <table>
            <thead class="tableTitle">
                <tr>
                    <th>ID</th>
                    <th>Question Text</th>
                    <th>Category</th>
                    <th>Difficulty</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="questions-table-body">
                <?php foreach($questions as $q): ?>
                    <tr id="question-row-<?php echo $q['id']; ?>">
                        <td>#<?php echo $q['id']; ?></td>
                        <td><?php echo htmlspecialchars($q['question_text'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <span>
                                <?php echo htmlspecialchars($q['category_label'] ?? 'No category', ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </td>
                        <td>
                            <span class="<?php echo $q['difficulty']?>">
                                <?php echo htmlspecialchars($q['difficulty'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </td>
                        <td>
                            <div>
                                <button 
                                    data-id="<?php echo $q['id']; ?>"
                                    data-category="<?php echo $q['category_id']; ?>"
                                    data-difficulty="<?php echo htmlspecialchars($q['difficulty'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-text="<?php echo htmlspecialchars($q['question_text'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-answers="<?php echo htmlspecialchars($q['answers_json'] ?? '[]', ENT_QUOTES, 'UTF-8'); ?>"
                                    onclick="initEditModal(this)"
                                    title="Edit Question"
                                    class="editBtn">
                                    <span class="material-icons">edit</span>
                                </button>
                                <button 
                                    onclick="confirmDeleteQuestion(<?php echo $q['id']; ?>)" 
                                    title="Delete Question"
                                    class="deleteBtn">
                                    <span class="material-icons">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal New Question -->
    <div id="question-modal" class="modal hidden">
        <div class="modal-content">
            <div class="titleText modal-header">
                <h3>Add a New Question</h3>
                <button class="closeBtn" id="close-modal-btn">&times;</button>
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
                    <div style="margin: 1rem">
                        <label>Answers Options (Select the correct one)</label>
                        <button class="btn" type="button" onclick="addAnswerField('add-answers-container', 'correct_answer')">+ Add Option</button>
                    </div>
                    <div id="add-answers-container" class="space-y-3"></div>
                </div>
                <div class="modal-btn">
                    <button type="button" class="inputField" id="cancel-modal-btn">Cancel</button>
                    <button type="submit" class="btn">Save Question</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal New Question -->
    <div id="edit-question-modal" class="modal hidden">
        <div class="modal-content">
            <div class="titleText modal-header">
                <h3>Edit Question Detail</h3>
                <button id="close-edit-modal-btn" class="closeBtn">&times;</button>
            </div>
            <form id="edit-question-form" >
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
                    <div style="margin: 1rem">
                        <label>Answers Options (Check the correct one)</label>
                        <button type="button" class="btn" onclick="addAnswerField('edit-answers-container', 'edit_correct_answer')" class="text-xs bg-blue-500/20 text-blue-400 hover:bg-blue-500 hover:text-white px-2 py-1 rounded font-bold transition">+ Add Option</button>
                    </div>
                    <div id="edit-answers-container" class="space-y-3"></div>
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
            <div id="notif-buttons" class="flex justify-center gap-3"></div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('question-modal');
        const openModalBtn = document.getElementById('open-modal-btn');
        const closeModalBtn = document.getElementById('close-modal-btn');
        const cancelModalBtn = document.getElementById('cancel-modal-btn');
        const addForm = document.getElementById('add-question-form');
        const addAnswersContainer = document.getElementById('add-answers-container');
        const editAnswersContainer = document.getElementById('edit-answers-container');

        // --- FONCTION DE GESTION DYNAMIQUE DES CHAMPS (MIN 2, MAX 7) ---
        function createAnswerRowHtml(radioName, textValue = '', isCorrect = false) {
            const div = document.createElement('div');
            div.className = "flex items-center gap-3 answer-row";
            
            const isChecked = isCorrect ? 'checked' : '';
            
            div.innerHTML = `
                <input type="radio" name="${radioName}" ${isChecked} class="w-4 h-4 text-emerald-500 bg-gray-700 border-gray-600 focus:ring-emerald-500 input-radio-target">
                <input type="text" required value="${textValue.replace(/"/g, '&quot;')}" placeholder="Answer option" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-1.5 text-white focus:outline-none focus:border-emerald-500 transition input-text-target">
                <button type="button" onclick="removeAnswerField(this)" class="text-gray-500 hover:text-red-400 font-bold px-2 text-lg transition" title="Remove option">&times;</button>
            `;
            return div;
        }

        function addAnswerField(containerId, radioName, textValue = '', isCorrect = false) {
            const container = document.getElementById(containerId);
            if (container.children.length >= 7) {
                showNotification('error', 'Maximum 7 options allowed.');
                return;
            }
            container.appendChild(createAnswerRowHtml(radioName, textValue, isCorrect));
            updateRadioValues(container);
        }

        function removeAnswerField(button) {
            const container = button.parentElement.parentElement;
            if (container.children.length <= 2) {
                showNotification('error', 'Minimum 2 options are required.');
                return;
            }
            button.parentElement.remove();
            updateRadioValues(container);
        }

        function updateRadioValues(container) {
            // Assigne l'index dynamique aux boutons radio (0, 1, 2, ...)
            Array.from(container.children).forEach((row, idx) => {
                const radio = row.querySelector('.input-radio-target');
                if(radio) radio.value = idx;
            });
        }

        // Initialisation de la modale d'ajout avec 2 champs vides
        openModalBtn.addEventListener('click', () => {
            addForm.reset(); 
            addAnswersContainer.innerHTML = '';
            addAnswerField('add-answers-container', 'correct_answer', '', true);
            addAnswerField('add-answers-container', 'correct_answer', '', false);
            modal.classList.remove('hidden');
        });

        const hideModal = () => modal.classList.add('hidden');
        closeModalBtn.addEventListener('click', hideModal);
        cancelModalBtn.addEventListener('click', hideModal);

        const editModal = document.getElementById('edit-question-modal');
        const closeEditModalBtn = document.getElementById('close-edit-modal-btn');
        const cancelEditModalBtn = document.getElementById('cancel-edit-modal-btn');
        const editForm = document.getElementById('edit-question-form');

        const hideEditModal = () => editModal.classList.add('hidden');
        closeEditModalBtn.addEventListener('click', hideEditModal);
        cancelEditModalBtn.addEventListener('click', hideEditModal);

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

            // Remplissage dynamique des réponses existantes
            editAnswersContainer.innerHTML = '';
            if(Array.isArray(answers) && answers.length > 0) {
                answers.forEach((ans) => {
                    addAnswerField('edit-answers-container', 'edit_correct_answer', ans.text, parseInt(ans.is_correct) === 1);
                });
            } else {
                // Secours si aucune réponse trouvée
                addAnswerField('edit-answers-container', 'edit_correct_answer', '', true);
                addAnswerField('edit-answers-container', 'edit_correct_answer', '', false);
            }
            editModal.classList.remove('hidden');
        }

        // --- ENVOI ÉDITION ---
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('edit-question-id').value;
            const questionText = document.getElementById('edit-modal-question-text').value;
            const categoryId = document.getElementById('edit-modal-category').value;
            const difficulty = document.getElementById('edit-modal-difficulty').value;

            // Collecte dynamique de toutes les valeurs textes présentes
            const textInputs = editAnswersContainer.querySelectorAll('.input-text-target');
            const answers = Array.from(textInputs).map(input => input.value);
            
            const checkedRadio = editAnswersContainer.querySelector('input[name="edit_correct_answer"]:checked');
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

        // --- ENVOI CRÉATION ---
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const questionText = document.getElementById('modal-question-text').value;
            const categoryId = document.getElementById('modal-category').value;
            const difficulty = document.getElementById('modal-difficulty').value;

            // Collecte dynamique de toutes les valeurs textes présentes
            const textInputs = addAnswersContainer.querySelectorAll('.input-text-target');
            const answers = Array.from(textInputs).map(input => input.value);
            
            const checkedRadio = addAnswersContainer.querySelector('input[name="correct_answer"]:checked');
            if(!checkedRadio) {
                showNotification('error', 'Please select a correct answer.');
                return;
            }
            const correctIndex = parseInt(checkedRadio.value);

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
    </script>

<?php require_once __DIR__ . '/components/footer.php'; ?>