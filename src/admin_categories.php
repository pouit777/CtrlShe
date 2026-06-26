<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /index.php');
    exit;
}

require_once __DIR__ . '/config/db.php';

$query = '
    SELECT c.*, COUNT(q.id) AS total_questions 
    FROM categories c
    LEFT JOIN questions q ON q.category_id = c.id
    GROUP BY c.id
    ORDER BY c.id DESC
';
$orphan_query = "
    SELECT q.id, q.question_text, q.difficulty, q.category_id, c.label AS current_category_label 
    FROM questions q 
    LEFT JOIN categories c ON q.category_id = c.id 
    ORDER BY q.id DESC
";
$orphan_questions = $pdo->query($orphan_query)->fetchAll();

$categories = $pdo->query($query)->fetchAll();

$page_title = "brainSKwiz - Admin Categories";
require_once __DIR__ . '/components/header.php';
?>

        <div class="titleBoxAdmin">
            <div>
                <h1 class="titleText">Question category management</h1>
                <button id="open-modal-btn" class="btn">
                    + Add New Category
                </button>
            </div>
        </div>

        <div class="table-wrapper">
            <table>
                <thead class="tableTitle">
                    <tr>
                        <th>ID</th>
                        <th>Label</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="categories-table-body">
                    <?php foreach($categories as $cat): ?>
                        <tr id="category-row-<?php echo $cat['id']; ?>">
                            <td>#<?php echo $cat['id']; ?></td>
                            <td>
                                <span>
                                    <?php echo htmlspecialchars($cat['label'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td>
                                <span>
                                    <?php echo $cat['total_questions'] . ' ' . ($cat['total_questions'] > 1 ? 'questions' : 'question'); ?>
                                </span>
                            </td>
                            <td>
                                <div>
                                    <button 
                                        data-id="<?php echo $cat['id']; ?>"
                                        data-label="<?php echo htmlspecialchars($cat['label'], ENT_QUOTES, 'UTF-8'); ?>"
                                        onclick="initManageQuestionsModal(this)"
                                        title="Add Questions"
                                        class="addBtn">
                                        <span class="material-icons">add</span>
                                    </button>
                                    <button 
                                        data-id="<?php echo $cat['id']; ?>"
                                        data-label="<?php echo htmlspecialchars($cat['label'], ENT_QUOTES, 'UTF-8'); ?>"
                                        onclick="initEditModal(this)"
                                        title="Edit Category"
                                        class="editBtn">
                                        <span class="material-icons">edit</span>
                                    </button>
                                    <button 
                                        onclick="confirmDeleteCategory(<?php echo $cat['id']; ?>)" 
                                        title="Delete Category"
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

    <div id="category-modal" class="modal hidden">
        <div class="modal-content">
            <div class="titleText modal-header">
                <h3>Add a New Category</h3>
                <button id="close-modal-btn" class="closeBtn">&times;</button>
            </div>
            <form id="add-category-form">
                <div>
                    <label>Category Label</label>
                    <input type="text" id="modal-category-label" required placeholder="Ex: Histoire, Informatique..." class="inputField">
                </div>
                <div class="modal-btn">
                    <button type="button" id="cancel-modal-btn" class="inputField">Cancel</button>
                    <button type="submit" class="btn">Save Category</button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-category-modal" class="modal hidden">
        <div class="modal-content">
            <div class="titleText modal-header">
                <h3>Edit Category Detail</h3>
                <button id="close-edit-modal-btn" class="closeBtn">&times;</button>
            </div>
            <form id="edit-category-form">
                <input type="hidden" id="edit-category-id">
                <div>
                    <label>Category Label</label>
                    <input type="text" id="edit-modal-category-label" required class="inputField">
                </div>
                <div class="modal-btn">
                    <button type="button" id="cancel-edit-modal-btn" class="inputField">Cancel</button>
                    <button type="submit" class="btn">Update Category</button>
                </div>
            </form>
        </div>
    </div>

    <div id="manage-questions-modal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-gray-800 max-w-lg w-full rounded-2xl border border-gray-700 p-6 shadow-2xl flex flex-col max-h-[85vh]">
            <div class="flex justify-between items-center mb-4 flex-shrink-0">
                <div>
                    <h3 class="text-xl font-bold text-teal-400">Add Questions to Category</h3>
                    <p id="manage-modal-subtitle" class="text-xs text-gray-400 mt-1">Target category: <span id="target-category-name" class="text-white font-medium"></span></p>
                </div>
                <button id="close-manage-modal-btn" class="text-gray-400 hover:text-white text-2xl font-bold">&times;</button>
            </div>
            
            <form id="manage-questions-form" class="flex flex-col flex-grow overflow-hidden">
                <input type="hidden" id="manage-category-id">
                
                <div id="questions-list-container" class="overflow-y-auto pr-1 my-4 space-y-2 flex-grow max-h-[50vh] border border-gray-700/50 rounded-xl p-3 bg-gray-900/40">
                    <?php if (empty($orphan_questions)): ?>
                        <p class="text-sm text-gray-400 text-center py-6">No questions available!</p>
                    <?php else: ?>
                        <?php foreach($orphan_questions as $orphan): ?>
                            <label data-current-cat-id="<?php echo $orphan['category_id'] ?? 0; ?>" class="question-item flex items-start gap-3 p-2.5 bg-gray-700/30 hover:bg-gray-700/60 rounded-lg cursor-pointer transition border border-gray-700/40 select-none">
                                <input type="checkbox" name="questions_ids[]" value="<?php echo $orphan['id']; ?>" class="mt-1 accent-teal-500 rounded h-4 w-4 bg-gray-800 border-gray-600">
                                <div class="text-sm w-full">
                                    <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                        <span class="text-xs font-mono px-1.5 py-0.5 rounded bg-gray-800 text-cyan-400">#<?php echo $orphan['id']; ?></span>
                                        <span class="text-xs uppercase px-1 rounded bg-gray-900 text-gray-400 border border-gray-800"><?php echo $orphan['difficulty']; ?></span>
                                        <span class="text-[11px] px-1.5 py-0.5 rounded <?php echo $orphan['category_id'] ? 'bg-blue-950 text-blue-400 border border-blue-900' : 'bg-amber-950 text-amber-400 border border-amber-900'; ?> border">
                                            Current : <?php echo htmlspecialchars($orphan['current_category_label'] ?? 'No category', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </div>
                                    <p class="text-gray-200 text-sm md:text-base"><?php echo htmlspecialchars($orphan['question_text'], ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="flex justify-end gap-3 pt-2 border-t border-gray-700/50 flex-shrink-0">
                    <button type="button" id="cancel-manage-modal-btn" class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded-lg font-medium transition text-sm">Cancel</button>
                    <button type="submit" id="submit-manage-btn" class="bg-teal-500 hover:bg-teal-600 disabled:opacity-40 disabled:hover:bg-teal-500 text-gray-950 font-bold px-4 py-2 rounded-lg transition text-sm">Link Selected Questions</button>
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
        // Management modals (Add)
        const modal = document.getElementById('category-modal');
        const openModalBtn = document.getElementById('open-modal-btn');
        const closeModalBtn = document.getElementById('close-modal-btn');
        const cancelModalBtn = document.getElementById('cancel-modal-btn');
        const addForm = document.getElementById('add-category-form');

        openModalBtn.addEventListener('click', () => {
            addForm.reset(); 
            modal.classList.remove('hidden');
        });
        const hideModal = () => modal.classList.add('hidden');
        closeModalBtn.addEventListener('click', hideModal);
        cancelModalBtn.addEventListener('click', hideModal);

        // Management modals updates
        const editModal = document.getElementById('edit-category-modal');
        const closeEditModalBtn = document.getElementById('close-edit-modal-btn');
        const cancelEditModalBtn = document.getElementById('cancel-edit-modal-btn');
        const editForm = document.getElementById('edit-category-form');

        const hideEditModal = () => editModal.classList.add('hidden');
        closeEditModalBtn.addEventListener('click', hideEditModal);
        cancelEditModalBtn.addEventListener('click', hideEditModal);


        // Management modals (Link Questions)
        const manageModal = document.getElementById('manage-questions-modal');
        const closeManageModalBtn = document.getElementById('close-manage-modal-btn');
        const cancelManageModalBtn = document.getElementById('cancel-manage-modal-btn');
        const manageForm = document.getElementById('manage-questions-form');
        const questionsContainer = document.getElementById('questions-list-container');
        const submitManageBtn = document.getElementById('submit-manage-btn');

        const hideManageModal = () => manageModal.classList.add('hidden');
        closeManageModalBtn.addEventListener('click', hideManageModal);
        cancelManageModalBtn.addEventListener('click', hideManageModal);

        function initManageQuestionsModal(button) {
            const id = parseInt(button.dataset.id);
            const label = button.dataset.label;

            document.getElementById('manage-category-id').value = id;
            document.getElementById('target-category-name').innerText = label;
            
            // Décocher toutes les cases
            manageForm.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);

            // Retirer un éventuel message d'erreur précédent
            const existingNoDataMsg = document.getElementById('js-no-questions-msg');
            if (existingNoDataMsg) existingNoDataMsg.remove();

            // Filtrer dynamiquement les questions affichées
            const questionItems = manageForm.querySelectorAll('.question-item');
            let visibleCount = 0;

            questionItems.forEach(item => {
                const itemCatId = parseInt(item.dataset.currentCatId);
                
                if (itemCatId === id) {
                    item.classList.add('hidden');
                } else {
                    item.classList.remove('hidden');
                    visibleCount++;
                }
            });

            // Si aucune question n'est disponible après filtrage
            if (visibleCount === 0) {
                const noDataParagraph = document.createElement('p');
                noDataParagraph.id = 'js-no-questions-msg';
                noDataParagraph.className = 'text-sm text-gray-400 text-center py-6';
                noDataParagraph.innerText = 'No questions available to link to this category.';
                questionsContainer.appendChild(noDataParagraph);
                
                // Désactiver le bouton de validation car il n'y a rien à faire
                submitManageBtn.disabled = true;
            } else {
                submitManageBtn.disabled = false;
            }

            manageModal.classList.remove('hidden');
        }

        // Types of notifications
        const notifModal = document.getElementById('notification-modal');
        const notifIconContainer = document.getElementById('notif-icon-container');
        const notifIcon = document.getElementById('notif-icon');
        const notifTitle = document.getElementById('notif-title');
        const notifMessage = document.getElementById('notif-message');
        const notifButtons = document.getElementById('notif-buttons');

        const notifTypes = {
            info: { title: "Information", bg: "bg-blue-900/30", text: "text-blue-400", border: "border-blue-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />' },
            success_create: { title: "Category Created!", bg: "bg-emerald-950", text: "text-emerald-400", border: "border-emerald-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />' },
            success_update: { title: "Category Updated!", bg: "bg-blue-950", text: "text-blue-400", border: "border-blue-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />' },
            success_delete: { title: "Category Deleted!", bg: "bg-orange-950", text: "text-orange-400", border: "border-orange-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />' },
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
            const label = button.dataset.label;

            document.getElementById('edit-category-id').value = id;
            document.getElementById('edit-modal-category-label').value = label;

            editModal.classList.remove('hidden');
        }

        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('edit-category-id').value;
            const label = document.getElementById('edit-modal-category-label').value;

            fetch('/api/categories/update_categories.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, label })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    hideEditModal();
                    showNotification('success_update', 'The category has been updated successfully.');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('error', data.message || 'Error occurred during updating.');
                }
            })
            .catch(() => showNotification('error', 'An unexpected error occurred during update.'));
        });

        function confirmDeleteCategory(id) {
            showNotification('info_delete', 'Are you sure you want to delete this category? Warning: This might impact questions linked to it.', () => {
                executeDelete(id);
            });
        }

        function executeDelete(id) {
            fetch('/api/categories/delete_categories.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const row = document.getElementById(`category-row-${id}`);
                    if (row) row.remove();
                    showNotification('success_delete', 'The category has been deleted successfully.');
                } else {
                    showNotification('error', data.message || 'Error while trying to delete.');
                }
            })
            .catch(() => showNotification('error', 'An error occurred during deletion.'));
        }

        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const label = document.getElementById('modal-category-label').value;

            fetch('/api/categories/add_categories.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ label })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    hideModal();
                    showNotification('success_create', 'The category was successfully saved.');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('error', data.message || 'Failed to create the category.');
                }
            })
            .catch(() => showNotification('error', 'An error occurred during save.'));
        });

        manageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const category_id = document.getElementById('manage-category-id').value;
            
            const checkedBoxes = manageForm.querySelectorAll('input[name="questions_ids[]"]:checked');
            const question_ids = Array.from(checkedBoxes).map(cb => parseInt(cb.value));

            if (question_ids.length === 0) {
                showNotification('error', 'Please select at least one question.');
                return;
            }

            fetch('/api/categories/bind_questions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ category_id, question_ids })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    hideManageModal();
                    showNotification('success_update', 'Questions successfully assigned to the category!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('error', data.message || 'Error occurred during processing.');
                }
            })
            .catch(() => showNotification('error', 'An unexpected error occurred.'));
        });
    </script>

<?php require_once __DIR__ . '/components/footer.php'; ?>