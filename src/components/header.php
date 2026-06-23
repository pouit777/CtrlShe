<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_user = isset($_SESSION['role']) && $_SESSION['role'] === 'user';

$is_logged = isset($_SESSION['user_id']);

?>

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'brainSKwiz'; ?></title>

    <link rel="icon" href="/public/logo-icon.ico">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="/public/js/tailwind-config.js"></script>

    <link rel="stylesheet" href="/css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://api.fontshare.com/v2/css?f[]=clash-display@400,500,600,700&display=swap" rel="stylesheet">
</head>

<body>

<nav class="navbar">

    <a href="index.php" class="logo">
        <img src="/public/logo.png" alt="Logo" id="logoNavBar">
    </a>

    <button class="hamburger" id="hamburger">
        <span class="material-icons">menu</span>
    </button>

    <ul class="nav-menu" id="nav-menu">

        <li><a href="/index.php">Home</a></li>

        <?php if($is_user): ?>
            <li><a href="/index.php">History</a></li>
            <li><a href="/index.php">Rank</a></li>
        <?php endif; ?>

        <?php if($is_admin): ?>
            <li><a href="/admin_questions.php">Questions</a></li>
            <li><a href="/admin_categories.php">Categories</a></li>
            <li><a href="/admin_dashboard.php">Quiz Dashboard</a></li>
            <li><a href="/admin_users.php">Users</a></li>
        <?php endif; ?>

        <?php if($is_logged): ?>
            <li>
                <a href="/profile.php" class="profile flex justify-center items-center gap-2 m-0">
                    <img src="/public/avatars/<?= htmlspecialchars($_SESSION['avatar'] ?? 'bee.png') ?>"
                         class="w-9 h-9 rounded-full border-2 border-cyan-400 object-cover">
                    <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                </a>
            </li>
            <li><a href="/settings.php">Settings</a></li>
            <li>
                <a href="/logout.php" class="logout flex justify-center items-center gap-2 m-0">
                    Logout  
                    <span class="material-icons logout">logout</span>
                </a>
            </li>
        <?php else: ?>
            <li><a href="/login.php">Login</a></li>
            <li><a href="/register.php">Register</a></li>
        <?php endif; ?>

        <li>
            <label class="switch">
                <input type="checkbox" id="theme-toggle">
                <span class="slider round">
                    <span id="theme-icon" class="material-icons">sunny</span>
                </span>
            </label>
        </li>

    </ul>

</nav>

<script>
    const toggle = document.getElementById('theme-toggle');
    const icon = document.getElementById('theme-icon');

    const savedTheme = localStorage.getItem('theme') || 'light';

    document.documentElement.setAttribute('data-theme', savedTheme);
    toggle.checked = savedTheme === 'dark';

    updateIcon(savedTheme);

    toggle.addEventListener('change', () => {
        const theme = toggle.checked ? 'dark' : 'light';

        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);

        updateIcon(theme);
    });

    function updateIcon(theme) {
        icon.textContent = theme === 'dark'
            ? 'dark_mode'
            : 'sunny';
    }

    //navBar script
    const hamburger = document.getElementById('hamburger');
    const navMenu = document.getElementById('nav-menu');

    hamburger.addEventListener('click', () => {
    navMenu.classList.toggle('active');
});
</script>