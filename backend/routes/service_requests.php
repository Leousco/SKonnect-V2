<?php
// backend/routes/service_requests.php
// Resident-facing: submit a new service application or reapply after action_required.

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../controllers/ServiceRequestController.php';

RoleMiddleware::requireAuth(); // any logged-in user (resident)

header('Content-Type: application/json; charset=utf-8');
ob_clean();

$action     = $_GET['action'] ?? ($_POST['action'] ?? '');
$controller = new ServiceRequestController();
$residentId = (int)($_SESSION['user_id'] ?? 0);

if (!$residentId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

try {
    switch ($action) {

        case 'submit':
            echo json_encode(
                $controller->submit(
                    $_POST,
                    $_FILES['documents'] ?? null,
                    $residentId
                )
            );
            break;

        // Resident updates + resubmits an application that is in 'action_required' status
        case 'reapply':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Application ID is required.']);
                break;
            }

            // Decode the list of document IDs to remove (sent as JSON string)
            $removeDocsRaw = $_POST['remove_docs'] ?? '[]';
            $removeDocs    = json_decode($removeDocsRaw, true);
            if (!is_array($removeDocs)) $removeDocs = [];

            echo json_encode(
                $controller->reapply(
                    $id,
                    $residentId,
                    $_POST,
                    $_FILES['documents'] ?? null,
                    $removeDocs
                )
            );
            break;

        case 'cancel':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Application ID is required.']);
                break;
            }
            echo json_encode($controller->cancel($id, $residentId));
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