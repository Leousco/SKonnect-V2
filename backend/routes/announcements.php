<?php
/**
 * backend/routes/announcements.php
 *
 * Single entry point for all announcement AJAX calls.
 * Called via ?action=<name>
 *
 * Officer actions (POST):
 *   create        — new announcement (with file uploads)
 *   update        — edit existing announcement
 *   archive       — soft-archive an announcement
 *   restore       — restore archived → active
 *   delete        — permanently delete
 *
 * Read actions (GET):
 *   listAll       — officer management list (all statuses + stats)
 *   listPublic    — portal / public list (active only)
 *   single        — single active announcement (portal)
 *   getForEdit    — single announcement data for officer edit form
 */

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../controllers/AnnouncementController.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action     = $_GET['action'] ?? $_POST['action'] ?? '';
$controller = new AnnouncementController();

$allowedActions = [
    'create',
    'update',
    'archive',
    'restore',
    'delete',
    'removeFile',
    'listAll',
    'listPublic',
    'single',
    'getForEdit',
];

if (!in_array($action, $allowedActions, true)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
    exit;
}

$controller->$action();
?>