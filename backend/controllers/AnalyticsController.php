<?php
/**
 * AnalyticsController.php
 *
 * Aggregates data from every module for the Admin Analytics dashboard.
 * Instantiate with a PDO connection; call getAll($year) for the full payload.
 *
 * Place at: /backend/controllers/AnalyticsController.php
 */

class AnalyticsController
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /* ── ENTRY POINT ──────────────────────────────────────── */

    public function getAll(int $year): array
    {
        return [
            /* Users */
            'totalUsers'          => $this->totalUsers(),
            'newThisMonth'        => $this->newUsersThisMonth(),
            'activeUsers'         => $this->activeUsers(),
            'inactiveUsers'       => $this->inactiveUsers(),
            'activePct'           => $this->activePct(),
            'usersByRole'         => $this->usersByRole(),
            'growthLabels'        => $this->growthLabels(),
            'growthData'          => $this->growthData(),

            /* Service Requests */
            'totalRequests'       => $this->totalRequests(),
            'requestsThisMonth'   => $this->requestsThisMonth(),
            'topService'          => $this->topService(),
            'requestsByMonth'     => $this->requestsByMonth($year),
            'serviceBreakdown'    => $this->serviceBreakdownByCategory(),
            'requestsByType'      => $this->requestsByServiceType(),
            'requestStatusCounts' => $this->requestStatusCounts(),
            'requestsByService'   => $this->requestsByService(),

            /* Announcements */
            'announcementStats'   => $this->announcementStats(),

            /* Events */
            'eventStats'          => $this->eventStats(),

            /* Threads */
            'threadStats'         => $this->threadStats(),

            /* Reports */
            'reportStats'         => $this->reportStats(),

            /* Meta */
            'availableYears'      => $this->availableYears(),
            'selectedYear'        => $year,
        ];
    }

    /* ════════════════════════════════════════════════════════
       USERS
    ════════════════════════════════════════════════════════ */

    private function totalUsers(): int
    {
        return (int) $this->conn->query(
            "SELECT COUNT(*) FROM users u
             JOIN user_status us ON us.user_id = u.id
             WHERE us.is_deleted = 0"
        )->fetchColumn();
    }

    private function newUsersThisMonth(): int
    {
        return (int) $this->conn->query(
            "SELECT COUNT(*) FROM users u
             JOIN user_status us ON us.user_id = u.id
             WHERE us.is_deleted = 0
               AND MONTH(u.created_at) = MONTH(CURDATE())
               AND YEAR(u.created_at)  = YEAR(CURDATE())"
        )->fetchColumn();
    }

    /**
     * Active = is_active=1, not banned, not deleted.
     */
    private function activeUsers(): int
    {
        return (int) $this->conn->query(
            "SELECT COUNT(*) FROM user_status
             WHERE is_active = 1 AND is_banned = 0 AND is_deleted = 0"
        )->fetchColumn();
    }

    /**
     * Inactive = is_active=0 OR is_banned=1, but not deleted.
     */
    private function inactiveUsers(): int
    {
        return (int) $this->conn->query(
            "SELECT COUNT(*) FROM user_status
             WHERE (is_active = 0 OR is_banned = 1) AND is_deleted = 0"
        )->fetchColumn();
    }

    private function activePct(): int
    {
        $total  = $this->totalUsers();
        $active = $this->activeUsers();
        return $total > 0 ? (int) round($active / $total * 100) : 0;
    }

    /**
     * Returns an associative array: ['admin'=>N, 'resident'=>N, 'moderator'=>N, 'sk_officer'=>N]
     */
    private function usersByRole(): array
    {
        $stmt = $this->conn->query(
            "SELECT u.role, COUNT(*) AS cnt
             FROM users u
             JOIN user_status us ON us.user_id = u.id
             WHERE us.is_deleted = 0
             GROUP BY u.role"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Ensure all expected roles are present even with zero count
        $defaults = ['admin' => 0, 'resident' => 0, 'moderator' => 0, 'sk_officer' => 0];
        return array_merge($defaults, array_map('intval', $rows));
    }

    private function growthLabels(): array
    {
        $stmt = $this->conn->query(
            "SELECT DATE_FORMAT(created_at, '%b %Y') AS label
             FROM users
             WHERE created_at >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 11 MONTH)
             GROUP BY YEAR(created_at), MONTH(created_at)
             ORDER BY YEAR(created_at), MONTH(created_at)"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function growthData(): array
    {
        // Base count: users registered before the 12-month window
        $baseCount = (int) $this->conn->query(
            "SELECT COUNT(*) FROM users
             WHERE created_at < DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 11 MONTH)"
        )->fetchColumn();

        $stmt = $this->conn->query(
            "SELECT
                DATE_FORMAT(created_at, '%Y-%m') AS ym,
                COUNT(*) AS cnt
             FROM users
             WHERE created_at >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 11 MONTH)
             GROUP BY ym
             ORDER BY ym ASC"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data    = [];
        $running = $baseCount;
        foreach ($rows as $r) {
            $running += (int) $r['cnt'];
            $data[]   = $running;
        }
        return $data;
    }

    /* ════════════════════════════════════════════════════════
       SERVICE REQUESTS
    ════════════════════════════════════════════════════════ */

    private function totalRequests(): int
    {
        return (int) $this->conn->query(
            "SELECT COUNT(*) FROM service_applications"
        )->fetchColumn();
    }

    private function requestsThisMonth(): int
    {
        return (int) $this->conn->query(
            "SELECT COUNT(*) FROM service_applications
             WHERE MONTH(submitted_at) = MONTH(CURDATE())
               AND YEAR(submitted_at)  = YEAR(CURDATE())"
        )->fetchColumn();
    }

    private function topService(): array
    {
        $stmt = $this->conn->query(
            "SELECT s.name, s.category, COUNT(sa.id) AS cnt
             FROM service_applications sa
             JOIN services s ON s.id = sa.service_id
             WHERE MONTH(sa.submitted_at) = MONTH(CURDATE())
               AND YEAR(sa.submitted_at)  = YEAR(CURDATE())
             GROUP BY sa.service_id
             ORDER BY cnt DESC
             LIMIT 1"
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: ['name' => 'N/A', 'category' => 'other', 'cnt' => 0];
    }

    private function requestsByMonth(int $year): array
    {
        $stmt = $this->conn->prepare(
            "SELECT MONTH(submitted_at) AS mo, COUNT(*) AS cnt
             FROM service_applications
             WHERE YEAR(submitted_at) = :yr
             GROUP BY MONTH(submitted_at)"
        );
        $stmt->execute([':yr' => $year]);
        $data = array_fill(0, 12, 0);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $data[(int) $r['mo'] - 1] = (int) $r['cnt'];
        }
        return $data;
    }

    /**
     * Breakdown by service category for the current month (donut chart).
     */
    private function serviceBreakdownByCategory(): array
    {
        $stmt = $this->conn->query(
            "SELECT s.category, COUNT(sa.id) AS cnt
             FROM service_applications sa
             JOIN services s ON s.id = sa.service_id
             WHERE MONTH(sa.submitted_at) = MONTH(CURDATE())
               AND YEAR(sa.submitted_at)  = YEAR(CURDATE())
             GROUP BY s.category
             ORDER BY cnt DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Counts per service_type (document / appointment / info) — all time.
     * Returns associative array: ['document'=>N, 'appointment'=>N, 'info'=>N]
     */
    private function requestsByServiceType(): array
    {
        $stmt = $this->conn->query(
            "SELECT s.service_type, COUNT(sa.id) AS cnt
             FROM service_applications sa
             JOIN services s ON s.id = sa.service_id
             GROUP BY s.service_type"
        );
        $rows     = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $defaults = ['document' => 0, 'appointment' => 0, 'info' => 0];
        return array_merge($defaults, array_map('intval', $rows));
    }

    /**
     * Counts per application status — all time.
     * Returns associative array keyed by status.
     */
    private function requestStatusCounts(): array
    {
        $stmt = $this->conn->query(
            "SELECT status, COUNT(*) AS cnt
             FROM service_applications
             GROUP BY status"
        );
        $rows     = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $defaults = ['pending' => 0, 'action_required' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0];
        return array_merge($defaults, array_map('intval', $rows));
    }

    /**
     * Top 10 services by total requests — all time.
     */
    private function requestsByService(): array
    {
        $stmt = $this->conn->query(
            "SELECT s.name, s.category, s.service_type, COUNT(sa.id) AS cnt
             FROM service_applications sa
             JOIN services s ON s.id = sa.service_id
             GROUP BY sa.service_id
             ORDER BY cnt DESC
             LIMIT 10"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ════════════════════════════════════════════════════════
       ANNOUNCEMENTS
    ════════════════════════════════════════════════════════ */

    private function announcementStats(): array
    {
        $row = $this->conn->query(
            "SELECT
                COUNT(*)                                        AS total,
                SUM(status = 'active')                         AS published,
                SUM(status = 'draft')                          AS drafts,
                SUM(status = 'archived')                       AS archived,
                SUM(featured = 1 AND status = 'active')        AS featured,
                SUM(category = 'urgent' AND status = 'active') AS urgent
             FROM announcements"
        )->fetch(PDO::FETCH_ASSOC);
        return array_map('intval', $row ?: [
            'total' => 0, 'published' => 0, 'drafts' => 0,
            'archived' => 0, 'featured' => 0, 'urgent' => 0,
        ]);
    }

    /* ════════════════════════════════════════════════════════
       EVENTS
    ════════════════════════════════════════════════════════ */

    private function eventStats(): array
    {
        $row = $this->conn->query(
            "SELECT
                COUNT(*) AS total,
                SUM(event_date >= CURDATE()) AS upcoming,
                SUM(event_date <  CURDATE()) AS past,
                SUM(MONTH(event_date) = MONTH(CURDATE())
                    AND YEAR(event_date) = YEAR(CURDATE())) AS this_month
             FROM events"
        )->fetch(PDO::FETCH_ASSOC);
        return array_map('intval', $row ?: [
            'total' => 0, 'upcoming' => 0, 'past' => 0, 'this_month' => 0,
        ]);
    }

    /* ════════════════════════════════════════════════════════
       THREADS
    ════════════════════════════════════════════════════════ */

    private function threadStats(): array
    {
        $row = $this->conn->query(
            "SELECT
                COUNT(*)                                        AS total,
                SUM(is_removed = 0)                            AS published,
                SUM(is_removed = 1)                            AS removed,
                SUM(is_flagged = 1 AND is_removed = 0)         AS flagged,
                SUM(is_pinned  = 1 AND is_removed = 0)         AS pinned,
                SUM(status = 'pending'   AND is_removed = 0)   AS pending,
                SUM(status = 'responded' AND is_removed = 0)   AS responded,
                SUM(status = 'resolved'  AND is_removed = 0)   AS resolved
             FROM threads"
        )->fetch(PDO::FETCH_ASSOC);
        return array_map('intval', $row ?: [
            'total' => 0, 'published' => 0, 'removed' => 0,
            'flagged' => 0, 'pinned' => 0, 'pending' => 0,
            'responded' => 0, 'resolved' => 0,
        ]);
    }

    /* ════════════════════════════════════════════════════════
       REPORTS (thread + comment)
    ════════════════════════════════════════════════════════ */

    private function reportStats(): array
    {
        $threads = $this->conn->query(
            "SELECT
                COUNT(*)                    AS total,
                SUM(status = 'pending')     AS pending,
                SUM(status = 'reviewed')    AS reviewed,
                SUM(status = 'dismissed')   AS dismissed
             FROM thread_reports"
        )->fetch(PDO::FETCH_ASSOC);

        $comments = $this->conn->query(
            "SELECT
                COUNT(*)                    AS total,
                SUM(status = 'pending')     AS pending,
                SUM(status = 'reviewed')    AS reviewed,
                SUM(status = 'dismissed')   AS dismissed
             FROM comment_reports"
        )->fetch(PDO::FETCH_ASSOC);

        return [
            'threads'  => array_map('intval', $threads  ?: ['total' => 0, 'pending' => 0, 'reviewed' => 0, 'dismissed' => 0]),
            'comments' => array_map('intval', $comments ?: ['total' => 0, 'pending' => 0, 'reviewed' => 0, 'dismissed' => 0]),
        ];
    }

    /* ════════════════════════════════════════════════════════
       YEAR FILTER
    ════════════════════════════════════════════════════════ */

    private function availableYears(): array
    {
        $stmt  = $this->conn->query(
            "SELECT DISTINCT YEAR(submitted_at) AS yr
             FROM service_applications
             ORDER BY yr DESC"
        );
        $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $cur   = (int) date('Y');
        if (! in_array($cur, $years)) {
            $years[] = $cur;
        }
        rsort($years);
        return $years;
    }
}