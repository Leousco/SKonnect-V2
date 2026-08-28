<?php
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../config/database.php';

RoleMiddleware::requireRole('sk_officer');
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

try {
    $conn   = (new Database())->getConnection();
    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        $stmt = $conn->query("
            SELECT
                u.id, u.first_name, u.last_name, u.middle_name,
                u.gender, u.birth_date, u.age, u.email,
                u.is_verified, u.created_at,
                up.mobile_number, up.purok, up.street_address,
                us.is_active, us.is_banned,
                (SELECT COUNT(*) FROM service_applications WHERE resident_id = u.id)                         AS request_total,
                (SELECT COUNT(*) FROM service_applications WHERE resident_id = u.id AND status = 'pending')  AS request_pending,
                (SELECT COUNT(*) FROM service_applications WHERE resident_id = u.id AND status = 'approved') AS request_approved
            FROM users u
            LEFT JOIN user_profiles up ON up.user_id = u.id
            JOIN  user_status us      ON us.user_id  = u.id
            WHERE u.role = 'resident' AND us.is_deleted = 0
            ORDER BY u.created_at DESC
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $now   = (new DateTime())->format('Y-m');
        $stats = ['total' => 0, 'verified' => 0, 'new_this_month' => 0, 'active_requestors' => 0];
        foreach ($users as $u) {
            $stats['total']++;
            if ($u['is_verified'])                              $stats['verified']++;
            if (substr($u['created_at'], 0, 7) === $now)       $stats['new_this_month']++;
            if ((int)$u['request_pending'] > 0)                $stats['active_requestors']++;
        }

        echo json_encode(['success' => true, 'users' => $users, 'stats' => $stats]);

    } elseif ($action === 'detail') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'Invalid ID']); exit; }

        $stmt = $conn->prepare("
            SELECT
                u.id, u.first_name, u.last_name, u.middle_name,
                u.gender, u.birth_date, u.age, u.email,
                u.is_verified, u.created_at,
                up.mobile_number, up.purok, up.street_address,
                up.civil_status, up.nationality, up.religion,
                up.educational_attainment, up.school_institution, up.course_strand,
                up.employment_status, up.is_registered_voter
            FROM users u
            LEFT JOIN user_profiles up ON up.user_id = u.id
            JOIN  user_status us      ON us.user_id  = u.id
            WHERE u.id = :id AND u.role = 'resident' AND us.is_deleted = 0
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) { echo json_encode(['success' => false, 'message' => 'User not found']); exit; }

        $stmt2 = $conn->prepare("
            SELECT
                COUNT(*)                          AS total,
                SUM(status = 'approved')          AS approved,
                SUM(status = 'pending')           AS pending,
                SUM(status = 'rejected')          AS rejected,
                SUM(status = 'cancelled')         AS cancelled,
                SUM(status = 'action_required')   AS action_required
            FROM service_applications
            WHERE resident_id = :id
        ");
        $stmt2->execute([':id' => $id]);
        $summary = $stmt2->fetch(PDO::FETCH_ASSOC);

        $stmt3 = $conn->prepare("
            SELECT sa.id, sa.status, sa.submitted_at, sv.name AS service_name
            FROM service_applications sa
            INNER JOIN services sv ON sv.id = sa.service_id
            WHERE sa.resident_id = :id
            ORDER BY sa.submitted_at DESC
            LIMIT 5
        ");
        $stmt3->execute([':id' => $id]);
        $recent = $stmt3->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'user'    => $user,
            'summary' => $summary,
            'recent'  => $recent,
        ]);

    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}