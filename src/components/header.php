<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

// Gestion des bascules de mode via l'URL (?action=toggle_preview)
if (isset($_GET['action']) && $_GET['action'] === 'toggle_preview' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $_SESSION['preview_mode'] = !($_SESSION['preview_mode'] ?? false);
    
    // Redirection propre pour nettoyer l'URL après le clic
    $redirect = $_SERVER['PHP_SELF'];
    if (!empty($_SERVER['QUERY_STRING'])) {
        // On retire l'action de la query string pour éviter les boucles
        parse_str($_SERVER['QUERY_STRING'], $query_params);
        unset($query_params['action']);
        if (!empty($query_params)) {
            $redirect .= '?' . http_build_query($query_params);
        }
    }
    header("Location: " . $redirect);
    exit;
}

// Détection du statut réel
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_user = isset($_SESSION['role']) && $_SESSION['role'] === 'user';
$is_logged = isset($_SESSION['user_id']);

// Est-ce que l'admin a activé la vue utilisateur ?
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
        <img src="/public/logo.png" alt="Logo" id="logoNavBar">
    </a>

    <button class="hamburger" id="hamburger">
        <span class="material-icons">menu</span>
    </button>

    <ul class="nav-menu" id="nav-menu">

        

        <?php if (!$is_admin || $preview_mode): ?>
            <li><a href="/index.php">Home</a></li>
            <?php if ($is_user || $preview_mode): ?>
                <li><a href="/index.php">History</a></li>
                <li><a href="/index.php">Rank</a></li>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($preview_mode): ?>
            <li>
                <!-- TODO -->
                <a href="?action=toggle_preview" class="bg-amber-500/20 text-amber-400 border border-amber-500/30 px-3 py-1 rounded-lg hover:bg-amber-500 hover:text-black transition font-bold flex items-center gap-1 animate-pulse">
                    <span class="material-icons text-sm mr-1">admin_panel_settings</span> 
                    Admin Mode
                </a>
            </li>
        <?php endif; ?>

        <?php if ($is_admin && !$preview_mode): ?>
            <li><a href="/admin_dashboard.php">Quiz</a></li>
            <li><a href="/admin_questions.php">Questions</a></li>
            <li><a href="/admin_categories.php">Categories</a></li>
            <li><a href="/admin_users.php">Users</a></li>
            <li>
                <!-- TODO -->
                <a href="?action=toggle_preview" class="bg-primary/20 text-primary border border-primary/30 px-3 py-1 rounded-lg hover:bg-primary hover:text-white transition font-medium flex items-center gap-1">
                    <span class="material-icons text-sm mr-2">visibility</span> 
                    User Mode
                </a>
            </li>
        <?php endif; ?>

        <?php if($is_logged): ?>
            <li>
                <!-- TODO -->
                <a href="/settings.php" class="profile flex justify-center items-center gap-2 m-0">
                    <img 
                        id="navbarAvatar"
                        src="/public/avatars/<?= htmlspecialchars($_SESSION['avatar'] ?? 'bee.png') ?>"
                        alt="Avatar"
                        class="w-9 h-9 rounded-full border-2 border-cyan-400 object-cover"
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
            <!-- <li><a href="/register.php">Register</a></li> -->
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
    const toggle = document.getElementById('theme-toggle');
    const icon = document.getElementById('theme-icon');
    const logo = document.getElementById('logoNavBar');

    const savedTheme = localStorage.getItem('theme') || 'light';

    document.documentElement.setAttribute('data-theme', savedTheme);
    toggle.checked = savedTheme === 'dark';

    updateIcon(savedTheme);
    updateLogo(savedTheme);

    toggle.addEventListener('change', () => {
        const theme = toggle.checked ? 'dark' : 'light';

        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);

        updateIcon(theme);
        updateLogo(theme);
    });

    function updateIcon(theme) {
        icon.textContent = theme === 'dark'
            ? 'dark_mode'
            : 'sunny';
    }

    function updateLogo(theme) {
    logo.src = theme === 'dark'
        ? '/public/logo-dark-theme.svg'
        : '/public/logo.png';
    }

    //navBar script
    const hamburger = document.getElementById('hamburger');
    const navMenu = document.getElementById('nav-menu');

    hamburger.addEventListener('click', () => {
        navMenu.classList.toggle('active');
    });
</script>