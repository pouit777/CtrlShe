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
    <form id="avatarForm" class="space-y-4">

        <input type="hidden" id="selectedAvatar" value="<?= htmlspecialchars($user['avatar']) ?>">

        <label class="text-sm text-gray-300">Choose your avatar</label>

        <div class="grid grid-cols-6 gap-3 bg-gray-900 p-4 rounded-lg border border-gray-700">

            <?php foreach ($avatars as $a): ?>
                <?php if (is_file($avatarDir . $a)): ?>

                    <img
                        src="/public/avatars/<?= $a ?>"
                        class="w-14 h-14 rounded-full cursor-pointer border-2 border-transparent hover:border-green-500 transition avatar-option"                        data-avatar="<?= $a ?>"
                    >

                <?php endif; ?>
            <?php endforeach; ?>

        </div>

        <button type="submit"
                class="bg-cyan-500 hover:bg-cyan-600 px-4 py-2 rounded-lg text-black font-bold">
            Save
        </button>

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