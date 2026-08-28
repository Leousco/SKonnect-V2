<?php
// backend/controllers/ToggleSupportController.php
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
RoleMiddleware::requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/SupportModel.php';

$db    = new Database();
$conn  = $db->getConnection();
$model = new SupportModel($conn);

$user_id   = $_SESSION['user_id'] ?? null;
$type      = $_POST['type']      ?? '';
$target_id = (int)($_POST['id']  ?? 0);

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}
if (!$target_id || !in_array($type, ['thread', 'comment'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
    exit;
}

$result = $type === 'thread'
    ? $model->toggleThread($target_id, (int)$user_id)
    : $model->toggleComment($target_id, (int)$user_id);

echo json_encode(['status' => 'success', 'supported' => $result['supported'], 'total' => $result['total']]);