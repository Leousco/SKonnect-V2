<?php
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
RoleMiddleware::requireAuth();

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/SanctionModel.php';

$db   = new Database();
$conn = $db->getConnection();

$user_id      = (int)($_SESSION['user_id'] ?? 0);
$sanctionModel = new SanctionModel($conn);

$level = $sanctionModel->getActiveLevel($user_id);

$expires_at   = null;
$reason       = null;
$issued_at    = null;

if ($level >= 2) {
    $stmt = $conn->prepare(
        "SELECT reason, expires_at, created_at
         FROM user_sanctions
         WHERE user_id = :uid AND is_active = 1 AND level = :lvl
         ORDER BY created_at DESC
         LIMIT 1"
    );
    $stmt->execute([':uid' => $user_id, ':lvl' => $level]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $expires_at = $row['expires_at'];
        $reason     = $row['reason'];
        $issued_at  = $row['created_at'];
    }
}

echo json_encode([
    'status'     => 'success',
    'level'      => $level,          
    'expires_at' => $expires_at,    
    'reason'     => $reason,
    'issued_at'  => $issued_at,
]);