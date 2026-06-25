<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/db.php';
include __DIR__ . '/components/header.php';

if (!isset($_SESSION['user_id'])): ?>
    <div class="max-w-md mx-auto mt-20 bg-gray-800 p-8 rounded-xl border border-gray-700 text-center space-y-4">
        <img src="/public/avatars/bee.png" class="w-20 h-20 mx-auto rounded-full border-2 border-cyan-400 object-cover">
        <h1 class="text-xl font-bold text-white">You are in Guest Mode</h1>
        <p class="text-gray-400 text-sm">Please sign in or create an account to customize your profile and save your scores.</p>
        <a href="/login.php" class="inline-block bg-cyan-500 hover:bg-cyan-600 text-black font-bold px-6 py-2 rounded-lg transition">Sign In</a>
    </div>
<?php 
include __DIR__ . '/components/footer.php';
exit; 
endif;

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// AUTO LOAD avatars from folder
$avatarDir = __DIR__ . "/public/avatars/";
$avatars = array_values(array_diff(scandir($avatarDir), ['.', '..']));
?>

<?php $page_title = "Profile"; ?>

<div class="max-w-3xl mx-auto mt-10 bg-gray-800 p-6 rounded-xl border border-gray-700">

    <h1 class="text-2xl font-bold text-cyan-400 mb-6">My Profile</h1>

    <!-- USER INFO -->
    <div class="flex items-center gap-6 mb-8">

        <img id="avatarPreview"
             src="/public/avatars/<?= htmlspecialchars($user['avatar']) ?>"
             class="w-24 h-24 rounded-full border-4 border-cyan-400 object-cover">

        <div>
            <p class="text-lg font-bold text-white"><?= htmlspecialchars($user['username']) ?></p>
            <p class="text-gray-400"><?= htmlspecialchars($user['email']) ?></p>
            <p class="text-sm text-gray-500">Role: <?= htmlspecialchars($user['role']) ?></p>
        </div>

    </div>

    <!-- AVATAR GRID -->
    <form id="avatarForm">

        <input type="hidden"
            id="selectedAvatar"
            name="avatar"
            value="<?= htmlspecialchars($user['avatar'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <div id="avatarChoices" class="avatar-selector textWhite">

            <h3>Choose your new avatar</h3>

            <div class="avatar-grid">

                <?php
                    $files = glob("public/avatars/*.png");

                    foreach ($files as $file):

                        $name = basename($file, ".png");
                        $url = "public/avatars/" . $name . ".png";

                ?>

                    <img
                        src="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                        class="avatar-option"
                        data-avatar="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                    >

                <?php endforeach; ?>

            </div>

            <div class="modal-btn">
                <button type="submit" class="btn">
                    Save Avatar
                </button>
            </div>

        </div>

</form>

</div>

<script>
const hiddenInput = document.getElementById("selectedAvatar");

// avatar actuellement enregistré
const currentAvatar = hiddenInput.value;

// mise en évidence de l'avatar actuel
document.querySelectorAll(".avatar-option").forEach(img => {

    if(img.dataset.avatar === currentAvatar){
        img.classList.remove("border-transparent");
        img.classList.add("border-green-500");
    }

    img.addEventListener("click", () => {

        document.querySelectorAll(".avatar-option").forEach(i => {
            i.classList.remove("border-green-500");
            i.classList.add("border-transparent");
        });

        img.classList.remove("border-transparent");
        img.classList.add("border-green-500");

        hiddenInput.value = img.dataset.avatar;
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

        if(data.status === "success"){

            alert("Avatar saved !");
            location.reload();

        }else{

            alert(data.message || "Save failed");
        }

    } catch(err){

        console.error(err);
        alert("Server error");
    }

});
</script>

<?php include __DIR__ . '/components/footer.php'; ?>