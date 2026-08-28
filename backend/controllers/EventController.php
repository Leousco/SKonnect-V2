<?php
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../models/EventModel.php';

RoleMiddleware::requireRole('sk_officer');

header('Content-Type: application/json');

$model  = new EventModel();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

function jsonOut(bool $success, mixed $data = null, string $message = ''): void {
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

function sanitizeStr(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

try {
    switch ($action) {

        case 'list':
            jsonOut(true, $model->getAll());

        case 'create':
            $title = sanitizeStr($_POST['title'] ?? '');
            $date  = $_POST['event_date'] ?? '';

            if (!$title || !$date) {
                jsonOut(false, null, 'Title and date are required.');
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                jsonOut(false, null, 'Invalid date format.');
            }

            $id = $model->create([
                'title'          => $title,
                'event_date'     => $date,
                'event_time'     => sanitizeStr($_POST['event_time'] ?? ''),
                'event_time_end' => sanitizeStr($_POST['event_time_end'] ?? ''),
                'location'       => sanitizeStr($_POST['location'] ?? ''),
                'description'    => sanitizeStr($_POST['description'] ?? ''),
                'created_by'     => $_SESSION['user_id'],
            ]);
            jsonOut(true, ['id' => $id], 'Event created.');

        case 'update':
            $id    = (int) ($_POST['id'] ?? 0);
            $title = sanitizeStr($_POST['title'] ?? '');
            $date  = $_POST['event_date'] ?? '';

            if (!$id || !$title || !$date) {
                jsonOut(false, null, 'ID, title, and date are required.');
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                jsonOut(false, null, 'Invalid date format.');
            }

            $ok = $model->update($id, [
                'title'          => $title,
                'event_date'     => $date,
                'event_time'     => sanitizeStr($_POST['event_time'] ?? ''),
                'event_time_end' => sanitizeStr($_POST['event_time_end'] ?? ''),
                'location'       => sanitizeStr($_POST['location'] ?? ''),
                'description'    => sanitizeStr($_POST['description'] ?? ''),
            ]);
            $ok ? jsonOut(true, null, 'Event updated.') : jsonOut(false, null, 'Update failed.');

        case 'delete':
            $id = (int) ($_POST['id'] ?? 0);
            if (!$id) jsonOut(false, null, 'Invalid ID.');
            $ok = $model->delete($id);
            $ok ? jsonOut(true, null, 'Event deleted.') : jsonOut(false, null, 'Delete failed.');

        default:
            jsonOut(false, null, 'Unknown action.');
    }
} catch (Exception $e) {
    jsonOut(false, null, 'Server error: ' . $e->getMessage());
}