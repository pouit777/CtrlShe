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

<div style="padding: 1rem;">
    <div class="titleBox">
        <div>
            <h1 class="titleText">Account Settings</h1>
            <p class="subTitle">
                Logged in as : <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?> (<span class="uppercase"><?php echo htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8'); ?></span>)</strong>
            </p>
        </div>
    </div>

    <div class="settings">
        <div class="choices">
            <ul>
                <li id="profileSettingBtn" class="active"><button onclick="setActive('profileSetting')">Profile<span class="material-icons">person</span></button></li>
                <li id="passwordSettingBtn"><button onclick="setActive('passwordSetting')">Password<span class="material-icons">lock</span></button></li>
                <li id="statUserBtn"><button onclick="setActive('statUser')">Stats<span class="material-icons">leaderboard</span></button></li>
            </ul>
        </div>

        <div id="profileSetting" class="settingPage activeWindow">
            <h1 class="textWhite">My Profile</h1>

            <!-- USER INFO -->
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

            <!-- PSEUDO -->
            <form id="usernameForm">
                <label>Username</label>
                <input style="margin-bottom: 1rem;" class="inputField" type="text" id="username" value="<?= htmlspecialchars($user['username']) ?>">
                <button type="submit" class="btn">Save</button>
            </form>

            <!-- AVATAR GRID -->
            <form id="avatarForm">
                <input type="hidden"
                    id="selectedAvatar"
                    name="avatar"
                    value="<?= htmlspecialchars($user['avatar'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                <div id="avatarChoices" class="avatar-section textWhite">
                    <h3>Choose your new avatar</h3>
                    <div class="avatar-grid">
                        <?php
                        $files = glob(__DIR__ . "/public/avatars/*.png");

                        foreach ($files as $file):
                            $filename = basename($file);
                        ?>
                            <img
                                src="/public/avatars/<?= htmlspecialchars($filename) ?>"
                                class="avatar-item"
                                data-avatar="<?= htmlspecialchars($filename) ?>"
                            >
                        <?php endforeach; ?>
                    </div>

                    <div class="modal-btn">
                        <button type="submit" class="btn">Save Avatar</button>
                    </div>
                </div>
            </form>
        </div>

        <div id="passwordSetting" class="settingPage hidden">
            <div class="titleText modal-header">
                <h3>Change password</h3>
            </div>
            <form id="resetPassword">
                <div>
                    <label for="password">New Password</label>
                    <input type="password" id="newPassword" required placeholder="••••••••"
                        class="inputField">
                </div>

                <div>
                    <label for="password">Confirm Password</label>
                    <input type="password" id="conformPassword" required placeholder="••••••••"
                        class="inputField">
                </div>

                <div class="modal-btn">
                    <button type="button" class="inputField">Cancel</button>
                    <button type="submit" class="btn">Reset Password</button>
                </div>
            </form>
        </div>

        <div id="statUser" class="settingPage hidden">
            <h3>Best time : </h3><h3 id="valueTime"></h3>
            <h3>Best score : </h3><h3 id="valueScore"></h3>
            <h3>Average response time : </h3><h3 id="valueResponseTime"></h3>
            <h3>Rate of correct answers : </h3><h3 id="valueResponses"></h3>
        </div>
    </div>
</div>

<script>
    async function loadStats() {
        const response = await fetch("/api/stats/get_stats.php");
        const stats = await response.json();

        document.getElementById("valueTime").textContent = stats.best_time ?? "-";

        document.getElementById("valueScore").textContent = stats.best_score ?? "-";

        document.getElementById("valueResponseTime").textContent = stats.avg_time ?? "-";

        document.getElementById("valueResponses").textContent = (stats.success_rate ?? 0) + " %";
    }

    loadStats();

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

    function diplayAvatarChoices(){
        document.getElementById("avatarChoices").classList.remove("hidden");
    }

    const hiddenInput = document.getElementById("selectedAvatar");
    const avatarPreview = document.getElementById("avatarPreview");
    const navbarAvatar = document.getElementById("navbarAvatar");

    document.querySelectorAll(".avatar-item").forEach(img => {

        if (img.dataset.avatar === hiddenInput.value) {
            img.classList.remove("border-transparent");
            img.classList.add("border-green-500");
        }

        img.addEventListener("click", async () => {

            const avatar = img.dataset.avatar;
            if (avatar === hiddenInput.value) {
                return;
            }

            document.querySelectorAll(".avatar-item").forEach(i => {
                i.classList.remove("border-green-500");
                i.classList.add("border-transparent");
            });

            img.classList.remove("border-transparent");
            img.classList.add("border-green-500");

            console.log("Avatar sélectionné :", avatar);
            console.log("URL :", `/public/avatars/${avatar}`);
            avatarPreview.src = `/public/avatars/${avatar}`;

            try {
                const response = await fetch("/api/account/update_avatar.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        avatar: avatar
                    })
                });

                const data = await response.json();

                if (data.status === "success") {
                    hiddenInput.value = avatar;
                    avatarPreview.src = `/public/avatars/${avatar}`;
                    if (navbarAvatar) {
                        navbarAvatar.src = `/public/avatars/${avatar}`;
                    }
                } 
                else {
                    alert(data.message || "Save failed");
                }

            } catch (err) {
                console.error(err);
                alert("Server error");
            }
        });
    });

    // SAVE
    document.getElementById("avatarForm").addEventListener("submit", async (e) => {

        e.preventDefault();

        try {

            const response = await fetch("/api/account/update_avatar.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    avatar: hiddenInput.value
                })
            });

            const data = await response.json();

            if (data.status === "success") {
                location.reload();
            } else {
                alert(data.message || "Save failed");
            }

        } catch (err) {

            console.error(err);
            alert("Server error");
        }

    });

    document.getElementById("usernameForm").addEventListener("submit", async (e) => {

        e.preventDefault();

        const username = document.getElementById("username").value.trim();

        if(username.length < 3){
            alert("Username too short");
            return;
        }

        try {

            const response = await fetch("/api/account/update_username.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    username: username
                })
            });

            const data = await response.json();

            if(data.status === "success"){
                location.reload();
            } else {
                alert(data.message);
            }

        } catch(err){
            console.error(err);
            alert("Server error");
        }

    });
</script>