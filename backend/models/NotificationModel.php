<?php
// backend/models/NotificationModel.php

require_once __DIR__ . '/../config/database.php';

class NotificationModel
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }

    // ── CREATE ────────────────────────────────────────────────────────────────

    public function create(
        int     $userId,
        string  $type,
        string  $title,
        string  $message,
        ?string $refType    = null,
        ?int    $refId      = null,
        ?string $link       = null,
        int     $isOfficial = 0
    ): int {
        $stmt = $this->conn->prepare("
            INSERT INTO notifications
                (user_id, type, title, message, ref_type, ref_id, link, is_official)
            VALUES
                (:user_id, :type, :title, :message, :ref_type, :ref_id, :link, :is_official)
        ");
        $stmt->execute([
            ':user_id'     => $userId,
            ':type'        => $type,
            ':title'       => $title,
            ':message'     => $message,
            ':ref_type'    => $refType,
            ':ref_id'      => $refId,
            ':link'        => $link,
            ':is_official' => $isOfficial,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    // ── READ ──────────────────────────────────────────────────────────────────

    /**
     * Fetch all non-dismissed notifications for a user, newest first.
     * Supports filtering by type, read status, and search keyword.
     */
    public function getByUser(int $userId, array $filters = []): array
    {
        $where  = ['user_id = :uid', 'is_dismissed = 0'];
        $params = [':uid' => $userId];

        if (!empty($filters['type'])) {
            $where[]         = 'type = :type';
            $params[':type'] = $filters['type'];
        }

        if (isset($filters['is_read']) && $filters['is_read'] !== '') {
            $where[]            = 'is_read = :is_read';
            $params[':is_read'] = (int) $filters['is_read'];
        }

        if (!empty($filters['search'])) {
            $where[]           = '(title LIKE :search OR message LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql  = 'SELECT * FROM notifications WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats(int $userId): array
    {
        $stmt = $this->conn->prepare("
            SELECT
                SUM(is_dismissed = 0)                                        AS total,
                SUM(is_dismissed = 0 AND is_read = 0)                        AS unread,
                SUM(is_dismissed = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS this_week,
                SUM(is_dismissed = 1)                                        AS dismissed
            FROM notifications
            WHERE user_id = :uid
        ");
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'total'     => (int) ($row['total']     ?? 0),
            'unread'    => (int) ($row['unread']    ?? 0),
            'this_week' => (int) ($row['this_week'] ?? 0),
            'dismissed' => (int) ($row['dismissed'] ?? 0),
        ];
    }

    // ── WRITE ─────────────────────────────────────────────────────────────────

    public function markRead(int $id, int $userId): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid"
        );
        return $stmt->execute([':id' => $id, ':uid' => $userId]);
    }

    public function markAllRead(int $userId): void
    {
        $this->conn->prepare(
            "UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_dismissed = 0"
        )->execute([':uid' => $userId]);
    }

    public function dismiss(int $id, int $userId): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE notifications SET is_dismissed = 1, is_read = 1 WHERE id = :id AND user_id = :uid"
        );
        return $stmt->execute([':id' => $id, ':uid' => $userId]);
    }

    // ── BROADCAST HELPERS ─────────────────────────────────────────────────────

    /**
     * Returns all verified resident user IDs for broadcast notifications.
     */
    public function getAllResidentIds(): array
    {
        $stmt = $this->conn->query(
            "SELECT u.id FROM users u
             JOIN user_status us ON us.user_id = u.id
             WHERE u.role = 'resident' AND u.is_verified = 1 AND us.is_deleted = 0"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}