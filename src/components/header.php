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
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white h-screen overflow-hidden font-sans grid grid-rows-[auto_1fr_auto]">
    
    <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <nav class="bg-gray-800 border-b border-gray-700 px-4 py-3 shadow-md">
        <div class="max-w-5xl mx-auto flex justify-between items-center">
            <a href="/index.php" class="text-xl font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500 tracking-wider">
                brainSKwiz
            </a>
            <div class="flex items-center gap-2">
                <a href="/admin_questions.php" class="px-3 py-1.5 rounded-lg text-sm font-semibold transition <?php echo strpos($_SERVER['SCRIPT_NAME'], 'admin_questions') !== false ? 'bg-blue-600 text-white shadow' : 'text-gray-300 hover:bg-gray-700'; ?>">
                    Questions
                </a>
                <a href="/admin_categories.php" class="px-3 py-1.5 rounded-lg text-sm font-semibold transition <?php echo strpos($_SERVER['SCRIPT_NAME'], 'admin_categories') !== false ? 'bg-blue-600 text-white shadow' : 'text-gray-300 hover:bg-gray-700'; ?>">
                    Catégories
                </a>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <main class="w-full overflow-y-auto p-4 md:p-6">
        <div class="max-w-5xl w-full mx-auto">