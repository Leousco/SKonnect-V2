<?php



require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../controllers/ServiceRequestController.php';

RoleMiddleware::requireRole('sk_officer');

header('Content-Type: application/json; charset=utf-8');
ob_clean();

$action     = $_GET['action'] ?? ($_POST['action'] ?? '');
$controller = new ServiceRequestController();


$officerId = (int)($_SESSION['user_id'] ?? 0);

try {
    switch ($action) {

        
        case 'list':
            $filters = [
                'status'   => $_GET['status']   ?? '',
                'category' => $_GET['category'] ?? '',
                'search'   => $_GET['search']   ?? '',
            ];
            $data = $controller->getAll($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        
        case 'view':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Application ID is required.']);
                break;
            }
            $application = $controller->getById($id);
            if (!$application) {
                echo json_encode(['success' => false, 'message' => 'Application not found.']);
                break;
            }
            echo json_encode(['success' => true, 'data' => $application]);
            break;

        
        case 'get_approval_message':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Application ID is required.']);
                break;
            }
            echo json_encode($controller->getApprovalMessage($id));
            break;

        
        case 'counts':
            echo json_encode(['success' => true, 'data' => $controller->getStatusCounts()]);
            break;

        
        case 'update_status':
            $id     = (int)($_POST['id']     ?? 0);
            $status = trim($_POST['status']  ?? '');
            $note   = trim($_POST['note']    ?? '');

            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Application ID is required.']);
                break;
            }

            
            $fulfillmentFile = ($status === 'approved' && !empty($_FILES['fulfillment_file']))
                ? $_FILES['fulfillment_file']
                : null;

            echo json_encode($controller->updateStatus($id, $status, $officerId, $note, $fulfillmentFile));
            break;

        
        case 'add_note':
            $id   = (int)($_POST['id']   ?? 0);
            $note = trim($_POST['note']  ?? '');

            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Application ID is required.']);
                break;
            }
            if ($note === '') {
                echo json_encode(['success' => false, 'message' => 'Note text is required.']);
                break;
            }
            echo json_encode($controller->addNote($id, $officerId, $note));
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