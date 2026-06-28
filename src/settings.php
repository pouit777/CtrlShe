<?php
include 'components/header.php';
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<div class="max-w-5xl mx-auto mt-10 page-settings">
    <div class="titleBoxAdmin">

        <h1 class="bigTitle">
            Account Settings
        </h1>

        <!-- <p class="subTitle">
            Logged in as :
            <strong>
                <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>
                (<span class="uppercase"><?= htmlspecialchars($_SESSION['role']) ?></span>)
            </strong>
        </p> -->

    </div>

    <div class="settings grid grid-cols-1 md:grid-cols-10 gap-4 items-start">
        <div class="choices md:col-span-3">
            <ul>
                <li id="profileSettingBtn" class="active"><button onclick="setActive('profileSetting')">Profile<span class="material-icons">person</span></button></li>
                <li id="passwordSettingBtn"><button onclick="setActive('passwordSetting')">Password<span class="material-icons">lock</span></button></li>
                <li id="statUserBtn"><button onclick="setActive('statUser')">Stats<span class="material-icons">leaderboard</span></button></li>
            </ul>
        </div>

        <div id="profileSetting" class="settingPage activeWindow md:col-span-7">
            <h1 class="textWhite">My Profile</h1>

            <div class="profile-header">
                <img id="avatarPreview"
                    src="/public/avatars/<?= htmlspecialchars($user['avatar']) ?>"
                    class="profile-avatar">

                <div class="profile-info">
                    <p class="profile-name"><?= htmlspecialchars($user['username']) ?></p>
                    <p class="profile-email"><?= htmlspecialchars($user['email']) ?></p>
                    <p class="profile-role">Role: <?= htmlspecialchars($user['role']) ?></p>
                </div>
            </div>

            <form id="profileForm">
                <div style="margin-bottom: 1.5rem;">
                    <label class="textWhite block mb-2">Username (3 to 25 characters)</label>
                    <input class="inputField" type="text" id="username" value="<?= htmlspecialchars($user['username']) ?>" minlength="3" maxlength="25" required>
                </div>

                <input type="hidden"
                    id="selectedAvatar"
                    name="avatar"
                    value="<?= htmlspecialchars($user['avatar'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                <div id="avatarChoices" class="avatar-section textWhite" style="margin-bottom: 1.5rem;">
                    <h3>Choose your new avatar</h3>
                    <div class="avatar-grid">
                        <?php
                        $files = glob(__DIR__ . "/public/avatars/*.png");
                        foreach ($files as $file):
                            $filename = basename($file);
                        ?>
                            <img
                                src="/public/avatars/<?= htmlspecialchars($filename) ?>"
                                class="avatar-item border-2 border-transparent cursor-pointer transition"
                                data-avatar="<?= htmlspecialchars($filename) ?>"
                            >
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="btn">Save Changes</button>
            </form>
        </div>

        <div id="passwordSetting" class="settingPage hidden md:col-span-7">
            <div class="titleText modal-header">
                <h3>Change password</h3>
            </div>
            <form id="resetPassword">
                <div style="margin-bottom: 1rem;">
                    <label for="newPassword">New Password</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="password" id="newPassword" required placeholder="••••••••" class="inputField" minlength="8" style="width: 100%; padding-right: 2.5rem;">
                        <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility('newPassword', this)" style="position: absolute; right: 10px; background: none; border: none; color: #9ca3af; cursor: pointer; display: flex; align-items: center;">
                            <span class="material-icons">visibility_off</span>
                        </button>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="conformPassword">Confirm Password</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="password" id="conformPassword" required placeholder="••••••••" class="inputField" minlength="8" style="width: 100%; padding-right: 2.5rem;">
                        <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility('conformPassword', this)" style="position: absolute; right: 10px; background: none; border: none; color: #9ca3af; cursor: pointer; display: flex; align-items: center;">
                            <span class="material-icons">visibility_off</span>
                        </button>
                    </div>
                </div>

                <div class="modal-btn">
                    <button type="button" class="inputField" onclick="setActive('profileSetting')">Cancel</button>
                    <button type="submit" class="btn">Reset Password</button>
                </div>
            </form>
        </div>

        <div id="statUser" class="settingPage hidden md:col-span-7">
            <h3>Meilleur temps</h3>
            <h3>Temps moyen de réponse</h3>
            <h3>Taux de bonnes réponses</h3>
            <h3>Classement mondial</h3>
        </div>
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
    function setActive(idWindow) {
        const pages = document.querySelectorAll('.settingPage');
        const buttons = document.querySelectorAll('.choices li');

        pages.forEach(page => {
            page.classList.remove('activeWindow');
            page.classList.add('hidden');
        });

        buttons.forEach(btn => {
            btn.classList.remove('active');
        });

        const activePage = document.getElementById(idWindow);
        if (activePage) {
            activePage.classList.remove('hidden');
            activePage.classList.add('activeWindow');
        }

        const activeBtn = document.getElementById(idWindow + 'Btn');
        if (activeBtn) {
            activeBtn.classList.add('active');
        }
    }

    // --- NOUVEAU : Fonction pour afficher/masquer le mot de passe ---
    function togglePasswordVisibility(inputId, button) {
        const passwordInput = document.getElementById(inputId);
        const icon = button.querySelector('.material-icons');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.textContent = 'visibility'; // Icône oeil ouvert
        } else {
            passwordInput.type = 'password';
            icon.textContent = 'visibility_off'; // Icône oeil barré
        }
    }

    // --- CONFIGURATION DU SYSTEME DE NOTIFICATIONS ---
    const notifModal = document.getElementById('notification-modal');
    const notifIconContainer = document.getElementById('notif-icon-container');
    const notifIcon = document.getElementById('notif-icon');
    const notifTitle = document.getElementById('notif-title');
    const notifMessage = document.getElementById('notif-message');
    const notifButtons = document.getElementById('notif-buttons');

    const notifTypes = {
        success_update: { title: "Profile Updated!", bg: "bg-blue-950", text: "text-blue-400", border: "border-blue-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />' },
        success_password: { title: "Password Changed!", bg: "bg-emerald-950", text: "text-emerald-400", border: "border-emerald-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />' },
        error: { title: "An Error Occurred", bg: "bg-red-950", text: "text-red-400", border: "border-red-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />' }
    };

    function showNotification(type, message) {
        const config = notifTypes[type] || notifTypes.error;
        notifIconContainer.className = `mx-auto flex items-center justify-center h-12 w-12 rounded-full mb-4 ${config.bg} ${config.text} border ${config.border}`;
        notifIcon.innerHTML = config.svg;
        notifTitle.innerText = config.title;
        notifTitle.className = `text-lg font-bold mb-2 ${config.text}`;
        notifMessage.innerText = message;
        notifButtons.innerHTML = '';

        const okBtn = document.createElement('button');
        okBtn.innerText = "Ok";
        okBtn.className = "bg-gray-700 hover:bg-gray-600 text-white font-medium px-5 py-2 rounded-lg transition w-full";
        okBtn.onclick = () => notifModal.classList.add('hidden');
        notifButtons.appendChild(okBtn);
        
        notifModal.classList.remove('hidden');
    }

    // --- GESTION DE L'AVATAR ---
    const hiddenInput = document.getElementById("selectedAvatar");
    const avatarPreview = document.getElementById("avatarPreview");

    document.querySelectorAll(".avatar-item").forEach(img => {
        if (img.dataset.avatar === hiddenInput.value) {
            img.classList.remove("border-transparent");
            img.classList.add("border-secondary");
        }

        img.addEventListener("click", () => {
            const avatar = img.dataset.avatar;
            hiddenInput.value = avatar;
            avatarPreview.src = `/public/avatars/${avatar}`;

            document.querySelectorAll(".avatar-item").forEach(i => {
                i.classList.remove("border-secondary");
                i.classList.add("border-transparent");
            });
            img.classList.remove("border-transparent");
            img.classList.add("border-secondary");
        });
    });

    // --- SOUMISSION DU PROFIL ---
    document.getElementById("profileForm").addEventListener("submit", async (e) => {
        e.preventDefault();

        const username = document.getElementById("username").value.trim();
        const avatar = hiddenInput.value;

        if (username.length < 3 || username.length > 25) {
            showNotification('error', "Username must be between 3 and 25 characters.");
            return;
        }

        try {
            const response = await fetch("/api/account/update_profile.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ username: username, avatar: avatar })
            });

            const data = await response.json();
            if (data.status === "success") {
                showNotification('success_update', 'Your profile details have been updated successfully.');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('error', data.message || "Save failed");
            }
        } catch (err) {
            console.error(err);
            showNotification('error', "Server error while saving profile.");
        }
    });

    // --- SOUMISSION DU MOT DE PASSE ---
    document.getElementById("resetPassword").addEventListener("submit", async (e) => {
        e.preventDefault();

        const newPassword = document.getElementById("newPassword").value;
        const conformPassword = document.getElementById("conformPassword").value;

        const passwordRegex = /^(?=.*[0-9])(?=.*[!@#$%^&*(),.?":{}|<>_\-+=]).{8,}$/;
    
        if (!passwordRegex.test(newPassword)) {
            showNotification('error', "Password must be at least 8 characters long, contain a number and a special character.");
            return;
        }

        if (newPassword !== conformPassword) {
            showNotification('error', "Passwords do not match!");
            return;
        }

        try {
            const response = await fetch("/api/account/update_password.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ password: newPassword })
            });

            const data = await response.json();

            if (data.status === "success") {
                showNotification('success_password', "Your password has been changed successfully!");
                document.getElementById("resetPassword").reset();
                
                // Réinitialise les icônes en mode caché si elles étaient visibles
                document.querySelectorAll('.toggle-password-btn .material-icons').forEach(icon => icon.textContent = 'visibility_off');
                document.getElementById('newPassword').type = 'password';
                document.getElementById('conformPassword').type = 'password';

                setTimeout(() => setActive('profileSetting'), 1500);
            } else {
                showNotification('error', data.message || "Failed to update password");
            }
        } catch (err) {
            console.error(err);
            showNotification('error', "Server error while updating password.");
        }
    });
</script>