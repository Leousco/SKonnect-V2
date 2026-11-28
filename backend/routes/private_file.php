<?php
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../services/StorageService.php';

RoleMiddleware::requireAuth();

$type = $_GET['type'] ?? '';
$id = (int) ($_GET['id'] ?? 0);
$role = RoleMiddleware::currentRole();
$userId = (int) ($_SESSION['user_id'] ?? 0);

if (!in_array($type, ['application_document', 'fulfillment'], true) || $id <= 0) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Invalid file identifier.']);
    exit;
}

$db = (new Database())->getConnection();

if ($type === 'application_document') {
    $stmt = $db->prepare("
        SELECT ad.file_path, sa.resident_id
        FROM application_documents ad
        JOIN service_applications sa ON sa.id = ad.application_id
        WHERE ad.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $stmt = $db->prepare("
        SELECT fulfillment_file AS file_path, resident_id
        FROM service_applications
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$row) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'File not found.']);
    exit;
}

if (!in_array($role, ['admin', 'sk_officer'], true) && (int) $row['resident_id'] !== $userId) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'You are not authorized to access this resource.']);
    exit;
}

$reference = (string) ($row['file_path'] ?? '');
header('Cache-Control: no-store');

if (!str_starts_with($reference, 'sb-private:')) {
    header('Location: ' . publicAssetUrl($reference), true, 302);
    exit;
}

try {
    $signedUrl = StorageService::createSignedUrl($reference, 120);
} catch (RuntimeException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Unable to open private file.']);
    exit;
}

header('Location: ' . $signedUrl, true, 302);
