<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'brainSKwiz'; ?></title>
    <link rel="icon" type="image/png" href="/public/logo-icon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="/logo-icon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white h-screen overflow-hidden font-sans grid grid-rows-[auto_1fr_auto]">
    
    <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <nav class="bg-gray-800 border-b border-gray-700 px-4 py-3 shadow-md relative z-50 flex-shrink-0">
        <div class="max-w-5xl mx-auto flex justify-between items-center">
            <a href="/index.php" class="block transition hover:opacity-90">
                <img src="../public/logo-white.png" alt="brainSKwiz Logo" class="h-12 w-auto">
            </a>
            
            <div class="hidden md:flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <a href="/admin_questions.php" class="px-3 py-1.5 rounded-lg text-sm font-semibold transition <?php echo strpos($_SERVER['SCRIPT_NAME'], 'admin_questions') !== false ? 'bg-blue-600 text-white shadow' : 'text-gray-300 hover:bg-gray-700'; ?>">
                        Questions
                    </a>
                    <a href="/admin_categories.php" class="px-3 py-1.5 rounded-lg text-sm font-semibold transition <?php echo strpos($_SERVER['SCRIPT_NAME'], 'admin_categories') !== false ? 'bg-blue-600 text-white shadow' : 'text-gray-300 hover:bg-gray-700'; ?>">
                        Categories
                    </a>
                    <a href="/admin_dashboard.php" class="px-3 py-1.5 rounded-lg text-sm font-semibold transition <?php echo strpos($_SERVER['SCRIPT_NAME'], 'admin_categories') !== false ? 'bg-blue-600 text-white shadow' : 'text-gray-300 hover:bg-gray-700'; ?>">
                        Quiz Dashboard
                    </a>
                </div>
                
                <span class="h-5 w-[1px] bg-gray-700"></span>

                <a href="/logout.php" title="Log out" class="text-gray-400 gap-1 hover:text-red-400 hover:bg-red-500/10 p-2 rounded-lg transition duration-150 flex items-center justify-center">
                    Logout
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </a>
            </div>

            <button id="burger-menu-btn" class="block md:hidden text-gray-400 hover:text-white p-2 rounded-lg transition focus:outline-none" aria-label="Toggle Menu">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <div id="mobile-menu" class="hidden absolute top-full left-0 w-full bg-gray-800 border-b border-gray-700 shadow-xl flex flex-col p-4 space-y-3 md:hidden transition-all duration-200 opacity-0 transform -translate-y-2">
            <a href="/admin_questions.php" class="px-4 py-2.5 rounded-xl text-base font-semibold transition <?php echo strpos($_SERVER['SCRIPT_NAME'], 'admin_questions') !== false ? 'bg-blue-600 text-white shadow' : 'text-gray-300 hover:bg-gray-700/60'; ?>">
                Questions
            </a>
            <a href="/admin_categories.php" class="px-4 py-2.5 rounded-xl text-base font-semibold transition <?php echo strpos($_SERVER['SCRIPT_NAME'], 'admin_categories') !== false ? 'bg-blue-600 text-white shadow' : 'text-gray-300 hover:bg-gray-700/60'; ?>">
                Categories
            </a>
            <a href="/admin_dashboard.php" class="px-4 py-2.5 rounded-xl text-base font-semibold transition <?php echo strpos($_SERVER['SCRIPT_NAME'], 'admin_categories') !== false ? 'bg-blue-600 text-white shadow' : 'text-gray-300 hover:bg-gray-700/60'; ?>">
                Quiz Dashboard
            </a>
            <hr class="border-gray-700 my-1">
            <a href="/logout.php" class="px-4 py-2.5 rounded-xl text-base font-semibold text-red-400 hover:bg-red-500/10 transition flex items-center gap-2">
                Logout
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </a>
        </div>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const burgerBtn = document.getElementById('burger-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');

            if (burgerBtn && mobileMenu) {
                burgerBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isHidden = mobileMenu.classList.contains('hidden');
                    
                    if (isHidden) {
                        mobileMenu.classList.remove('hidden');
                        setTimeout(() => {
                            mobileMenu.classList.remove('opacity-0', '-translate-y-2');
                        }, 10);
                    } else {
                        mobileMenu.classList.add('opacity-0', '-translate-y-2');
                        mobileMenu.addEventListener('transitionend', function handler() {
                            mobileMenu.classList.add('hidden');
                            mobileMenu.removeEventListener('transitionend', handler);
                        });
                    }
                });

                document.addEventListener('click', () => {
                    if (!mobileMenu.classList.contains('hidden')) {
                        mobileMenu.classList.add('opacity-0', '-translate-y-2');
                        mobileMenu.classList.add('hidden');
                    }
                });
            }
        });
    </script>
    <?php endif; ?>

    <main class="w-full overflow-y-auto p-4 md:p-6">
        <div class="max-w-5xl w-full mx-auto">