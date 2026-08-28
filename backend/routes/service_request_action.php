<?php
/**
 * service_request_action.php
 * Handles approve / reject / complete / action_required actions.
 * Place at: /backend/routes/service_request_action.php
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../config/database.php';

RoleMiddleware::requireAdmin();

$db   = new Database();
$conn = $db->getConnection();

$input = json_decode(file_get_contents('php://input'), true);

$appId   = isset($input['id'])      ? (int) $input['id']           : 0;
$action  = isset($input['action'])  ? trim($input['action'])        : '';
$note    = isset($input['note'])    ? trim($input['note'])          : '';
$officer = $_SESSION['user_id']     ?? 0;

$allowed = ['approved', 'rejected', 'action_required'];

if (!$appId || !in_array($action, $allowed)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

try {
    // Update status
    $stmt = $conn->prepare("
        UPDATE service_applications
        SET status = :status, updated_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([':status' => $action, ':id' => $appId]);

    // Save note if provided
    if ($note !== '' && $officer) {
        $stmt = $conn->prepare("
            INSERT INTO application_notes (application_id, officer_id, note, created_at)
            VALUES (:app_id, :officer, :note, NOW())
        ");
        $stmt->execute([
            ':app_id'  => $appId,
            ':officer' => $officer,
            ':note'    => $note,
        ]);
    }

    echo json_encode(['status' => 'success', 'new_status' => $action]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}