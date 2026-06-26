<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only allow access to this page for users with the 'admin' role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /index.php');
    exit;
}

require_once __DIR__ . '/config/db.php';

// Ajout de la colonne 'avatar' à la récupération
$query = "SELECT id, username, email, role, avatar, created_at FROM users ORDER BY id DESC";
$users = $pdo->query($query)->fetchAll();

$page_title = "brainSKwiz - User Management";
require_once __DIR__ . '/components/header.php';
?>

        <div class="titleBoxAdmin">
            <div>
                <h1 class="titleText">User management</h1>
                <div class="modal-btn">
                    <button id="open-modal-btn" class="btn">
                        + Add New User
                    </button>
                </div>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="whitespace-normal">
                <thead class="tableTitle">
                    <tr>
                        <th>ID</th>
                        <th>Avatar</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="users-table-body">
                    <?php foreach($users as $u): ?>
                        <tr id="user-row-<?php echo $u['id']; ?>">
                            <td>#<?php echo $u['id']; ?></td>
                            <td>
                                <img src="/public/avatars/<?php echo htmlspecialchars($u['avatar'] ?? 'default.png', ENT_QUOTES, 'UTF-8'); ?>" 
                                     class="w-8 h-8 rounded-full object-cover border border-lightBlue">
                            </td>
                            <td><?php echo htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <span class="text-xs font-mono uppercase px-2 py-0.5 rounded <?php echo $u['role'] === 'admin' ? 'bg-red-950 text-red-400 border border-red-900' : 'bg-green-950 text-green-400 border border-green-900'; ?> border">
                                    <?php echo htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($u['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="align-middle">
                                <div class="flex items-center gap-2 justify-start h-full">
                                    <button 
                                        data-id="<?php echo $u['id']; ?>"
                                        data-username="<?php echo htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-email="<?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-role="<?php echo htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-avatar="<?php echo htmlspecialchars($u['avatar'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        onclick="initEditModal(this)"
                                        title="Edit User"
                                        class="editBtn">
                                        <span class="material-icons">edit</span>
                                    </button>
                                    <?php if ($u['id'] != ($_SESSION['user_id'] ?? 0)): ?>
                                    <button 
                                        onclick="confirmDeleteUser(<?php echo $u['id']; ?>)" 
                                        title="Delete User"
                                        class="deleteBtn">
                                        <span class="material-icons">delete</span>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <div id="user-modal" class="modal hidden">
        <div class="modal-content">
            <div class="titleText modal-header">
                <h3>Add a New User</h3>
                <button id="close-modal-btn" class="closeBtn font-large text-secondary">&times;</button>
            </div>
            <form id="add-user-form">
                <div>
                    <label>Username</label>
                    <input type="text" id="modal-username" required placeholder="john_doe" class="inputField">
                </div>
                <div>
                    <label>Email</label>
                    <input type="email" id="modal-email" required placeholder="john@example.com" class="inputField">
                </div>
                <div>
                    <label>Password</label>
                    <input type="password" id="modal-password" required placeholder="••••••••" class="inputField">
                </div>
                <div>
                    <label>Role</label>
                    <select id="modal-role" required class="inputField">
                        <option value="user" selected>User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="modal-btn">
                    <button type="button" id="cancel-modal-btn" class="inputField">Cancel</button>
                    <button type="submit" class="btn">Save User</button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-user-modal" class="modal hidden">
        <div class="modal-content max-w-lg overflow-y-auto max-h-[90vh]">
            <div class="titleText modal-header">
                <h3>Edit User Detail</h3>
                <button id="close-edit-modal-btn" class="closeBtn font-large text-secondary">&times;</button>
            </div>
            <form id="edit-user-form">
                <input type="hidden" id="edit-user-id">
                
                <div class="flex items-center gap-4 mb-4 p-3 rounded-xl border border-lightBlue/20">
                    <img id="edit-avatarPreview" src="/public/avatars/default.png" class="w-16 h-16 rounded-full object-cover border-2 border-secondary">
                    <div>
                        <p class="text-sm font-semibold text-secondary">User Avatar</p>
                        <p class="text-xs text-gray-400">Click on an avatar below to update it</p>
                    </div>
                </div>

                <input type="hidden" id="edit-selectedAvatar" name="avatar">

                <div class="avatar-section mb-4">
                    <label class="block mb-2 font-medium">Choose user's avatar</label>
                    <div class="avatar-grid flex flex-wrap gap-2 p-2 bg-lightBlue rounded-xl border border-gray-200 max-h-[150px] overflow-y-auto">
                        <?php
                        $files = glob(__DIR__ . "/public/avatars/*.png");
                        foreach ($files as $file):
                            $filename = basename($file);
                        ?>
                            <img
                                src="/public/avatars/<?php echo htmlspecialchars($filename, ENT_QUOTES, 'UTF-8'); ?>"
                                class="admin-avatar-item w-12 h-12 rounded-full cursor-pointer border-2 border-transparent hover:border-secondary transition"
                                data-avatar="<?php echo htmlspecialchars($filename, ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <label>Username</label>
                    <input type="text" id="edit-modal-username" required class="inputField">
                </div>
                <div>
                    <label>Email</label>
                    <input type="email" id="edit-modal-email" required class="inputField">
                </div>
                <div>
                    <label>Password (Leave empty to keep current password)</label>
                    <input type="password" id="edit-modal-password" placeholder="New password (optional)" class="inputField">
                </div>
                <div>
                    <label>Role</label>
                    <select id="edit-modal-role" required class="inputField">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="modal-btn mt-4">
                    <button type="button" id="cancel-edit-modal-btn" class="inputField">Cancel</button>
                    <button type="submit" class="btn">Update User</button>
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

        // INITIALISATION DE LA MODALE D'ÉDITION AVEC LA COPIE DE L'AVATAR
        const editHiddenInput = document.getElementById("edit-selectedAvatar");
        const editAvatarPreview = document.getElementById("edit-avatarPreview");

        function initEditModal(button) {
            document.getElementById('edit-user-id').value = button.dataset.id;
            document.getElementById('edit-modal-username').value = button.dataset.username;
            document.getElementById('edit-modal-email').value = button.dataset.email;
            document.getElementById('edit-modal-role').value = button.dataset.role;
            document.getElementById('edit-modal-password').value = ''; 

            // Configuration de la preview de l'avatar de l'utilisateur concerné
            const currentAvatar = button.dataset.avatar || 'default.png';
            editHiddenInput.value = currentAvatar;
            editAvatarPreview.src = `/public/avatars/${currentAvatar}`;

            // Sélection visuelle de l'avatar dans la grille
            document.querySelectorAll(".admin-avatar-item").forEach(img => {
                if (img.dataset.avatar === currentAvatar) {
                    img.classList.add("border-secondary");
                } else {
                    img.classList.remove("border-secondary");
                }
            });
            
            editModal.classList.remove('hidden');
        }

        // GESTION DU CLIC SUR LA GRILLE D'AVATARS (MODALE ADMIN)
        document.querySelectorAll(".admin-avatar-item").forEach(img => {
            img.addEventListener("click", () => {
                const avatar = img.dataset.avatar;
                editHiddenInput.value = avatar;
                editAvatarPreview.src = `/public/avatars/${avatar}`;

                document.querySelectorAll(".admin-avatar-item").forEach(i => {
                    i.classList.remove("border-secondary");
                });
                img.classList.add("border-secondary");
            });
        });

        // --- FETCH : ENVOI ÉDITION (AVEC PARAMÈTRE AVATAR INCLUS) ---
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('edit-user-id').value;
            const username = document.getElementById('edit-modal-username').value;
            const email = document.getElementById('edit-modal-email').value;
            const role = document.getElementById('edit-modal-role').value;
            const password = document.getElementById('edit-modal-password').value;
            const avatar = editHiddenInput.value; // Récupération de l'avatar choisi

            fetch('/api/users/update_users.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, username, email, role, password, avatar }) // Ajout à la payload
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