<?php
// backend/routes/services.php

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../controllers/ServiceController.php';

RoleMiddleware::requireRole('sk_officer');

header('Content-Type: application/json; charset=utf-8');
ob_clean();

$action     = $_GET['action'] ?? ($_POST['action'] ?? '');
$controller = new ServiceController();
$officerId  = (int) ($_SESSION['user_id'] ?? 0);

try {
    switch ($action) {

        case 'list':
            $filters = [
                'category'     => $_GET['category']     ?? '',
                'service_type' => $_GET['service_type'] ?? '',
                'status'       => $_GET['status']       ?? '',
                'search'       => $_GET['search']       ?? '',
            ];
            echo json_encode(['success' => true, 'data' => $controller->getAll($filters)]);
            break;

        case 'create':
            echo json_encode($controller->create($_POST, $_FILES['attachments'] ?? null, $officerId));
            break;

        case 'update':
            $id = (int) ($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'errors' => ['Service ID is required.']]); break; }
            echo json_encode($controller->update($id, $_POST, $_FILES['attachments'] ?? null));
            break;

        case 'toggle':
            $id = (int) ($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Service ID is required.']); break; }
            echo json_encode($controller->toggleStatus($id));
            break;

        case 'delete':
            $id = (int) ($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Service ID is required.']); break; }
            echo json_encode($controller->delete($id));
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
    ]);
}