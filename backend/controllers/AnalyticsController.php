<?php

class AnalyticsController
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getAll(int $year): array
    {
        $growth = $this->userGrowth();
        $availableYears = $this->availableYears();
        if (!in_array($year, $availableYears, true)) {
            $year = (int) date('Y');
        }

        return [
            'totalUsers'          => $this->totalUsers(),
            'newThisMonth'        => $this->newUsersThisMonth(),
            'activeUsers'         => $this->activeUsers(),
            'inactiveUsers'       => $this->inactiveUsers(),
            'activePct'           => $this->activePct(),
            'usersByRole'         => $this->usersByRole(),
            'growthLabels'        => $growth['labels'],
            'growthData'          => $growth['data'],

            
            'totalRequests'       => $this->totalRequests(),
            'requestsThisMonth'   => $this->requestsThisMonth(),
            'topService'          => $this->topService(),
            'requestsByMonth'     => $this->requestsByMonth($year),
            'serviceBreakdown'    => $this->serviceBreakdownByCategory(),
            'requestsByType'      => $this->requestsByServiceType(),
            'requestStatusCounts' => $this->requestStatusCounts(),
            'requestsByService'   => $this->requestsByService(),

            
            'announcementStats'   => $this->announcementStats(),

            
            'eventStats'          => $this->eventStats(),

            
            'threadStats'         => $this->threadStats(),

            
            'reportStats'         => $this->reportStats(),

            
            'availableYears'      => $availableYears,
            'selectedYear'        => $year,
        ];
    }

    private function totalUsers(): int
    {
        return (int) $this->conn->query(
            "SELECT COUNT(*) FROM users u
             JOIN user_status us ON us.user_id = u.id
             WHERE us.is_deleted = FALSE"
        )->fetchColumn();
    }

    private function newUsersThisMonth(): int
    {
        return (int) $this->conn->query(
            "SELECT COUNT(*) FROM users u
             JOIN user_status us ON us.user_id = u.id
             WHERE us.is_deleted = FALSE
               AND u.created_at >= date_trunc('month', CURRENT_DATE)
               AND u.created_at < date_trunc('month', CURRENT_DATE) + INTERVAL '1 month'"
        )->fetchColumn();
    }

    private function activeUsers(): int
    {
        return (int) $this->conn->query(
            "SELECT COUNT(*) FROM user_status
             WHERE is_active = TRUE AND is_banned = FALSE AND is_deleted = FALSE"
        )->fetchColumn();
    }

    private function inactiveUsers(): int
    {
        return (int) $this->conn->query(
            "SELECT COUNT(*) FROM user_status
             WHERE (is_active = FALSE OR is_banned = TRUE) AND is_deleted = FALSE"
        )->fetchColumn();
    }

    private function activePct(): int
    {
        $total  = $this->totalUsers();
        $active = $this->activeUsers();
        return $total > 0 ? (int) round($active / $total * 100) : 0;
    }

    private function usersByRole(): array
    {
        $stmt = $this->conn->query(
            "SELECT u.role, COUNT(*) AS cnt
             FROM users u
             JOIN user_status us ON us.user_id = u.id
             WHERE us.is_deleted = FALSE
             GROUP BY u.role"
        );
        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[$row['role']] = (int) $row['cnt'];
        }

        $defaults = ['admin' => 0, 'resident' => 0, 'moderator' => 0, 'sk_officer' => 0];
        return array_merge($defaults, $rows);
    }

    private function userGrowth(): array
    {
        $stmt = $this->conn->query(
            "WITH months AS (
                SELECT generate_series(
                    date_trunc('month', CURRENT_DATE) - INTERVAL '11 months',
                    date_trunc('month', CURRENT_DATE),
                    INTERVAL '1 month'
                )::date AS month_start
             ),
             base AS (
                SELECT COUNT(*) AS user_count
                FROM users u
                JOIN user_status us ON us.user_id = u.id
                WHERE us.is_deleted = FALSE
                  AND u.created_at < date_trunc('month', CURRENT_DATE) - INTERVAL '11 months'
             ),
             monthly AS (
                SELECT date_trunc('month', u.created_at)::date AS month_start, COUNT(*) AS user_count
                FROM users u
                JOIN user_status us ON us.user_id = u.id
                WHERE us.is_deleted = FALSE
                  AND u.created_at >= date_trunc('month', CURRENT_DATE) - INTERVAL '11 months'
                  AND u.created_at < date_trunc('month', CURRENT_DATE) + INTERVAL '1 month'
                GROUP BY date_trunc('month', u.created_at)::date
             )
             SELECT to_char(months.month_start, 'Mon YYYY') AS label,
                    base.user_count + SUM(COALESCE(monthly.user_count, 0)) OVER (
                        ORDER BY months.month_start
                    ) AS total_users
             FROM months
             CROSS JOIN base
             LEFT JOIN monthly ON monthly.month_start = months.month_start
             ORDER BY months.month_start"
        );
        $labels = [];
        $data = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $labels[] = $row['label'];
            $data[] = (int) $row['total_users'];
        }
        return ['labels' => $labels, 'data' => $data];
    }

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
             WHERE submitted_at >= date_trunc('month', CURRENT_DATE)
               AND submitted_at < date_trunc('month', CURRENT_DATE) + INTERVAL '1 month'"
        )->fetchColumn();
    }

    private function topService(): array
    {
        $stmt = $this->conn->query(
            "SELECT s.name, s.category, COUNT(sa.id) AS cnt
             FROM service_applications sa
             JOIN services s ON s.id = sa.service_id
             WHERE sa.submitted_at >= date_trunc('month', CURRENT_DATE)
               AND sa.submitted_at < date_trunc('month', CURRENT_DATE) + INTERVAL '1 month'
             GROUP BY s.id, s.name, s.category
             ORDER BY COUNT(sa.id) DESC, s.name
             LIMIT 1"
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return ['name' => 'N/A', 'category' => 'other', 'cnt' => 0];
        }
        $row['cnt'] = (int) $row['cnt'];
        return $row;
    }

    private function requestsByMonth(int $year): array
    {
        $stmt = $this->conn->prepare(
            "SELECT EXTRACT(MONTH FROM submitted_at)::integer AS mo, COUNT(*) AS cnt
             FROM service_applications
             WHERE EXTRACT(YEAR FROM submitted_at)::integer = :yr
             GROUP BY 1"
        );
        $stmt->execute([':yr' => $year]);
        $data = array_fill(0, 12, 0);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $data[(int) $r['mo'] - 1] = (int) $r['cnt'];
        }
        return $data;
    }

    private function serviceBreakdownByCategory(): array
    {
        $stmt = $this->conn->query(
            "SELECT s.category, COUNT(sa.id) AS cnt
             FROM service_applications sa
             JOIN services s ON s.id = sa.service_id
             WHERE sa.submitted_at >= date_trunc('month', CURRENT_DATE)
               AND sa.submitted_at < date_trunc('month', CURRENT_DATE) + INTERVAL '1 month'
             GROUP BY s.category
             ORDER BY COUNT(sa.id) DESC, s.category"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function requestsByServiceType(): array
    {
        $stmt = $this->conn->query(
            "SELECT s.service_type, COUNT(sa.id) AS cnt
             FROM service_applications sa
             JOIN services s ON s.id = sa.service_id
             GROUP BY s.service_type"
        );
        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[$row['service_type']] = (int) $row['cnt'];
        }
        $defaults = ['document' => 0, 'appointment' => 0, 'info' => 0];
        return array_merge($defaults, $rows);
    }

    private function requestStatusCounts(): array
    {
        $stmt = $this->conn->query(
            "SELECT status, COUNT(*) AS cnt
             FROM service_applications
             GROUP BY status"
        );
        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[$row['status']] = (int) $row['cnt'];
        }
        $defaults = ['pending' => 0, 'action_required' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0];
        return array_merge($defaults, $rows);
    }

    private function requestsByService(): array
    {
        $stmt = $this->conn->query(
            "SELECT s.name, s.category, s.service_type, COUNT(sa.id) AS cnt
             FROM service_applications sa
             JOIN services s ON s.id = sa.service_id
             GROUP BY s.id, s.name, s.category, s.service_type
             ORDER BY COUNT(sa.id) DESC, s.name
             LIMIT 10"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function announcementStats(): array
    {
        $row = $this->conn->query(
            "SELECT
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE status = 'active') AS published,
                COUNT(*) FILTER (WHERE status = 'draft') AS drafts,
                COUNT(*) FILTER (WHERE status = 'archived') AS archived,
                COUNT(*) FILTER (WHERE featured = TRUE AND status = 'active') AS featured,
                COUNT(*) FILTER (WHERE category = 'urgent' AND status = 'active') AS urgent
             FROM announcements"
        )->fetch(PDO::FETCH_ASSOC);
        return array_map('intval', $row);
    }

    private function eventStats(): array
    {
        $row = $this->conn->query(
            "SELECT
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE event_date >= CURRENT_DATE) AS upcoming,
                COUNT(*) FILTER (WHERE event_date < CURRENT_DATE) AS past,
                COUNT(*) FILTER (
                    WHERE event_date >= date_trunc('month', CURRENT_DATE)::date
                      AND event_date < (date_trunc('month', CURRENT_DATE) + INTERVAL '1 month')::date
                ) AS this_month
             FROM events"
        )->fetch(PDO::FETCH_ASSOC);
        return array_map('intval', $row);
    }

    private function threadStats(): array
    {
        $row = $this->conn->query(
            "SELECT
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE is_removed = FALSE) AS published,
                COUNT(*) FILTER (WHERE is_removed = TRUE) AS removed,
                COUNT(*) FILTER (WHERE is_flagged = TRUE AND is_removed = FALSE) AS flagged,
                COUNT(*) FILTER (WHERE is_pinned = TRUE AND is_removed = FALSE) AS pinned,
                COUNT(*) FILTER (WHERE status = 'pending' AND is_removed = FALSE) AS pending,
                COUNT(*) FILTER (WHERE status = 'responded' AND is_removed = FALSE) AS responded,
                COUNT(*) FILTER (WHERE status = 'resolved' AND is_removed = FALSE) AS resolved
             FROM threads"
        )->fetch(PDO::FETCH_ASSOC);
        return array_map('intval', $row);
    }

    private function reportStats(): array
    {
        $threads = $this->conn->query(
            "SELECT
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE status = 'pending') AS pending,
                COUNT(*) FILTER (WHERE status = 'reviewed') AS reviewed,
                COUNT(*) FILTER (WHERE status = 'dismissed') AS dismissed
             FROM thread_reports"
        )->fetch(PDO::FETCH_ASSOC);

        $comments = $this->conn->query(
            "SELECT
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE status = 'pending') AS pending,
                COUNT(*) FILTER (WHERE status = 'reviewed') AS reviewed,
                COUNT(*) FILTER (WHERE status = 'dismissed') AS dismissed
             FROM comment_reports"
        )->fetch(PDO::FETCH_ASSOC);

        return [
            'threads'  => array_map('intval', $threads  ?: ['total' => 0, 'pending' => 0, 'reviewed' => 0, 'dismissed' => 0]),
            'comments' => array_map('intval', $comments ?: ['total' => 0, 'pending' => 0, 'reviewed' => 0, 'dismissed' => 0]),
        ];
    }

    private function availableYears(): array
    {
        $stmt  = $this->conn->query(
            "SELECT DISTINCT EXTRACT(YEAR FROM submitted_at)::integer AS yr
             FROM service_applications
             ORDER BY yr DESC"
        );
        $years = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        $cur   = (int) date('Y');
        if (!in_array($cur, $years, true)) {
            $years[] = $cur;
        }
        rsort($years, SORT_NUMERIC);
        return $years;
    }
}