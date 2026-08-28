<?php
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../controllers/BookmarkController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action     = $_GET['action'] ?? $_POST['action'] ?? '';
$controller = new BookmarkController();

$allowedActions = ['toggle', 'ids'];

if (!in_array($action, $allowedActions, true)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
    exit;
}

$controller->$action();
?>