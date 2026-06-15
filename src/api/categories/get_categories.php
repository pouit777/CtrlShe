<?php
// src/api/categories.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, DELETE, GET');

require_once __DIR__ . '/../../config/db.php';

// Vérification rapide de sécurité (à adapter selon ta session)
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($method) {
        case 'POST': // CREATE
            if (empty($input['label'])) {
                echo json_encode(['status' => 'error', 'message' => 'Label is required.']);
                exit;
            }
            $stmt = $pdo->prepare("INSERT INTO categories (label) VALUES (:label)");
            $stmt->execute(['label' => trim($input['label'])]);
            echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);
            break;

        case 'PUT': // UPDATE
            if (empty($input['id']) || empty($input['label'])) {
                echo json_encode(['status' => 'error', 'message' => 'Missing ID or Label.']);
                exit;
            }
            $stmt = $pdo->prepare("UPDATE categories SET label = :label WHERE id = :id");
            $stmt->execute([
                'label' => trim($input['label']),
                'id' => intval($input['id'])
            ]);
            echo json_encode(['status' => 'success']);
            break;

        case 'DELETE': // DELETE
            if (empty($input['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Missing category ID.']);
                exit;
            }
            // Note: ON DELETE CASCADE dans ton init.sql supprimera automatiquement les questions liées !
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
            $stmt->execute(['id' => intval($input['id'])]);
            echo json_encode(['status' => 'success']);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
            break;
    }
} catch (\PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}