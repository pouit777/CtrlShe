<?php
include 'components/header.php';
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT u.*, COALESCE(SUM(g.points_earned), 0) AS total_points 
    FROM users u 
    LEFT JOIN games g ON g.user_id = u.id 
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<div class="page-container page-settings">

    <div class="titleBoxAdmin">
        <h1 class="bigTitle">Account Settings</h1>
    </div>

    <div class="settings">

        <!-- MENU -->
        <div class="choices">
            <ul>
                <li id="profileSettingBtn" class="active">
                    <button onclick="setActive('profileSetting')">Profile<span class="material-icons">person</span></button>
                </li>
                <li id="passwordSettingBtn">
                    <button onclick="setActive('passwordSetting')">Password<span class="material-icons">lock</span></button>
                </li>
                <li id="statUserBtn">
                    <button onclick="setActive('statUser')">Stats<span class="material-icons">leaderboard</span></button>
                </li>
            </ul>
        </div>

        <!-- PROFILE -->
        <div id="profileSetting" class="settingPage activeWindow">
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
                    <input class="inputField" type="text" id="username"
                        value="<?= htmlspecialchars($user['username']) ?>"
                        minlength="3" maxlength="25" required>
                </div>

                <input type="hidden" id="selectedAvatar" name="avatar"
                    value="<?= htmlspecialchars($user['avatar'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                <div id="avatarChoices" class="avatar-section textWhite">
                    <h3>Choose your new avatar</h3>

                    <div class="avatar-grid">
                        <?php
                        $files = glob(__DIR__ . "/public/avatars/*.png");
                        foreach ($files as $file):
                            $filename = basename($file);
                        ?>
                            <img src="/public/avatars/<?= htmlspecialchars($filename) ?>"
                                class="avatar-item border-2 border-transparent cursor-pointer transition"
                                data-avatar="<?= htmlspecialchars($filename) ?>">
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="btn">Save Changes</button>
            </form>
        </div>

        <!-- PASSWORD -->
        <div id="passwordSetting" class="settingPage hidden">

            <div class="titleText modal-header">
                <h3>Change password</h3>
            </div>

            <form id="resetPassword">

                <div style="margin-bottom: 1rem;">
                    <label for="newPassword">New Password</label>
                    <div class="relative w-full">
                        <input type="password" id="newPassword" required class="inputField pr-10 w-full" minlength="8">
                        <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center hover:text-secondary transition focus:outline-none"
                            onclick="togglePasswordVisibility('newPassword', this)">
                            <span class="material-icons eyeIcon">visibility_off</span>
                        </button>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="conformPassword">Confirm Password</label>
                    <div class="relative w-full">
                        <input type="password" id="conformPassword" required class="inputField pr-10 w-full" minlength="8">
                        <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center hover:text-secondary transition focus:outline-none"
                            onclick="togglePasswordVisibility('conformPassword', this)">
                            <span class="material-icons eyeIcon">visibility_off</span>
                        </button>
                    </div>
                </div>

                    <div class="modal-btn">
                        <button type="button" class="inputField"
                            onclick="setActive('profileSetting')">Cancel</button>
                        <button type="submit" class="btn">Reset Password</button>
                    </div>
                </form>
            </div>

        <!-- STATS -->
        <div id="statUser" class="settingPage hidden">

            <h2 class="bigTitle text-white mb-4">My Statistics</h2>

            <div class="stats-grid">

                <!-- BEST TIME -->
                <div class="stat-card">
                    <span class="material-icons text-blue-400">timer</span>
                    <p class="stat-label text-gray-400">Best time</p>
                    <h2 id="valueTime" class="text-white text-xl font-bold">--</h2>
                </div>

                <!-- BEST SCORE -->
                <div class="stat-card">
                    <span class="material-icons text-emerald-400">emoji_events</span>
                    <p class="stat-label text-gray-400">Best score</p>
                    <h2 id="valueScore" class="text-white text-xl font-bold">--</h2>
                </div>

                <!-- RESPONSE TIME -->
                <div class="stat-card">
                    <span class="material-icons text-yellow-400">schedule</span>
                    <p class="stat-label text-gray-400">Average response time</p>
                    <h2 id="valueResponseTime" class="text-white text-xl font-bold">--</h2>
                </div>

                <!-- TOTAL POINTS -->
                <div class="stat-card">
                    <span class="material-icons text-purple-400">stars</span>
                    <p class="stat-label text-gray-400">Total points</p>
                    <h2 id="valuePoints" class="text-white text-xl font-bold">
                        <?= (int)$user['total_points'] ?>
                    </h2>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- NOTIFICATION MODAL -->
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
async function loadStats() {
    try {
        const response = await fetch("/api/stats/get_stats.php");
        const stats = await response.json();

        document.getElementById("valueTime").textContent = stats.best_time ?? "-";
        document.getElementById("valueScore").textContent = stats.best_score ?? "-";
        document.getElementById("valueResponseTime").textContent = stats.avg_time ?? "-";
    } catch (error) {
        console.error("Error loading stats:", error);
    }
}
loadStats();

document.querySelectorAll('.avatar-item').forEach(avatar => {
    avatar.addEventListener('click', function() {
        document.querySelectorAll('.avatar-item').forEach(img => img.classList.remove('border-blue-500'));
        this.classList.add('border-blue-500');
        
        const avatarName = this.getAttribute('data-avatar');
        document.getElementById('selectedAvatar').value = avatarName;
        document.getElementById('avatarPreview').src = "/public/avatars/" + avatarName;
    });
});

document.getElementById('profileForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    const username = document.getElementById('username').value.trim();
    const avatar = document.getElementById('selectedAvatar').value.trim();

    try {
        const response = await fetch("/api/account/update_profile.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ username, avatar })
        });
        const result = await response.json();

        if (result.status === "success") {
            showNotification("success_profile", "Your profile has been updated successfully!", () => {
                location.reload(); 
            });
        } else {
            showNotification("error", result.message || "Failed to update profile.");
        }
    } catch (error) {
        console.error("Error updating profile:", error);
        showNotification("error", "An error occurred. Please try again.");
    }
});

document.getElementById('resetPassword')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    const newPassword = document.getElementById('newPassword').value;
    const conformPassword = document.getElementById('conformPassword').value;

    if (newPassword !== conformPassword) {
        showNotification("error", "Passwords do not match!");
        return;
    }

    const hasNumber = /[0-9]/.test(newPassword);
    const hasSpecial = /[^a-zA-Z0-9]/.test(newPassword);

    if (newPassword.length < 8 || !hasNumber || !hasSpecial) {
        showNotification("error", "Password must be at least 8 characters long, containing at least one number and one special character.");
        return;
    }

    try {
        const response = await fetch("/api/account/update_password.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ password: newPassword })
        });
        const result = await response.json();

        if (result.status === "success") {
            showNotification("success_password", "Your password has been reset successfully!", () => {
                document.getElementById('resetPassword').reset();
                setActive('profileSetting');
            });
        } else {
            showNotification("error", result.message || "Failed to reset password.");
        }
    } catch (error) {
        console.error("Error resetting password:", error);
        showNotification("error", "An error occurred. Please try again.");
    }
});

const notifModal = document.getElementById('notification-modal');
const notifIconContainer = document.getElementById('notif-icon-container');
const notifIcon = document.getElementById('notif-icon');
const notifTitle = document.getElementById('notif-title');
const notifMessage = document.getElementById('notif-message');
const notifButtons = document.getElementById('notif-buttons');

const notifTypes = {
    info: { title: "Information", bg: "bg-blue-900/30", text: "text-blue-400", border: "border-blue-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />' },
    success_profile: { title: "Profile Updated!", bg: "bg-emerald-950", text: "text-emerald-400", border: "border-emerald-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />' },
    success_password: { title: "Password Reset!", bg: "bg-blue-950", text: "text-blue-400", border: "border-blue-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />' },
    error: { title: "An Error Occurred", bg: "bg-red-950", text: "text-red-400", border: "border-red-800", svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />' }
};

function showNotification(type, message, onCloseCallback = null) {
    const config = notifTypes[type] || notifTypes.info;
    notifIconContainer.className = `mx-auto flex items-center justify-center h-12 w-12 rounded-full mb-4 ${config.bg} ${config.text} border ${config.border}`;
    notifIcon.innerHTML = config.svg;
    notifTitle.innerText = config.title;
    notifTitle.className = `text-lg font-bold mb-2 ${config.text}`;
    notifMessage.innerText = message;
    notifButtons.innerHTML = '';

    const okBtn = document.createElement('button');
    okBtn.innerText = "Ok";
    okBtn.className = "bg-gray-700 hover:bg-gray-600 text-white font-medium px-5 py-2 rounded-lg transition w-full";
    okBtn.onclick = () => {
        notifModal.classList.add('hidden');
        if (onCloseCallback) onCloseCallback();
    };
    notifButtons.appendChild(okBtn);
    notifModal.classList.remove('hidden');
}

function setActive(idWindow) {
    document.querySelectorAll('.settingPage').forEach(p => {
        p.classList.add('hidden');
        p.classList.remove('activeWindow');
    });

    document.querySelectorAll('.choices li').forEach(b => b.classList.remove('active'));

    document.getElementById(idWindow)?.classList.remove('hidden');
    document.getElementById(idWindow)?.classList.add('activeWindow');

    document.getElementById(idWindow + 'Btn')?.classList.add('active');
}

function togglePasswordVisibility(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('.material-icons');

    if (input.type === "password") {
        input.type = "text";
        icon.textContent = "visibility";
    } else {
        input.type = "password";
        icon.textContent = "visibility_off";
    }
}
</script>