<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/NotificationModel.php';
require_once __DIR__ . '/../models/AnnouncementModel.php';

class OfficerNotificationService
{
    private PDO $db;
    private NotificationModel $notifications;

    public function __construct(?PDO $connection = null)
    {
        $this->db = $connection ?? (new Database())->getConnection();
        $this->notifications = new NotificationModel($this->db);
    }

    public function generateOnListRequest(int $officerId): void
    {
        $this->notifyExpiredAnnouncements();

        $hourly = $this->db->query("
            SELECT EXTRACT(EPOCH FROM date_trunc('hour', submitted_at))::bigint AS bucket,
                   COUNT(*) AS request_count
            FROM service_applications
            WHERE submitted_at >= date_trunc('hour', NOW())
            GROUP BY date_trunc('hour', submitted_at)
        ")->fetch(PDO::FETCH_ASSOC);
        if ($hourly) {
            $this->notifications->updateHourlyRequestNotification(
                $officerId,
                (int)$hourly['bucket'],
                (int)$hourly['request_count']
            );
        }

        $eventsStmt = $this->db->prepare("
            SELECT e.id, e.title, e.event_date, e.event_time, e.created_by
            FROM events e
            WHERE e.event_date BETWEEN CURRENT_DATE + 1 AND CURRENT_DATE + 2
              AND e.created_by = :officer_id
            ORDER BY e.id
        ");
        $eventsStmt->execute([':officer_id' => $officerId]);
        $events = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($events as $event) {
            $when = $event['event_time']
                ? date('g:i A', strtotime($event['event_time']))
                : '';
            $message = "“{$event['title']}” is scheduled for "
                . date('F j, Y', strtotime($event['event_date']))
                . ($when !== '' ? " at {$when}." : '.');
            $this->notifications->createOfficerNotificationOnce(
                (int)$event['created_by'],
                'event_reminder',
                'Upcoming event in two days',
                $message,
                'event',
                (int)$event['id'],
                'officer_events.php'
            );
        }

        $overdue = $this->db->query("
            SELECT sa.id, sv.name AS service_name
            FROM service_applications sa
            JOIN services sv ON sv.id = sa.service_id
            WHERE sa.status IN ('pending', 'action_required')
              AND GREATEST(sa.submitted_at, COALESCE(sa.updated_at, sa.submitted_at))
                    <= NOW() - INTERVAL '3 days'
            ORDER BY sa.id
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($overdue as $application) {
            $this->notifications->createOverdueReminderIfDue(
                $officerId,
                (int)$application['id'],
                'Service request needs attention',
                "Request #{$application['id']} for “{$application['service_name']}” is still unresolved. "
                . 'Please review its current status.',
                'officer_requests.php'
            );
        }
    }

    public function notifyIncomingRequest(int $applicationId): void
    {
        $stmt = $this->db->prepare("
            SELECT EXTRACT(EPOCH FROM date_trunc('hour', submitted_at))::bigint AS bucket,
                   COUNT(*) AS request_count
            FROM service_applications
            WHERE date_trunc('hour', submitted_at) = (
                SELECT date_trunc('hour', submitted_at)
                FROM service_applications WHERE id = :id
            )
            GROUP BY date_trunc('hour', submitted_at)
        ");
        $stmt->execute([':id' => $applicationId]);
        $hourly = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$hourly) {
            return;
        }

        foreach ($this->notifications->getOfficerIds() as $officerId) {
            $this->notifications->updateHourlyRequestNotification(
                $officerId,
                (int)$hourly['bucket'],
                (int)$hourly['request_count']
            );
        }
    }

    public function notifyRequestUpdate(string $kind, int $applicationId, string $serviceName): void
    {
        $officerIds = $this->notifications->getOfficerIds();
        $cancelled = $kind === 'cancelled';
        $type = $cancelled ? 'request_cancelled' : 'request_updated';
        $title = $cancelled ? 'Resident cancelled a request' : 'Resident updated a request';
        $message = $cancelled
            ? "A resident cancelled request #{$applicationId} for “{$serviceName}”."
            : "A resident responded to the action-required request #{$applicationId} for “{$serviceName}”.";

        foreach ($officerIds as $officerId) {
            $this->notifications->create(
                $officerId,
                $type,
                $title,
                $message,
                'service_application',
                $applicationId,
                'officer_requests.php'
            );
        }
    }

    public function notifyAdminUpdate(
        int $authorId,
        string $refType,
        int $refId,
        string $itemTitle
    ): void {
        $check = $this->db->prepare("
            SELECT 1 FROM users WHERE id = :id AND role = 'sk_officer'
        ");
        $check->execute([':id' => $authorId]);
        if (!$check->fetchColumn()) {
            return;
        }

        $label = $refType === 'service' ? 'service' : 'announcement';
        $link = $refType === 'service'
            ? 'officer_services.php'
            : 'officer_announcements.php';
        $this->notifications->create(
            $authorId,
            'admin_update',
            'Admin updated your ' . $label,
            "An administrator changed your {$label} “{$itemTitle}”.",
            $refType,
            $refId,
            $link,
            1
        );
    }

    public function notifyArchivedAnnouncements(array $archived): void
    {
        $check = $this->db->prepare("
            SELECT 1 FROM users WHERE id = :id AND role = 'sk_officer'
        ");
        foreach ($archived as $announcement) {
            $authorId = (int)$announcement['author_id'];
            $check->execute([':id' => $authorId]);
            if (!$check->fetchColumn()) {
                continue;
            }
            $this->notifications->createOfficerNotificationOnce(
                $authorId,
                'announcement_archived',
                'Your announcement was archived',
                "Your announcement “{$announcement['title']}” was archived after its expiry date.",
                'announcement',
                (int)$announcement['id'],
                'officer_announcements.php'
            );
        }
    }

    private function notifyExpiredAnnouncements(): void
    {
        $announcementModel = new AnnouncementModel($this->db);
        $announcementModel->archiveExpired();
        $pendingNotices = $this->db->query("
            SELECT a.id, a.title, a.author_id
            FROM announcements a
            JOIN users u ON u.id = a.author_id
            WHERE a.status = 'archived'
              AND a.expired_at IS NOT NULL
              AND a.archived_at::date > a.expired_at
              AND u.role = 'sk_officer'
              AND NOT EXISTS (
                  SELECT 1 FROM notifications n
                  WHERE n.user_id = a.author_id
                    AND n.type = 'announcement_archived'
                    AND n.ref_type = 'announcement'
                    AND n.ref_id = a.id
              )
            ORDER BY a.archived_at, a.id
        ")->fetchAll(PDO::FETCH_ASSOC);
        if ($pendingNotices) {
            $this->notifyArchivedAnnouncements($pendingNotices);
        }
    }
}
