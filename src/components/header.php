<?php
// Initialize session management globally if no active runtime session contexts are detected
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Import core database engine configuration layer
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
    
    <main class="w-full overflow-y-auto p-4 md:p-6">
        <div class="max-w-5xl w-full mx-auto">