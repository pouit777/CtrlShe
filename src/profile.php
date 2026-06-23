<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// 🔥 AUTO LOAD avatars from folder (IMPORTANT)
$avatarDir = __DIR__ . "/public/avatars/";
$avatars = array_values(array_diff(scandir($avatarDir), ['.', '..']));
?>

<?php $page_title = "Profile"; ?>
<?php include __DIR__ . '/components/header.php'; ?>

<div class="max-w-3xl mx-auto mt-10 bg-gray-800 p-6 rounded-xl border border-gray-700">

    <h1 class="text-2xl font-bold text-cyan-400 mb-6">My Profile</h1>

    <!-- USER INFO -->
    <div class="flex items-center gap-6 mb-8">

        <img id="avatarPreview"
             src="/public/avatars/<?= htmlspecialchars($user['avatar']) ?>"
             class="w-24 h-24 rounded-full border-4 border-cyan-400 object-cover">

        <div>
            <p class="text-lg font-bold"><?= htmlspecialchars($user['username']) ?></p>
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
                        class="w-14 h-14 rounded-full cursor-pointer border-2 border-transparent hover:border-cyan-400 transition avatar-option"
                        data-avatar="<?= $a ?>"
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
const preview = document.getElementById("avatarPreview");
const hiddenInput = document.getElementById("selectedAvatar");

// selection avatar
document.querySelectorAll(".avatar-option").forEach(img => {
    img.addEventListener("click", () => {

        document.querySelectorAll(".avatar-option").forEach(i =>
            i.classList.remove("border-cyan-400")
        );

        img.classList.add("border-cyan-400");

        const avatar = img.dataset.avatar;
        hiddenInput.value = avatar;
        preview.src = "/public/avatars/" + avatar;
    });
});

// SAVE
document.getElementById("avatarForm").addEventListener("submit", async (e) => {
    e.preventDefault();

    const res = await fetch("/api/account/update_avatar.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({
            avatar: hiddenInput.value
        })
    });

    const data = await res.json();

    if (data.status === "success") {
        location.reload();
    } else {
        alert("Error saving avatar");
    }
});
</script>

<?php include __DIR__ . '/components/footer.php'; ?>