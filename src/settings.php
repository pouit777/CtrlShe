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
        <h1 class="bigTitle">Account Settings</h1>
    </div>

    <div class="settings grid grid-cols-1 md:grid-cols-10 gap-4 items-start">

        <!-- MENU -->
        <div class="choices md:col-span-3">
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
        <div id="passwordSetting" class="settingPage hidden md:col-span-7">

            <div class="titleText modal-header">
                <h3>Change password</h3>
            </div>

            <form id="resetPassword">

                <div style="margin-bottom: 1rem;">
                    <label for="newPassword">New Password</label>
                    <div style="position: relative;">
                        <input type="password" id="newPassword" required class="inputField" minlength="8">
                        <button type="button" class="toggle-password-btn"
                            onclick="togglePasswordVisibility('newPassword', this)">
                            <span class="material-icons">visibility_off</span>
                        </button>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="conformPassword">Confirm Password</label>
                    <div style="position: relative;">
                        <input type="password" id="conformPassword" required class="inputField" minlength="8">
                        <button type="button" class="toggle-password-btn"
                            onclick="togglePasswordVisibility('conformPassword', this)">
                            <span class="material-icons">visibility_off</span>
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
        <div id="statUser" class="settingPage hidden md:col-span-7">

            <h2 class="bigTitle text-white mb-4">My Statistics</h2>

            <div class="stats-grid grid grid-cols-1 md:grid-cols-2 gap-4">

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
    <div class="bg-gray-800 max-w-sm w-full rounded-xl border border-gray-700 p-6 shadow-2xl text-center">
        <div id="notif-icon-container" class="mx-auto flex items-center justify-center h-12 w-12 rounded-full mb-4"></div>
        <h3 id="notif-title" class="text-lg font-bold mb-2"></h3>
        <p id="notif-message" class="text-sm text-gray-400 mb-6"></p>
        <div id="notif-buttons" class="flex justify-center gap-3"></div>
    </div>
</div>

<script>
// Fetches the computed metric data payload from get_stats.php to populate DOM text elements asynchronously
async function loadStats() {
    const response = await fetch("/api/stats/get_stats.php");
    const stats = await response.json();

    document.getElementById("valueTime").textContent = stats.best_time ?? "-";
    document.getElementById("valueScore").textContent = stats.best_score ?? "-";
    document.getElementById("valueResponseTime").textContent = stats.avg_time ?? "-";
}

loadStats();

// Hides all inactive functional panels and applies active tab markup styling to the navigation choice
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

// Dynamically toggles input context between password and plain-text string formatting
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