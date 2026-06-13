<?php
// src/config/db.php
// Manages the unique, centralized Data Objects (PDO) layer connection to the database.

$host = 'db';
$db   = 'brainskwiz_db';
$user = 'brainskwiz_user';
$pass = 'brainskwiz_password_123';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (\PDOException $e) {
    // Developer Sandbox Safeguard: Echo direct debugging metrics over JSON stream on execution failure
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}