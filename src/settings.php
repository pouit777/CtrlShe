<?php
    include 'components/header.php'
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
            <div class="avatar">
                <img src="public/avatar.png"/>
                <h3 class="textWhite"><?php echo $_SESSION['username'] ?></h3>
                <div class="modal-btn">
                    <button class="btn" onclick="editUsername()">Edit Username</button>
                    <button class="btn" onclick="diplayAvatarChoices()">Change Avatar</button>
                </div>
            </div>

            <div id="avatarChoices" class="avatar-selector hidden textWhite">
                <h3>Choose your new avatar</h3>
                <div id="avatarGrid" class="avatar-grid">
                    <?php
                        $files = glob("public/avatars/*.png");

                        foreach ($files as $file) {
                            $name = basename($file, ".png");
                            echo '
                            <label class="avatar-option">
                                <button type="radio" name="avatar" value="'.$name.'">
                                    <img src="'.$file.'" alt="'.$name.'">
                                </button>
                            </label>';
                        }
                    ?>
                </div>
            </div>
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
            <h3>Meilleur temps</h3>
            <h3>Temps moyen de réponse</h3>
            <h3>Taux de bonnes réponses</h3>
            <h3>Classement mondial</h3>
        </div>
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

    function diplayAvatarChoices(){
        document.getElementById("avatarChoices").classList.remove("hidden");
    }
</script>