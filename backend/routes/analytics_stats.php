<?php






header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AnalyticsController.php';

RoleMiddleware::requireAdmin();

$db   = new Database();
$conn = $db->getConnection();

$year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
$from = isset($_GET['from']) ? trim((string) $_GET['from']) : null;
$to   = isset($_GET['to'])   ? trim((string) $_GET['to'])   : null;

$isValidDate = static function (?string $value): bool {
    if ($value === null || $value === '') {
        return true;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
};

if (!$isValidDate($from) || !$isValidDate($to)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid date filter. Use YYYY-MM-DD.']);
    exit;
}

try {
    $controller = new AnalyticsController($conn, $from, $to);
    $data       = $controller->getAll($year);

    echo json_encode([
        'status' => 'success',
        'data'   => $data,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}