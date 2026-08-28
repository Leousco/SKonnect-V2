<?php
require_once __DIR__ . '/../config/Database.php';

class DashboardModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function getWidgetStats(int $userId): array
    {
        $activeRequests = $this->db->prepare("
            SELECT COUNT(*) FROM service_applications
            WHERE resident_id = :uid AND status IN ('pending', 'action_required')
        ");
        $activeRequests->execute([':uid' => $userId]);

        $communityPosts = $this->db->prepare("
            SELECT COUNT(*) FROM threads
            WHERE author_id = :uid AND is_removed = 0
        ");
        $communityPosts->execute([':uid' => $userId]);

        $upcomingEvents = $this->db->prepare("
            SELECT COUNT(*) FROM events
            WHERE event_date >= CURDATE()
        ");
        $upcomingEvents->execute();

        $unreadNotifs = $this->db->prepare("
            SELECT COUNT(*) FROM notifications
            WHERE user_id = :uid AND is_read = 0
        ");
        $unreadNotifs->execute([':uid' => $userId]);

        return [
            'active_requests'   => (int) $activeRequests->fetchColumn(),
            'community_posts'   => (int) $communityPosts->fetchColumn(),
            'upcoming_events'   => (int) $upcomingEvents->fetchColumn(),
            'unread_notifs'     => (int) $unreadNotifs->fetchColumn(),
        ];
    }

    public function getRecentActivity(int $userId, int $limit = 7): array
    {
        $activities = [];

        // Threads created by user
        $stmt = $this->db->prepare("
            SELECT
                'thread' AS type,
                CONCAT('You posted a thread: \"', subject, '\"') AS description,
                created_at AS activity_at,
                id AS ref_id
            FROM threads
            WHERE author_id = :uid AND is_removed = 0
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $stmt->execute([':uid' => $userId]);
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));

        // Service applications submitted by user
        $stmt = $this->db->prepare("
            SELECT
                'request_submitted' AS type,
                CONCAT('You submitted a request for \"', sv.name, '\"') AS description,
                sa.submitted_at AS activity_at,
                sa.id AS ref_id
            FROM service_applications sa
            INNER JOIN services sv ON sv.id = sa.service_id
            WHERE sa.resident_id = :uid
            ORDER BY sa.submitted_at DESC
            LIMIT 5
        ");
        $stmt->execute([':uid' => $userId]);
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));

        // Comments posted by user
        $stmt = $this->db->prepare("
            SELECT
                'comment' AS type,
                CONCAT('You commented on a thread: \"', t.subject, '\"') AS description,
                tc.created_at AS activity_at,
                tc.thread_id AS ref_id
            FROM thread_comments tc
            INNER JOIN threads t ON t.id = tc.thread_id
            WHERE tc.author_id = :uid AND tc.is_removed = 0
            ORDER BY tc.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([':uid' => $userId]);
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));

        // Replies posted by user
        $stmt = $this->db->prepare("
            SELECT
                'reply' AS type,
                'You replied to a comment' AS description,
                cr.created_at AS activity_at,
                cr.id AS ref_id
            FROM comment_replies cr
            WHERE cr.author_id = :uid AND cr.is_removed = 0
            ORDER BY cr.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([':uid' => $userId]);
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));

        usort($activities, fn($a, $b) => strtotime($b['activity_at']) - strtotime($a['activity_at']));

        return array_slice($activities, 0, $limit);
    }

    public function getLatestAnnouncements(int $limit = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT id, title, category, published_at
            FROM announcements
            WHERE status = 'active'
              AND (expired_at IS NULL OR expired_at >= CURDATE())
            ORDER BY published_at DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUpcomingEvents(): array
    {
        $stmt = $this->db->prepare("
            SELECT id, title, event_date, event_time, event_time_end, location, description
            FROM events
            ORDER BY event_date ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}