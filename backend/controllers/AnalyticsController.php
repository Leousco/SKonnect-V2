<?php

class AnalyticsController
{
    private PDO $conn;
    private ?string $from = null;
    private ?string $to = null;
    private bool $filtered = false;
    private array $filterMeta = [];

    private const MONTHS = [
        'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
    ];

    public function __construct(PDO $conn, ?string $from = null, ?string $to = null)
    {
        $this->conn = $conn;

        $from = self::normalizeDate($from);
        $to   = self::normalizeDate($to);

        // A lone boundary is treated as a single day so the page never
        // renders a half-open period it cannot label.
        if ($from === null && $to !== null) {
            $from = $to;
        }
        if ($to === null && $from !== null) {
            $to = $from;
        }

        if ($from !== null && $to !== null && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        $this->from     = $from;
        $this->to       = $to;
        $this->filtered = $from !== null && $to !== null;
        $this->filterMeta = $this->buildFilterMeta();
    }

    public function getAll(int $year): array
    {
        $availableYears = $this->availableYears();
        if (!in_array($year, $availableYears, true)) {
            $year = (int) date('Y');
        }

        $timeline = $this->requestsTimeline($year);
        $growth   = $this->userGrowth();

        return [
            'totalUsers'          => $this->totalUsers(),
            'newUsers'            => $this->newUsers(),
            'activeUsers'         => $this->activeUsers(),
            'inactiveUsers'       => $this->inactiveUsers(),
            'activePct'           => $this->activePct(),
            'usersByRole'         => $this->usersByRole(),
            'growthLabels'        => $growth['labels'],
            'growthData'          => $growth['data'],
            'growthGranularity'   => $growth['granularity'],

            'totalRequests'       => $this->totalRequests(),
            'requestsInPeriod'    => $this->requestsInPeriod(),
            'topService'          => $this->topService(),
            'requestsTimeline'    => $timeline,
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
            'filter'              => $this->filterMeta,
        ];
    }

    // ── Date helpers ────────────────────────────────────────────────────

    private static function normalizeDate(?string $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        $value = trim($value);
        $date  = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            return null;
        }
        return $date->format('Y-m-d');
    }

    private function buildFilterMeta(): array
    {
        if (!$this->filtered) {
            return [
                'mode'  => 'all',
                'from'  => null,
                'to'    => null,
                'label' => 'All time',
                'days'  => null,
            ];
        }

        $days = (int) floor((strtotime($this->to) - strtotime($this->from)) / 86400) + 1;

        if ($this->from === $this->to) {
            return [
                'mode'  => 'day',
                'from'  => $this->from,
                'to'    => $this->to,
                'label' => date('F j, Y', strtotime($this->from)),
                'days'  => 1,
            ];
        }

        return [
            'mode'  => 'range',
            'from'  => $this->from,
            'to'    => $this->to,
            'label' => $this->formatRangeLabel($this->from, $this->to),
            'days'  => $days,
        ];
    }

    private function formatRangeLabel(string $from, string $to): string
    {
        $f = new DateTimeImmutable($from);
        $t = new DateTimeImmutable($to);

        if ($f->format('Y-m') === $t->format('Y-m')) {
            return $f->format('F j') . ' – ' . $t->format('j, Y');
        }
        if ($f->format('Y') === $t->format('Y')) {
            return $f->format('F j') . ' – ' . $t->format('F j, Y');
        }
        return $f->format('F j, Y') . ' – ' . $t->format('F j, Y');
    }

    /**
     * SQL condition for a timestamp column, inclusive of both dates.
     */
    private function tsCondition(string $column, array &$params): string
    {
        $sql = '';

        if ($this->from !== null) {
            $sql .= " AND {$column} >= :an_from";
            $params[':an_from'] = $this->from . ' 00:00:00';
        }
        if ($this->to !== null) {
            $sql .= " AND {$column} < :an_to_exclusive";
            $params[':an_to_exclusive'] = date('Y-m-d', strtotime($this->to . ' +1 day')) . ' 00:00:00';
        }

        return $sql;
    }

    /**
     * SQL condition for a date column, inclusive of both dates.
     */
    private function dateCondition(string $column, array &$params): string
    {
        $sql = '';

        if ($this->from !== null) {
            $sql .= " AND {$column} >= :an_from_date";
            $params[':an_from_date'] = $this->from;
        }
        if ($this->to !== null) {
            $sql .= " AND {$column} <= :an_to_date";
            $params[':an_to_date'] = $this->to;
        }

        return $sql;
    }

    private function granularityForRange(): string
    {
        if ($this->from === $this->to) {
            return 'hour';
        }

        $days = (int) floor((strtotime($this->to) - strtotime($this->from)) / 86400) + 1;
        if ($days <= 31) {
            return 'day';
        }
        if ($days <= 180) {
            return 'week';
        }
        return 'month';
    }

    /**
     * @return DateTimeImmutable[]
     */
    private function bucketStarts(string $granularity): array
    {
        $from = new DateTimeImmutable($this->from);
        $to   = new DateTimeImmutable($this->to);

        if ($granularity === 'hour') {
            $cursor = $from->setTime(0, 0);
            $end    = $to->setTime(23, 0);
            $step   = '+1 hour';
        } elseif ($granularity === 'day') {
            $cursor = $from->setTime(0, 0);
            $end    = $to->setTime(0, 0);
            $step   = '+1 day';
        } elseif ($granularity === 'week') {
            $offset = ((int) $from->format('N')) - 1;
            $cursor = $from->modify("-{$offset} days")->setTime(0, 0);
            $end    = $to->setTime(0, 0);
            $step   = '+7 days';
        } else {
            $cursor = $from->modify('first day of this month')->setTime(0, 0);
            $end    = $to->modify('first day of this month')->setTime(0, 0);
            $step   = '+1 month';
        }

        $starts = [];
        while ($cursor <= $end) {
            $starts[] = $cursor;
            $cursor   = $cursor->modify($step);
        }
        return $starts;
    }

    private function bucketKey(DateTimeImmutable $date, string $granularity): string
    {
        return $granularity === 'hour'
            ? $date->format('Y-m-d H:00:00')
            : $date->format('Y-m-d 00:00:00');
    }

    private function bucketLabel(DateTimeImmutable $date, string $granularity): string
    {
        return match ($granularity) {
            'hour'        => $date->format('g A'),
            'day', 'week' => $date->format('M j'),
            default       => $date->format('M Y'),
        };
    }

    /**
     * Count rows per time bucket inside the active filter.
     */
    private function bucketCounts(
        string $fromSql,
        string $column,
        string $granularity,
        string $extraWhere = ''
    ): array {
        $params = [];
        $range  = $this->tsCondition($column, $params);
        $unit   = in_array($granularity, ['hour', 'day', 'week', 'month'], true)
            ? $granularity
            : 'day';

        $sql = "SELECT date_trunc('{$unit}', {$column}) AS bucket, COUNT(*) AS cnt
                FROM {$fromSql}
                WHERE 1 = 1 {$range}";
        if ($extraWhere !== '') {
            $sql .= " AND {$extraWhere}";
        }
        $sql .= ' GROUP BY 1 ORDER BY 1';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        $counts = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ts  = strtotime((string) $row['bucket']);
            $key = $granularity === 'hour'
                ? date('Y-m-d H:00:00', $ts)
                : date('Y-m-d 00:00:00', $ts);
            $counts[$key] = (int) $row['cnt'];
        }

        return $counts;
    }

    // ── Users ───────────────────────────────────────────────────────────

    private function totalUsers(): int
    {
        $params = [];
        $range  = $this->tsCondition('u.created_at', $params);
        $stmt   = $this->conn->prepare(
            "SELECT COUNT(*)
             FROM users u
             JOIN user_status us ON us.user_id = u.id
             WHERE us.is_deleted = FALSE {$range}"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private function newUsers(): int
    {
        if ($this->filtered) {
            return $this->totalUsers();
        }
        return $this->newUsersThisMonth();
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
        $params = [];
        $range  = $this->tsCondition('u.created_at', $params);
        $stmt   = $this->conn->prepare(
            "SELECT COUNT(*)
             FROM user_status us
             JOIN users u ON u.id = us.user_id
             WHERE us.is_active = TRUE AND us.is_banned = FALSE AND us.is_deleted = FALSE {$range}"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private function inactiveUsers(): int
    {
        $params = [];
        $range  = $this->tsCondition('u.created_at', $params);
        $stmt   = $this->conn->prepare(
            "SELECT COUNT(*)
             FROM user_status us
             JOIN users u ON u.id = us.user_id
             WHERE (us.is_active = FALSE OR us.is_banned = TRUE)
               AND us.is_deleted = FALSE {$range}"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private function activePct(): int
    {
        $total  = $this->totalUsers();
        $active = $this->activeUsers();
        return $total > 0 ? (int) round($active / $total * 100) : 0;
    }

    private function usersByRole(): array
    {
        $params = [];
        $range  = $this->tsCondition('u.created_at', $params);
        $stmt   = $this->conn->prepare(
            "SELECT u.role, COUNT(*) AS cnt
             FROM users u
             JOIN user_status us ON us.user_id = u.id
             WHERE us.is_deleted = FALSE {$range}
             GROUP BY u.role"
        );
        $stmt->execute($params);

        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[$row['role']] = (int) $row['cnt'];
        }

        $defaults = ['admin' => 0, 'resident' => 0, 'moderator' => 0, 'sk_officer' => 0];
        return array_merge($defaults, $rows);
    }

    private function userGrowth(): array
    {
        if (!$this->filtered) {
            return $this->userGrowthLast12Months();
        }

        $granularity = $this->granularityForRange();
        $starts      = $this->bucketStarts($granularity);
        $counts      = $this->bucketCounts(
            'users u JOIN user_status us ON us.user_id = u.id',
            'u.created_at',
            $granularity,
            'us.is_deleted = FALSE'
        );

        $baseStmt = $this->conn->prepare(
            "SELECT COUNT(*)
             FROM users u
             JOIN user_status us ON us.user_id = u.id
             WHERE us.is_deleted = FALSE AND u.created_at < :an_base_from"
        );
        $baseStmt->execute([':an_base_from' => $this->from . ' 00:00:00']);
        $running = (int) $baseStmt->fetchColumn();

        $labels = [];
        $data   = [];
        foreach ($starts as $start) {
            $running += $counts[$this->bucketKey($start, $granularity)] ?? 0;
            $labels[] = $this->bucketLabel($start, $granularity);
            $data[]   = $running;
        }

        return ['labels' => $labels, 'data' => $data, 'granularity' => $granularity];
    }

    private function userGrowthLast12Months(): array
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
        $data   = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $labels[] = $row['label'];
            $data[]   = (int) $row['total_users'];
        }

        return ['labels' => $labels, 'data' => $data, 'granularity' => 'month'];
    }

    // ── Service requests ────────────────────────────────────────────────

    private function totalRequests(): int
    {
        $params = [];
        $range  = $this->tsCondition('submitted_at', $params);
        $stmt   = $this->conn->prepare(
            "SELECT COUNT(*) FROM service_applications WHERE 1 = 1 {$range}"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private function requestsInPeriod(): int
    {
        if ($this->filtered) {
            return $this->totalRequests();
        }
        return $this->requestsThisMonth();
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
        $params = [];

        if ($this->filtered) {
            $where = $this->tsCondition('sa.submitted_at', $params);
        } else {
            $where = " AND sa.submitted_at >= date_trunc('month', CURRENT_DATE)
                       AND sa.submitted_at < date_trunc('month', CURRENT_DATE) + INTERVAL '1 month'";
        }

        $stmt = $this->conn->prepare(
            "SELECT s.name, s.category, COUNT(sa.id) AS cnt
             FROM service_applications sa
             JOIN services s ON s.id = sa.service_id
             WHERE 1 = 1 {$where}
             GROUP BY s.id, s.name, s.category
             ORDER BY COUNT(sa.id) DESC, s.name
             LIMIT 1"
        );
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ['name' => 'N/A', 'category' => 'other', 'cnt' => 0];
        }
        $row['cnt'] = (int) $row['cnt'];
        return $row;
    }

    private function requestsTimeline(int $year): array
    {
        if (!$this->filtered) {
            return [
                'labels'      => self::MONTHS,
                'data'        => $this->requestsByMonth($year),
                'granularity' => 'month',
                'title'       => 'Service requests per month',
            ];
        }

        $granularity = $this->granularityForRange();
        $starts      = $this->bucketStarts($granularity);
        $counts      = $this->bucketCounts('service_applications', 'submitted_at', $granularity);

        $labels = [];
        $data   = [];
        foreach ($starts as $start) {
            $labels[] = $this->bucketLabel($start, $granularity);
            $data[]   = $counts[$this->bucketKey($start, $granularity)] ?? 0;
        }

        $title = match ($granularity) {
            'hour'  => 'Service requests by hour',
            'day'   => 'Service requests by day',
            'week'  => 'Service requests by week',
            default => 'Service requests by month',
        };

        return [
            'labels'      => $labels,
            'data'        => $data,
            'granularity' => $granularity,
            'title'       => $title,
        ];
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
        $params = [];
        $where  = $this->filtered
            ? $this->tsCondition('sa.submitted_at', $params)
            : " AND sa.submitted_at >= date_trunc('month', CURRENT_DATE)
                AND sa.submitted_at < date_trunc('month', CURRENT_DATE) + INTERVAL '1 month'";

        $stmt = $this->conn->prepare(
            "SELECT s.category, COUNT(sa.id) AS cnt
             FROM service_applications sa
             JOIN services s ON s.id = sa.service_id
             WHERE 1 = 1 {$where}
             GROUP BY s.category
             ORDER BY COUNT(sa.id) DESC, s.category"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function requestsByServiceType(): array
    {
        $params = [];
        $range  = $this->tsCondition('sa.submitted_at', $params);
        $stmt   = $this->conn->prepare(
            "SELECT s.service_type, COUNT(sa.id) AS cnt
             FROM service_applications sa
             JOIN services s ON s.id = sa.service_id
             WHERE 1 = 1 {$range}
             GROUP BY s.service_type"
        );
        $stmt->execute($params);

        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[$row['service_type']] = (int) $row['cnt'];
        }

        $defaults = ['document' => 0, 'appointment' => 0, 'info' => 0];
        return array_merge($defaults, $rows);
    }

    private function requestStatusCounts(): array
    {
        $params = [];
        $range  = $this->tsCondition('submitted_at', $params);
        $stmt   = $this->conn->prepare(
            "SELECT status, COUNT(*) AS cnt
             FROM service_applications
             WHERE 1 = 1 {$range}
             GROUP BY status"
        );
        $stmt->execute($params);

        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[$row['status']] = (int) $row['cnt'];
        }

        $defaults = ['pending' => 0, 'action_required' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0];
        return array_merge($defaults, $rows);
    }

    private function requestsByService(): array
    {
        $params = [];
        $range  = $this->tsCondition('sa.submitted_at', $params);
        $stmt   = $this->conn->prepare(
            "SELECT s.name, s.category, s.service_type, COUNT(sa.id) AS cnt
             FROM service_applications sa
             JOIN services s ON s.id = sa.service_id
             WHERE 1 = 1 {$range}
             GROUP BY s.id, s.name, s.category, s.service_type
             ORDER BY COUNT(sa.id) DESC, s.name
             LIMIT 10"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Content and moderation ──────────────────────────────────────────

    private function announcementStats(): array
    {
        $params = [];
        $range  = $this->tsCondition('COALESCE(published_at, updated_at)', $params);
        $stmt   = $this->conn->prepare(
            "SELECT
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE status = 'active') AS published,
                COUNT(*) FILTER (WHERE status = 'draft') AS drafts,
                COUNT(*) FILTER (WHERE status = 'archived') AS archived,
                COUNT(*) FILTER (WHERE featured = TRUE AND status = 'active') AS featured,
                COUNT(*) FILTER (WHERE category = 'urgent' AND status = 'active') AS urgent
             FROM announcements
             WHERE 1 = 1 {$range}"
        );
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return array_map('intval', $row);
    }

    private function eventStats(): array
    {
        $params = [];
        $range  = $this->dateCondition('event_date', $params);
        $stmt   = $this->conn->prepare(
            "SELECT
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE event_date >= CURRENT_DATE) AS upcoming,
                COUNT(*) FILTER (WHERE event_date < CURRENT_DATE) AS past,
                COUNT(*) FILTER (
                    WHERE event_date >= date_trunc('month', CURRENT_DATE)::date
                      AND event_date < (date_trunc('month', CURRENT_DATE) + INTERVAL '1 month')::date
                ) AS this_month,
                COUNT(*) FILTER (
                    WHERE event_date >= CURRENT_DATE
                      AND event_date < CURRENT_DATE + INTERVAL '30 days'
                ) AS next_30,
                COUNT(*) FILTER (
                    WHERE EXTRACT(YEAR FROM event_date) = EXTRACT(YEAR FROM CURRENT_DATE)
                ) AS this_year
             FROM events
             WHERE 1 = 1 {$range}"
        );
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return array_map('intval', $row);
    }

    private function threadStats(): array
    {
        $params = [];
        $range  = $this->tsCondition('created_at', $params);
        $stmt   = $this->conn->prepare(
            "SELECT
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE is_removed = FALSE) AS published,
                COUNT(*) FILTER (WHERE is_removed = TRUE) AS removed,
                COUNT(*) FILTER (WHERE is_flagged = TRUE AND is_removed = FALSE) AS flagged,
                COUNT(*) FILTER (WHERE is_pinned = TRUE AND is_removed = FALSE) AS pinned,
                COUNT(*) FILTER (WHERE status = 'pending' AND is_removed = FALSE) AS pending,
                COUNT(*) FILTER (WHERE status = 'responded' AND is_removed = FALSE) AS responded,
                COUNT(*) FILTER (WHERE status = 'resolved' AND is_removed = FALSE) AS resolved
             FROM threads
             WHERE 1 = 1 {$range}"
        );
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return array_map('intval', $row);
    }

    private function reportStats(): array
    {
        return [
            'threads'  => $this->reportTableStats('thread_reports'),
            'comments' => $this->reportTableStats('comment_reports'),
        ];
    }

    private function reportTableStats(string $table): array
    {
        $table  = $table === 'comment_reports' ? 'comment_reports' : 'thread_reports';
        $params = [];
        $range  = $this->tsCondition('created_at', $params);
        $stmt   = $this->conn->prepare(
            "SELECT
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE status = 'pending') AS pending,
                COUNT(*) FILTER (WHERE status = 'reviewed') AS reviewed,
                COUNT(*) FILTER (WHERE status = 'dismissed') AS dismissed
             FROM {$table}
             WHERE 1 = 1 {$range}"
        );
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return array_map('intval', $row);
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
