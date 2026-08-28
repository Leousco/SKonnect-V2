<?php
/**
 * analytics_stats.php
 * Delegates ALL data aggregation to AnalyticsController.
 * Place at: /backend/routes/analytics_stats.php
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AnalyticsController.php';

RoleMiddleware::requireAdmin();

$db   = new Database();
$conn = $db->getConnection();

$year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');

try {
    $controller = new AnalyticsController($conn);
    $data       = $controller->getAll($year);

    echo json_encode([
        'status' => 'success',
        'data'   => $data,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}