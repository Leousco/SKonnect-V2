<?php

require_once __DIR__ . '/../models/NotificationModel.php';

class AdminNotificationService
{
    private PDO $db;
    private NotificationModel $notifications;

    public function __construct(?PDO $connection = null)
    {
        $this->db = $connection ?? (new Database())->getConnection();
        $this->notifications = new NotificationModel($this->db);
    }

    public function generateForAdmins(): void
    {
        $adminIds = array_map('intval', $this->db->query(
            "SELECT id FROM users WHERE role = 'admin' ORDER BY id"
        )->fetchAll(PDO::FETCH_COLUMN));
        if (!$adminIds) return;

        $this->generatePendingRequests($adminIds);
        $this->generatePendingReports($adminIds);
        $this->generateContentChanges($adminIds);
        $this->generateUserChanges($adminIds);
    }

    public function notifyAnnouncementChange(string $action, int $announcementId, string $title): void
    {
        $labels = [
            'created' => ['Announcement created', 'was created'],
            'updated' => ['Announcement updated', 'was updated'],
            'status' => ['Announcement status changed', 'had its status changed'],
            'featured' => ['Announcement featured status changed', 'had its featured status changed'],
            'deleted' => ['Announcement removed', 'was removed'],
        ];
        if (!isset($labels[$action])) return;
        [$notificationTitle, $description] = $labels[$action];
        $message = "\"{$title}\" {$description}.";
        $adminIds = array_map('intval', $this->db->query(
            "SELECT id FROM users WHERE role = 'admin' ORDER BY id"
        )->fetchAll(PDO::FETCH_COLUMN));
        foreach ($adminIds as $adminId) {
            $this->notifications->create(
                $adminId,
                'admin_content_announcement',
                $notificationTitle,
                $message,
                'announcement',
                $announcementId,
                'admin_announcements.php'
            );
        }
    }

    public function notifyNewServiceRequest(int $applicationId): void
    {
        $stmt = $this->db->prepare("
            SELECT sa.id, sa.status, sa.submitted_at, COALESCE(s.name, 'service') AS service_name
            FROM service_applications sa
            LEFT JOIN services s ON s.id = sa.service_id
            WHERE sa.id = :id
        ");
        $stmt->execute([':id' => $applicationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || !in_array($row['status'], ['pending', 'action_required'], true)) return;

        $adminIds = array_map('intval', $this->db->query(
            "SELECT id FROM users WHERE role = 'admin' ORDER BY id"
        )->fetchAll(PDO::FETCH_COLUMN));
        $id = (int)$row['id'];
        $name = trim((string)$row['service_name']);
        $this->createForAdmins($adminIds, [
            'type' => 'admin_service_request',
            'title' => 'New service request',
            'message' => "Request #{$id} for \"{$name}\" is awaiting review.",
            'ref_type' => 'service_application',
            'ref_id' => $id,
            'link' => 'admin_service_requests.php',
            'created_at' => $row['submitted_at'],
        ]);
    }

    private function generatePendingRequests(array $adminIds): void
    {
        $rows = $this->db->query("
            SELECT sa.id, sa.submitted_at, COALESCE(s.name, 'service') AS service_name
            FROM service_applications sa
            LEFT JOIN services s ON s.id = sa.service_id
            WHERE sa.status IN ('pending', 'action_required')
            ORDER BY sa.submitted_at DESC, sa.id DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $id = (int)$row['id'];
            $name = trim((string)$row['service_name']);
            $this->createForAdmins($adminIds, [
                'type' => 'admin_service_request',
                'title' => 'New service request',
                'message' => "Request #{$id} for \"{$name}\" is awaiting review.",
                'ref_type' => 'service_application',
                'ref_id' => $id,
                'link' => 'admin_service_requests.php',
                'created_at' => $row['submitted_at'],
            ]);
        }
    }

    private function generatePendingReports(array $adminIds): void
    {
        $threadReports = $this->db->query("
            SELECT tr.id, tr.created_at, t.subject AS subject
            FROM thread_reports tr
            JOIN threads t ON t.id = tr.thread_id
            WHERE tr.status = 'pending'
            ORDER BY tr.created_at DESC, tr.id DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($threadReports as $row) {
            $id = (int)$row['id'];
            $subject = trim((string)$row['subject']);
            $this->createForAdmins($adminIds, [
                'type' => 'admin_community_report',
                'title' => 'New community report',
                'message' => $subject !== ''
                    ? "A community post was reported: \"{$subject}\". Please review it."
                    : 'A community post was reported and needs review.',
                'ref_type' => 'thread_report',
                'ref_id' => $id,
                'link' => 'admin_reports.php',
                'created_at' => $row['created_at'],
            ]);
        }

        $commentReports = $this->db->query("
            SELECT cr.id, cr.created_at,
                   COALESCE(th1.subject, th2.subject, 'community comment') AS subject
            FROM comment_reports cr
            LEFT JOIN thread_comments tc
                ON cr.target_type = 'comment' AND tc.id = cr.target_id
            LEFT JOIN threads th1 ON th1.id = tc.thread_id
            LEFT JOIN comment_replies reply
                ON cr.target_type = 'reply' AND reply.id = cr.target_id
            LEFT JOIN thread_comments reply_comment ON reply_comment.id = reply.comment_id
            LEFT JOIN threads th2 ON th2.id = reply_comment.thread_id
            WHERE cr.status = 'pending'
            ORDER BY cr.created_at DESC, cr.id DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($commentReports as $row) {
            $id = (int)$row['id'];
            $subject = trim((string)$row['subject']);
            $this->createForAdmins($adminIds, [
                'type' => 'admin_community_report',
                'title' => 'New community report',
                'message' => "A comment on \"{$subject}\" was reported. Please review it.",
                'ref_type' => 'comment_report',
                'ref_id' => $id,
                'link' => 'admin_reports.php',
                'created_at' => $row['created_at'],
            ]);
        }
    }

    private function generateContentChanges(array $adminIds): void
    {
        $actions = [
            'announcement_created', 'announcement_updated', 'announcement_archived',
            'announcement_restored', 'announcement_deleted', 'service_created',
            'service_updated', 'service_status_updated', 'service_deleted',
        ];
        $placeholders = implode(', ', array_fill(0, count($actions), '?'));
        $stmt = $this->db->prepare("
            SELECT id, action, description, created_at
            FROM activity_logs
            WHERE action IN ({$placeholders})
              AND created_at >= NOW() - INTERVAL '30 days'
            ORDER BY id DESC
            LIMIT 500
        ");
        $stmt->execute($actions);

        $labels = [
            'announcement_created' => ['admin_content_announcement', 'Announcement created', 'created', 'admin_announcements.php'],
            'announcement_updated' => ['admin_content_announcement', 'Announcement updated', 'updated', 'admin_announcements.php'],
            'announcement_archived' => ['admin_content_announcement', 'Announcement archived', 'archived', 'admin_announcements.php'],
            'announcement_restored' => ['admin_content_announcement', 'Announcement restored', 'restored', 'admin_announcements.php'],
            'announcement_deleted' => ['admin_content_announcement', 'Announcement removed', 'removed', 'admin_announcements.php'],
            'service_created' => ['admin_content_service', 'Service created', 'created', 'admin_manage_services.php'],
            'service_updated' => ['admin_content_service', 'Service updated', 'updated', 'admin_manage_services.php'],
            'service_status_updated' => ['admin_content_service', 'Service status changed', 'updated', 'admin_manage_services.php'],
            'service_deleted' => ['admin_content_service', 'Service removed', 'removed', 'admin_manage_services.php'],
        ];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $meta = json_decode((string)$row['description'], true);
            if (!is_array($meta)) continue;
            $action = (string)$row['action'];
            $label = $labels[$action] ?? null;
            if (!$label) continue;
            [$type, $title, $verb, $link] = $label;
            $name = trim((string)($meta['target_name'] ?? ''));
            if ($name === '') $name = ucfirst(str_starts_with($action, 'announcement_') ? 'announcement' : 'service');
            $this->createForAdmins($adminIds, [
                'type' => $type,
                'title' => $title,
                'message' => "\"{$name}\" was {$verb}.",
                'ref_type' => 'activity_log',
                'ref_id' => (int)$row['id'],
                'link' => $link,
                'created_at' => $row['created_at'],
            ]);
        }
    }

    private function generateUserChanges(array $adminIds): void
    {
        $stmt = $this->db->query("
            SELECT id, action, description, created_at
            FROM activity_logs
            WHERE created_at >= NOW() - INTERVAL '30 days'
              AND action IN ('created', 'updated', 'declined', 'deleted')
              AND (
                    description ILIKE '%Created new user account%'
                 OR description ILIKE '%Updated profile of%'
                 OR description ILIKE '%Changed role of%'
                 OR description ILIKE '%Account <strong>%'
                 OR description ILIKE '%Banned user%'
                 OR description ILIKE '%Lifted ban for user%'
                 OR description ILIKE '%Permanently deleted account%'
              )
            ORDER BY id DESC
            LIMIT 500
        ");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $message = html_entity_decode(strip_tags((string)$row['description']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $message = trim(preg_replace('/\s+/', ' ', $message) ?? '');
            if ($message === '') $message = 'A user account was modified.';
            $this->createForAdmins($adminIds, [
                'type' => 'admin_user_management',
                'title' => 'User account changed',
                'message' => $message,
                'ref_type' => 'activity_log',
                'ref_id' => (int)$row['id'],
                'link' => 'admin_manage_users.php',
                'created_at' => $row['created_at'],
            ]);
        }
    }

    private function createForAdmins(array $adminIds, array $notification): void
    {
        foreach ($adminIds as $adminId) {
            $this->notifications->createAdminNotificationOnce(
                $adminId,
                $notification['type'],
                $notification['title'],
                $notification['message'],
                $notification['ref_type'],
                $notification['ref_id'],
                $notification['link'],
                (string)$notification['created_at']
            );
        }
    }
}
