<?php
// Initialize session state parameters if not active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Import persistent structural database abstraction layer
require_once __DIR__ . '/../config/db.php';

// RBAC Safe-Guard: Intercept administrative URL actions to toggle user preview mode layout configurations
if (isset($_GET['action']) && $_GET['action'] === 'toggle_preview' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $_SESSION['preview_mode'] = !($_SESSION['preview_mode'] ?? false);
    
   // Clean query streams using HTTP redirections to prevent refresh cycles or layout loops
    $redirect = $_SERVER['PHP_SELF'];
    if (!empty($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $query_params);
        unset($query_params['action']); // Remove action verb from parameters list
        if (!empty($query_params)) {
            $redirect .= '?' . http_build_query($query_params);
        }
    }
    header("Location: " . $redirect);
    exit;
}

// Map logical runtime state flags based on verified session footprints
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_user = isset($_SESSION['role']) && $_SESSION['role'] === 'user';
$is_logged = isset($_SESSION['user_id']);

// Determine if an administrator is currently simulating a standard user environment
$preview_mode = $is_admin && ($_SESSION['preview_mode'] ?? false);
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
        <img src="/public/logo-dark-theme.svg" alt="Logo" id="logoNavBar">
    </a>

    <button class="hamburger" id="hamburger">
        <span class="material-icons">menu</span>
    </button>

    <ul class="nav-menu" id="nav-menu">
        <?php if (!$is_admin || $preview_mode): ?>
            <li><a href="/index.php">Home</a></li>
            <?php if ($is_user || $preview_mode): ?>
                <li><a href="/history.php">History</a></li>
                <li><a href="/index.php">Rank</a></li>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($preview_mode): ?>
            <li class="">
                <a href="admin_dashboard.php?action=toggle_preview" class="bg-primary/20 text-primary border border-primary/30 px-3 py-1.5 rounded-lg hover:bg-primary hover:text-white transition font-medium inline-flex items-center gap-2">
                    <span class="material-icons text-base">admin_panel_settings</span> 
                    <span>Admin Mode</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if ($is_admin && !$preview_mode): ?>
            <li><a href="/admin_dashboard.php">Quiz</a></li>
            <li><a href="/admin_questions.php">Questions</a></li>
            <li><a href="/admin_categories.php">Categories</a></li>
            <li><a href="/admin_users.php">Users</a></li>
            <li class="">
                <a href="index.php?action=toggle_preview" class="bg-primary/20 text-primary border border-primary/30 px-3 py-1.5 rounded-lg hover:bg-primary hover:text-white transition font-medium inline-flex items-center gap-2">
                    <span class="material-icons text-base">visibility</span> 
                    <span>User Mode</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if($is_logged): ?>
            <li>
                <a href="/settings.php" class="profile flex justify-center items-center gap-2 m-0">
                    <img 
                        id="navbarAvatar"
                        src="/public/avatars/<?= htmlspecialchars($_SESSION['avatar'] ?? 'bee.png') ?>"
                        alt="Avatar"
                        class="w-9 h-9 rounded-full border-2 border-secondary object-cover"
                    >
                    <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                </a>
            </li>
            <li>
                <a href="/logout.php" class="logout flex justify-center items-center gap-2 m-0">
                    Logout  
                    <span class="material-icons logout">logout</span>
                </a>
            </li>
        <?php else: ?>
            <li><a href="/login.php">Login</a></li>
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

<main class="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col">

<script>
    // Theme switching management references
    const toggle = document.getElementById('theme-toggle');
    const icon = document.getElementById('theme-icon');
    const logo = document.getElementById('logoNavBar');

    // Pull or establish base user viewport preferences out of localStorage parameters
    const savedTheme = localStorage.getItem('theme') || 'light';

    document.documentElement.setAttribute('data-theme', savedTheme);
    toggle.checked = savedTheme === 'dark';

    updateIcon(savedTheme);
    updateLogo(savedTheme);

    // Bind event hooks to store updated theme preferences dynamically
    toggle.addEventListener('change', () => {
        const theme = toggle.checked ? 'dark' : 'light';

        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);

        updateIcon(theme);
        updateLogo(theme);
    });

    // Helper routine managing accessibility icon swaps
    function updateIcon(theme) {
        icon.textContent = theme === 'dark'
            ? 'dark_mode'
            : 'sunny';
    }

    // Helper routine handling active logo file extension swaps based on contrast modes
    function updateLogo(theme) {
    logo.src = theme === 'dark'
        ? '/public/logo-dark-theme.png'
        : '/public/logo.png';
    }

    // Interactive component script handling small viewport adaptive responsive navbar states
    const hamburger = document.getElementById('hamburger');
    const navMenu = document.getElementById('nav-menu');

    hamburger.addEventListener('click', () => {
        navMenu.classList.toggle('active');
    });
</script>