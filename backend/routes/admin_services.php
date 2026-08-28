<?php
// backend/routes/admin_services.php

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../config/database.php';

RoleMiddleware::requireAdmin();

header('Content-Type: application/json');

$db     = (new Database())->getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

/* ── helpers ────────────────────────────────────────────── */

function jsonSuccess($data = [], string $message = 'OK'): void {
    echo json_encode(['status' => 'success', 'message' => $message, 'data' => $data]);
    exit;
}

function jsonError(string $message, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

function sanitize(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)));
}

/* ── GET /admin_services.php?action=list ────────────────── */

if ($method === 'GET' && $action === 'list') {

    $category = $_GET['category'] ?? 'all';
    $status   = $_GET['status']   ?? 'all';
    $search   = trim($_GET['search'] ?? '');

    $sql    = 'SELECT id, name, category, service_type, description,
                      eligibility, processing_time, requirements, status,
                      max_capacity, current_count, created_at, updated_at
               FROM services WHERE 1=1';
    $params = [];

    if ($category !== 'all') {
        $sql      .= ' AND category = :category';
        $params[':category'] = $category;
    }
    if ($status !== 'all') {
        $sql      .= ' AND status = :status';
        $params[':status'] = $status;
    }
    if ($search !== '') {
        $sql      .= ' AND name LIKE :search';
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= ' ORDER BY created_at DESC';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonSuccess($services);
}

/* ── POST /admin_services.php?action=create ─────────────── */

if ($method === 'POST' && $action === 'create') {

    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    $name            = sanitize($body['name']            ?? '');
    $category        = sanitize($body['category']        ?? 'other');
    $service_type    = sanitize($body['service_type']    ?? 'document');
    $description     = sanitize($body['description']     ?? '');
    $approval_msg    = sanitize($body['approval_message']?? '');
    $eligibility     = sanitize($body['eligibility']     ?? '');
    $processing_time = sanitize($body['processing_time'] ?? '');
    $requirements    = sanitize($body['requirements']    ?? '');
    $status          = sanitize($body['status']          ?? 'active');
    $max_capacity    = isset($body['max_capacity']) && $body['max_capacity'] !== ''
                        ? (int) $body['max_capacity'] : null;
    $created_by      = $_SESSION['user_id'] ?? null;

    if ($name === '') jsonError('Service name is required.');

    $allowed_categories   = ['medical','education','scholarship','livelihood','assistance','legal','other'];
    $allowed_types        = ['document','appointment','info'];
    $allowed_statuses     = ['active','inactive'];

    if (!in_array($category,     $allowed_categories)) $category     = 'other';
    if (!in_array($service_type, $allowed_types))      $service_type = 'document';
    if (!in_array($status,       $allowed_statuses))   $status       = 'active';

    $stmt = $db->prepare('
        INSERT INTO services
            (name, category, service_type, description, approval_message,
             eligibility, processing_time, requirements, status,
             max_capacity, created_by)
        VALUES
            (:name, :category, :service_type, :description, :approval_message,
             :eligibility, :processing_time, :requirements, :status,
             :max_capacity, :created_by)
    ');

    $stmt->execute([
        ':name'             => $name,
        ':category'         => $category,
        ':service_type'     => $service_type,
        ':description'      => $description,
        ':approval_message' => $approval_msg,
        ':eligibility'      => $eligibility,
        ':processing_time'  => $processing_time,
        ':requirements'     => $requirements,
        ':status'           => $status,
        ':max_capacity'     => $max_capacity,
        ':created_by'       => $created_by,
    ]);

    $newId = $db->lastInsertId();
    jsonSuccess(['id' => $newId], 'Service created successfully.');
}

/* ── POST /admin_services.php?action=update ─────────────── */

if ($method === 'POST' && $action === 'update') {

    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    $id              = (int) ($body['id'] ?? 0);
    $name            = sanitize($body['name']            ?? '');
    $category        = sanitize($body['category']        ?? 'other');
    $service_type    = sanitize($body['service_type']    ?? 'document');
    $description     = sanitize($body['description']     ?? '');
    $approval_msg    = sanitize($body['approval_message']?? '');
    $eligibility     = sanitize($body['eligibility']     ?? '');
    $processing_time = sanitize($body['processing_time'] ?? '');
    $requirements    = sanitize($body['requirements']    ?? '');
    $status          = sanitize($body['status']          ?? 'active');
    $max_capacity    = isset($body['max_capacity']) && $body['max_capacity'] !== ''
                        ? (int) $body['max_capacity'] : null;

    if ($id === 0)   jsonError('Invalid service ID.');
    if ($name === '') jsonError('Service name is required.');

    $allowed_categories   = ['medical','education','scholarship','livelihood','assistance','legal','other'];
    $allowed_types        = ['document','appointment','info'];
    $allowed_statuses     = ['active','inactive'];

    if (!in_array($category,     $allowed_categories)) $category     = 'other';
    if (!in_array($service_type, $allowed_types))      $service_type = 'document';
    if (!in_array($status,       $allowed_statuses))   $status       = 'active';

    // Verify record exists
    $check = $db->prepare('SELECT id FROM services WHERE id = :id');
    $check->execute([':id' => $id]);
    if (!$check->fetch()) jsonError('Service not found.', 404);

    $stmt = $db->prepare('
        UPDATE services
        SET name             = :name,
            category         = :category,
            service_type     = :service_type,
            description      = :description,
            approval_message = :approval_message,
            eligibility      = :eligibility,
            processing_time  = :processing_time,
            requirements     = :requirements,
            status           = :status,
            max_capacity     = :max_capacity
        WHERE id = :id
    ');

    $stmt->execute([
        ':name'             => $name,
        ':category'         => $category,
        ':service_type'     => $service_type,
        ':description'      => $description,
        ':approval_message' => $approval_msg,
        ':eligibility'      => $eligibility,
        ':processing_time'  => $processing_time,
        ':requirements'     => $requirements,
        ':status'           => $status,
        ':max_capacity'     => $max_capacity,
        ':id'               => $id,
    ]);

    jsonSuccess([], 'Service updated successfully.');
}

/* ── POST /admin_services.php?action=delete ─────────────── */

if ($method === 'POST' && $action === 'delete') {

    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int) ($body['id'] ?? 0);

    if ($id === 0) jsonError('Invalid service ID.');

    $check = $db->prepare('SELECT id FROM services WHERE id = :id');
    $check->execute([':id' => $id]);
    if (!$check->fetch()) jsonError('Service not found.', 404);

    // Prevent deletion if there are pending/active applications
    $apps = $db->prepare('
        SELECT COUNT(*) FROM service_applications
        WHERE service_id = :id AND status IN (\'pending\', \'action_required\')
    ');
    $apps->execute([':id' => $id]);
    if ((int) $apps->fetchColumn() > 0) {
        jsonError('Cannot delete a service with pending applications. Deactivate it instead.');
    }

    $stmt = $db->prepare('DELETE FROM services WHERE id = :id');
    $stmt->execute([':id' => $id]);

    jsonSuccess([], 'Service deleted successfully.');
}

jsonError('Invalid action or method.', 405);