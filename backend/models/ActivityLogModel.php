<?php
// backend/models/ActivityLogModel.php

class ActivityLogModel
{
    private PDO $conn;

    const MOD_ACTIONS = [
        'thread_flagged',       'thread_unflagged',
        'thread_removed',       'thread_restored',
        'thread_pinned',        'thread_unpinned',
        'thread_status_updated',
        'comment_removed',
        'mod_comment_posted',
        'warning_issued', 'mute_issued', 'ban_issued', 'sanction_cleared',
        'report_resolved', 'report_dismissed',
    ];

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Insert a moderator activity log entry.
     * $meta keys: target_type, target_id, target_name, target_user, notes
     */
    public function log(int $user_id, string $action, array $meta, ?string $ip = null): bool
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO activity_logs (user_id, action, description, ip_address)
             VALUES (:uid, :action, :desc, :ip)"
        );
        return $stmt->execute([
            ':uid'    => $user_id,
            ':action' => $action,
            ':desc'   => json_encode($meta, JSON_UNESCAPED_UNICODE),
            ':ip'     => $ip ?? ($_SERVER['REMOTE_ADDR'] ?? null),
        ]);
    }

    /**
     * Fetch paginated mod logs with optional filters.
     * Filters: action, moderator_id, date_from (Y-m-d), date_to (Y-m-d), search
     */
    public function getLogs(array $filters = [], int $page = 1, int $per_page = 100): array
    {
        [$where, $params] = $this->buildWhere($filters);

        $offset   = ($page - 1) * $per_page;

        // Build the query with placeholders
        $sql = "SELECT al.id, al.user_id, al.action, al.description, al.created_at,
                   CONCAT(u.first_name, ' ', u.last_name) AS moderator_name
            FROM activity_logs al
            LEFT JOIN users u ON u.id = al.user_id
            $where
            ORDER BY al.created_at DESC
            LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($sql);

        // Bind parameters explicitly with types
        $paramIndex = 1;
        foreach ($params as $param) {
            $stmt->bindValue($paramIndex++, $param);
        }
        $stmt->bindValue($paramIndex++, $per_page, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countLogs(array $filters = []): int
    {
        [$where, $params] = $this->buildWhere($filters);
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM activity_logs al $where"
        );
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getStats(): array
    {
        $ph   = $this->ph(self::MOD_ACTIONS);
        $stmt = $this->conn->prepare(
            "SELECT
                COUNT(*)                                                    AS total,
                SUM(action IN ('report_resolved','report_dismissed'))       AS reports_handled,
                SUM(action IN ('warning_issued','mute_issued','ban_issued')) AS sanctions_issued,
                SUM(action = 'thread_removed')                              AS threads_hidden
             FROM activity_logs
             WHERE action IN ($ph)"
        );
        $stmt->execute(self::MOD_ACTIONS);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return array_map('intval', $row ?: [
            'total' => 0, 'reports_handled' => 0,
            'sanctions_issued' => 0, 'threads_hidden' => 0,
        ]);
    }

    public function getModerators(): array
    {
        $ph   = $this->ph(self::MOD_ACTIONS);
        $stmt = $this->conn->prepare(
            "SELECT DISTINCT al.user_id,
                    CONCAT(u.first_name, ' ', u.last_name) AS name
             FROM activity_logs al
             JOIN users u ON u.id = al.user_id
             WHERE al.action IN ($ph)
             ORDER BY name ASC"
        );
        $stmt->execute(self::MOD_ACTIONS);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buildWhere(array $filters): array
    {
        $ph         = $this->ph(self::MOD_ACTIONS);
        $conditions = ["al.action IN ($ph)"];
        $params     = self::MOD_ACTIONS;

        if (!empty($filters['action']) && $filters['action'] !== 'all') {
            $conditions[] = 'al.action = ?';
            $params[]     = $filters['action'];
        }
        if (!empty($filters['moderator_id']) && $filters['moderator_id'] !== 'all') {
            $conditions[] = 'al.user_id = ?';
            $params[]     = (int)$filters['moderator_id'];
        }
        if (!empty($filters['date_from'])) {
            $conditions[] = 'DATE(al.created_at) >= ?';
            $params[]     = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = 'DATE(al.created_at) <= ?';
            $params[]     = $filters['date_to'];
        }

        return ['WHERE ' . implode(' AND ', $conditions), $params];
    }

    private function ph(array $arr): string
    {
        return implode(',', array_fill(0, count($arr), '?'));
    }
}