<?php

require_once __DIR__ . '/../models/NotificationModel.php';

class ModeratorNotificationService
{
    private PDO $db;
    private NotificationModel $notifications;

    public function __construct(?PDO $connection = null)
    {
        $this->db = $connection ?? (new Database())->getConnection();
        $this->notifications = new NotificationModel($this->db);
    }

    public function notifyNewReport(): void
    {
        $this->notifyHourly(
            'moderator_new_reports',
            'moderator_report_hour',
            'New reports received',
            '1 new report was submitted this hour.',
            '%d new reports were submitted this hour.',
            'mod_queue.php'
        );
    }

    public function notifyAdminReportAction(): void
    {
        $this->notifyHourly(
            'moderator_admin_report_action',
            'moderator_admin_report_hour',
            'Administrator report actions',
            '1 report was handled by an administrator this hour.',
            '%d reports were handled by an administrator this hour.',
            'mod_queue.php'
        );
    }

    public function notifyAdminThreadAction(
        string $action,
        int $threadId,
        string $threadSubject,
        string $actionDetail = ''
    ): void {
        $actionLabels = [
            'status' => 'changed the status of',
            'flagged' => 'flagged',
            'unflagged' => 'removed the flag from',
            'pinned' => 'pinned',
            'unpinned' => 'unpinned',
            'hidden' => 'hid',
            'restored' => 'restored',
            'comment' => 'replied to',
            'reply' => 'replied to',
        ];
        $label = $actionLabels[$action] ?? 'updated';
        $detail = $action === 'status' && $actionDetail !== ''
            ? " The new status is “{$actionDetail}”."
            : '';

        foreach ($this->notifications->getModeratorIds() as $moderatorId) {
            $this->notifications->create(
                $moderatorId,
                'moderator_admin_thread_action',
                'Administrator updated a thread',
                "An administrator {$label} the thread “{$threadSubject}”.{$detail}",
                'thread',
                $threadId,
                'mod_feed.php',
                1
            );
        }
    }

    public function notifyAdminComment(
        int $threadId,
        string $threadSubject,
        bool $isReply
    ): void {
        $type = $isReply ? 'moderator_admin_reply' : 'moderator_admin_comment';
        $title = $isReply ? 'Administrator replied to a thread' : 'Administrator commented on a thread';
        $message = $isReply
            ? "An administrator replied in the thread “{$threadSubject}”."
            : "An administrator commented on the thread “{$threadSubject}”.";

        foreach ($this->notifications->getModeratorIds() as $moderatorId) {
            $this->notifications->create(
                $moderatorId,
                $type,
                $title,
                $message,
                'thread',
                $threadId,
                'mod_feed.php',
                1
            );
        }
    }

    private function notifyHourly(
        string $type,
        string $refType,
        string $title,
        string $singularMessage,
        string $pluralMessage,
        string $link
    ): void {
        $hourBucket = (int)$this->db->query(
            "SELECT EXTRACT(EPOCH FROM date_trunc('hour', NOW()))::bigint"
        )->fetchColumn();

        foreach ($this->notifications->getModeratorIds() as $moderatorId) {
            $this->notifications->updateHourlyModeratorNotification(
                $moderatorId,
                $type,
                $refType,
                $hourBucket,
                $title,
                $singularMessage,
                $pluralMessage,
                $link
            );
        }
    }
}
