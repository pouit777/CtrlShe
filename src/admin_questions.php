<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.php');
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

        <div class="flex flex-col sm:flex-row justify-between items-center text-center sm:text-left gap-4 mb-8 bg-gray-800 p-4 md:p-6 rounded-xl border border-gray-700 shadow-lg">
            <div>
                <h1 class="text-2xl md:text-3xl mb-5 md:mb-3 font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-emerald-500 via-teal-400 to-cyan-400 drop-shadow-sm">Question management</h1>
                <p class="text-xs md:text-sm mb-3 md:mb-0 text-gray-400 mt-1">
                    Logged in as : <strong class="text-cyan-400"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8'); ?>)</strong>
                </p>
            </div>
            <div class="w-full sm:w-auto flex justify-end">
                <button id="open-modal-btn" class="w-full sm:w-auto bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-gray-950 font-bold py-2 px-4 rounded-lg shadow-md transition transform hover:-translate-y-0.5 text-sm md:text-base">
                    + Add New Question
                </button>
            </div>
        </div>

        <div class="bg-gray-800 rounded-xl border border-gray-700 shadow-xl overflow-hidden overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                    <tr class="bg-gray-700/50 text-gray-400 uppercase text-xs font-mono border-b border-gray-700">
                        <th class="p-4 w-20">ID</th>
                        <th class="p-4">Question Text</th>
                        <th class="p-4 w-40">Category</th>
                        <th class="p-4 w-32">Difficulty</th>
                        <th class="p-4 w-28 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="questions-table-body">
                    <?php foreach($questions as $q): ?>
                        <tr id="question-row-<?php echo $q['id']; ?>" class="border-b border-gray-700/50 hover:bg-gray-700/30 transition">
                            <td class="p-4 font-mono text-cyan-400">#<?php echo $q['id']; ?></td>
                            <td class="p-4 text-gray-200 font-medium text-sm md:text-base"><?php echo htmlspecialchars($q['question_text'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="p-4">
                                <span class="bg-gray-900 px-2 py-1 rounded text-xs text-gray-400 border border-gray-700 whitespace-nowrap">
                                    <?php echo htmlspecialchars($q['category_label'] ?? 'No category', ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
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
                                    title="Edit Question"
                                    class="inline-flex items-center gap-1 bg-blue-900/30 hover:bg-blue-600 border border-blue-800 hover:border-blue-500 text-blue-300 hover:text-white px-2.5 py-1.5 rounded-lg text-xs font-semibold transition duration-150 shadow-sm">
                                    Edit
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                                <button 
                                    onclick="confirmDeleteQuestion(<?php echo $q['id']; ?>)" 
                                    title="Delete Question"
                                    class="inline-flex items-center gap-1 bg-red-900/30 hover:bg-red-600 border border-red-800 hover:border-red-500 text-red-300 hover:text-white px-2.5 py-1.5 rounded-lg text-xs font-semibold transition duration-150 shadow-sm">
                                    Delete
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
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
            <div id="notif-buttons" class="flex justify-center gap-3"></div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('question-modal');
        const openModalBtn = document.getElementById('open-modal-btn');
        const closeModalBtn = document.getElementById('close-modal-btn');
        const cancelModalBtn = document.getElementById('cancel-modal-btn');
        const addForm = document.getElementById('add-question-form');

        openModalBtn.addEventListener('click', () => {
            addForm.reset(); 
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
    </script>

<?php require_once __DIR__ . '/components/footer.php'; ?>