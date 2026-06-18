<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only allow access to this page for users with the 'admin' role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/config/db.php';

// Recuperation of all users from the database, ordered by ID in descending order
$query = "SELECT id, username, email, role, created_at FROM users ORDER BY id DESC";
$users = $pdo->query($query)->fetchAll();

$page_title = "brainSKwiz - User Management";
require_once __DIR__ . '/components/header.php';
?>

        <div class="flex flex-col sm:flex-row justify-between items-center text-center sm:text-left gap-4 mb-8 bg-primary p-4 md:p-6 rounded-xl border border-secondary shadow-lg">
            <div>
                <h1 class="text-2xl md:text-3xl mb-5 md:mb-3 font-extrabold text-transparent bg-clip-text bg-white drop-shadow-sm">User management</h1>
                <p class="text-xs md:text-sm mb-3 md:mb-0 text-white mt-1">
                    Logged in as : <strong class="text-white bg-white-500 "><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?> (<span class="uppercase"><?php echo htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8'); ?></span>)</strong>                </p>
            </div>
            <div class="w-full sm:w-auto flex justify-end">
                <button id="open-modal-btn" class="w-full sm:w-auto bg-gradient-to-r bg-accent hover:bg-accent-hover text-primary font-bold py-2 px-4 rounded-lg shadow-md transition transform hover:-translate-y-0.5 text-sm md:text-base">
                    + Add New User
                </button>
            </div>
        </div>

        <div class="bg-secondary rounded-xl border border-primary shadow-xl overflow-hidden overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                    <tr class="bg-primary text-white uppercase text-xs font-mono border-b border-primary">
                        <th class="p-4 w-20">ID</th>
                        <th class="p-4">Username</th>
                        <th class="p-4">Email</th>
                        <th class="p-4 w-32">Role</th>
                        <th class="p-4 w-40">Created At</th>
                        <th class="p-4 w-28 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="users-table-body">
                    <?php foreach($users as $u): ?>
                        <tr id="user-row-<?php echo $u['id']; ?>" class="border-b border-gray-700/50 hover:bg-gray-700/30 transition">
                            <td class="p-4 font-mono text-primary">#<?php echo $u['id']; ?></td>
                            <td class="p-4 text-primary font-medium text-sm md:text-base"><?php echo htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="p-4 text-primary text-sm"><?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="p-4">
                                <span class="text-xs font-mono uppercase px-2 py-0.5 rounded <?php echo $u['role'] === 'admin' ? 'bg-red-950 text-red-400 border border-red-900' : 'bg-green-950 text-green-400 border border-green-900'; ?> border">
                                    <?php echo htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td class="p-4 text-primary text-xs font-mono"><?php echo htmlspecialchars($u['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="p-4 align-middle text-center">
                                <div class="flex justify-center items-center gap-2 w-full h-full">
                                    <button 
                                        data-id="<?php echo $u['id']; ?>"
                                        data-username="<?php echo htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-email="<?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-role="<?php echo htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8'); ?>"
                                        onclick="initEditModal(this)"
                                        title="Edit User"
                                        class="flex items-center gap-1 bg-blue-600 hover:bg-blue-600/50 border border-blue-500 hover:border-blue-800 text-white px-2.5 py-1.5 rounded-lg text-xs font-semibold transition duration-150 shadow-sm">
                                        Edit
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    <?php if ($u['id'] != $_SESSION['user_id'] ?? 0): ?>
                                    <button 
                                        onclick="confirmDeleteUser(<?php echo $u['id']; ?>)" 
                                        title="Delete User"
                                        class="flex items-center gap-1 bg-red-600 hover:bg-red-600/50 border border-red-500 hover:border-red-800 text-white px-2.5 py-1.5 rounded-lg text-xs font-semibold transition duration-150 shadow-sm">
                                        Delete
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <div id="user-modal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-gray-800 max-w-lg w-full rounded-2xl border border-gray-700 p-6 shadow-2xl overflow-y-auto max-h-[90vh]">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-emerald-400">Add a New User</h3>
                <button id="close-modal-btn" class="text-gray-400 hover:text-white text-2xl font-bold">&times;</button>
            </div>
            <form id="add-user-form" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Username</label>
                    <input type="text" id="modal-username" required placeholder="john_doe" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-emerald-500 transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Email</label>
                    <input type="email" id="modal-email" required placeholder="john@example.com" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-emerald-500 transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Password</label>
                    <input type="password" id="modal-password" required placeholder="••••••••" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-emerald-500 transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Role</label>
                    <select id="modal-role" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-emerald-500 transition">
                        <option value="user" selected>User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" id="cancel-modal-btn" class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded-lg font-medium transition">Cancel</button>
                    <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-gray-950 font-bold px-4 py-2 rounded-lg transition">Save User</button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-user-modal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-gray-800 max-w-lg w-full rounded-2xl border border-gray-700 p-6 shadow-2xl overflow-y-auto max-h-[90vh]">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-blue-400">Edit User Detail</h3>
                <button id="close-edit-modal-btn" class="text-gray-400 hover:text-white text-2xl font-bold">&times;</button>
            </div>
            <form id="edit-user-form" class="space-y-4">
                <input type="hidden" id="edit-user-id">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Username</label>
                    <input type="text" id="edit-modal-username" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-blue-500 transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Email</label>
                    <input type="email" id="edit-modal-email" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-blue-500 transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Password (Leave empty to keep current password)</label>
                    <input type="password" id="edit-modal-password" placeholder="New password (optional)" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-blue-500 transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Role</label>
                    <select id="edit-modal-role" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-blue-500 transition">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" id="cancel-edit-modal-btn" class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded-lg font-medium transition">Cancel</button>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold px-4 py-2 rounded-lg transition">Update User</button>
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
        const modal = document.getElementById('user-modal');
        const openModalBtn = document.getElementById('open-modal-btn');
        const closeModalBtn = document.getElementById('close-modal-btn');
        const cancelModalBtn = document.getElementById('cancel-modal-btn');
        const addForm = document.getElementById('add-user-form');

        // Toggle Modale Ajout
        openModalBtn.addEventListener('click', () => {
            addForm.reset(); 
            modal.classList.remove('hidden');
        });

        const hideModal = () => modal.classList.add('hidden');
        closeModalBtn.addEventListener('click', hideModal);
        cancelModalBtn.addEventListener('click', hideModal);

        const editModal = document.getElementById('edit-user-modal');
        const closeEditModalBtn = document.getElementById('close-edit-modal-btn');
        const cancelEditModalBtn = document.getElementById('cancel-edit-modal-btn');
        const editForm = document.getElementById('edit-user-form');

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
            success_create: { title: "User Created!", bg: "bg-emerald-950", text: "text-emerald-400", border: "border-emerald-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />' },
            success_update: { title: "User Updated!", bg: "bg-blue-950", text: "text-blue-400", border: "border-blue-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />' },
            success_delete: { title: "User Deleted!", bg: "bg-orange-950", text: "text-orange-400", border: "border-orange-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />' },
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

        // Init Modale d'Édition
        function initEditModal(button) {
            document.getElementById('edit-user-id').value = button.dataset.id;
            document.getElementById('edit-modal-username').value = button.dataset.username;
            document.getElementById('edit-modal-email').value = button.dataset.email;
            document.getElementById('edit-modal-role').value = button.dataset.role;
            document.getElementById('edit-modal-password').value = ''; // Reset mot de passe par sécurité
            
            editModal.classList.remove('hidden');
        }

        // --- FETCH : ENVOI ÉDITION ---
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('edit-user-id').value;
            const username = document.getElementById('edit-modal-username').value;
            const email = document.getElementById('edit-modal-email').value;
            const role = document.getElementById('edit-modal-role').value;
            const password = document.getElementById('edit-modal-password').value;

            fetch('/api/users/update_users.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, username, email, role, password })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    hideEditModal();
                    showNotification('success_update', 'The user has been updated successfully.');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('error', data.message || 'Error occurred during updating.');
                }
            })
            .catch(() => showNotification('error', 'An unexpected error occurred during update.'));
        });

        function confirmDeleteUser(id) {
            showNotification('info_delete', 'Are you absolutely sure you want to delete this user? This will also wipe their game sessions history.', () => {
                executeDelete(id);
            });
        }

        function executeDelete(id) {
            fetch('/api/users/delete_users.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const row = document.getElementById(`user-row-${id}`);
                    if (row) row.remove();
                    showNotification('success_delete', 'The user has been deleted successfully.');
                } else {
                    showNotification('error', data.message || 'Error while trying to delete.');
                }
            })
            .catch(() => showNotification('error', 'An error occurred during deletion.'));
        }

        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const username = document.getElementById('modal-username').value;
            const email = document.getElementById('modal-email').value;
            const password = document.getElementById('modal-password').value;
            const role = document.getElementById('modal-role').value;

            fetch('/api/users/add_users.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, email, password, role })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    hideModal();
                    showNotification('success_create', 'The user has been successfully created.');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('error', data.message || 'Failed to create the user.');
                }
            })
            .catch(() => showNotification('error', 'An error occurred during save.'));
        });
    </script>

<?php require_once __DIR__ . '/components/footer.php'; ?>