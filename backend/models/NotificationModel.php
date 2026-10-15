<?php

require_once __DIR__ . '/../config/database.php';

class NotificationModel
{
    private PDO $conn;

    public function __construct(?PDO $connection = null)
    {
        $this->conn = $connection ?? (new Database())->getConnection();
    }

    private function bindOfficerFilterParams(PDOStatement $stmt, array $params): void
    {
        foreach ($params as $name => $value) {
            $type = match ($name) {
                ':uid' => PDO::PARAM_INT,
                ':is_read' => PDO::PARAM_BOOL,
                default => PDO::PARAM_STR,
            };
            $stmt->bindValue($name, $value, $type);
        }
    }

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
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':message', $message, PDO::PARAM_STR);
        $stmt->bindValue(':ref_type', $refType, $refType === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':ref_id', $refId, $refId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':link', $link, $link === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':is_official', (bool)$isOfficial, PDO::PARAM_BOOL);
        $stmt->execute();
        return (int) $this->conn->lastInsertId();
    }

    public function getByUser(int $userId, array $filters = []): array
    {
        $where  = ['user_id = :uid', 'is_dismissed = FALSE'];

        if (!empty($filters['type'])) {
            $where[] = 'type = :type';
        }

        if (isset($filters['is_read']) && $filters['is_read'] !== '') {
            $where[] = 'is_read = :is_read';
        }

        if (!empty($filters['search'])) {
            $where[] = '(title ILIKE :search OR message ILIKE :search)';
        }

        $sql  = 'SELECT * FROM notifications WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        if (!empty($filters['type'])) {
            $stmt->bindValue(':type', $filters['type'], PDO::PARAM_STR);
        }
        if (isset($filters['is_read']) && $filters['is_read'] !== '') {
            $stmt->bindValue(':is_read', (bool)$filters['is_read'], PDO::PARAM_BOOL);
        }
        if (!empty($filters['search'])) {
            $stmt->bindValue(':search', '%' . $filters['search'] . '%', PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOfficerNotifications(int $userId, array $filters = []): array
    {
        $categorySql = "CASE
            WHEN type IN ('service_request_overdue', 'request_overdue', 'request_attention', 'action_required') THEN 'attention'
            WHEN type IN ('admin_update', 'admin_announcement_update', 'admin_service_update') THEN 'admin_updates'
            WHEN type IN ('event_reminder', 'event') THEN 'events'
            WHEN type IN ('announcement_archived', 'announcement') THEN 'announcements'
            WHEN type IN (
                'service_request', 'new_service_request', 'request', 'new_request',
                'request_approved', 'request_rejected', 'request_cancelled', 'request_updated',
                'service', 'new_service', 'service_approved', 'service_rejected',
                'approved', 'rejected', 'cancelled', 'service_application'
            ) THEN 'requests'
            ELSE 'announcements'
        END";
        $where = ['user_id = :uid', 'is_dismissed = FALSE'];
        $params = [':uid' => $userId];

        if (isset($filters['category']) && $filters['category'] !== '') {
            $where[] = "({$categorySql}) = :category";
            $params[':category'] = $filters['category'];
        }
        if (isset($filters['is_read']) && $filters['is_read'] !== '') {
            $where[] = 'is_read = :is_read';
            $params[':is_read'] = (bool)$filters['is_read'];
        }
        if (isset($filters['search']) && $filters['search'] !== '') {
            $where[] = '(title ILIKE :search_title OR message ILIKE :search_message)';
            $search = '%' . $filters['search'] . '%';
            $params[':search_title'] = $search;
            $params[':search_message'] = $search;
        }

        $whereSql = implode(' AND ', $where);
        $stmt = $this->conn->prepare("
            SELECT id, type, title, message, {$categorySql} AS category,
                   is_read, created_at, link
            FROM notifications
            WHERE {$whereSql}
            ORDER BY created_at DESC, id DESC
            LIMIT :limit OFFSET :offset
        ");
        $this->bindOfficerFilterParams($stmt, $params);
        $stmt->bindValue(':limit', (int)$filters['limit'], PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)($filters['offset'] ?? 0), PDO::PARAM_INT);
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = $this->conn->prepare("
            SELECT COUNT(*) FROM notifications WHERE {$whereSql}
        ");
        $this->bindOfficerFilterParams($countStmt, $params);
        $countStmt->execute();

        $unreadStmt = $this->conn->prepare("
            SELECT COUNT(*) FROM notifications
            WHERE user_id = :uid AND is_dismissed = FALSE AND is_read = FALSE
        ");
        $unreadStmt->execute([':uid' => $userId]);

        foreach ($notifications as &$notification) {
            $notification['id'] = (int)$notification['id'];
            $notification['is_read'] = (int)$notification['is_read'];
        }
        unset($notification);

        return [
            'notifications' => $notifications,
            'total' => (int)$countStmt->fetchColumn(),
            'unreadCount' => (int)$unreadStmt->fetchColumn(),
        ];
    }

    public function getOfficerIds(): array
    {
        $stmt = $this->conn->query("SELECT id FROM users WHERE role = 'sk_officer' ORDER BY id");
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function updateHourlyRequestNotification(
        int $userId,
        int $hourBucket,
        int $requestCount
    ): void {
        $this->conn->beginTransaction();
        try {
            $this->lockNotificationKey("incoming-request:{$userId}:{$hourBucket}");
            $find = $this->conn->prepare("
                SELECT id, message FROM notifications
                WHERE user_id = :uid AND type = 'service_request'
                  AND ref_type = 'officer_request_hour' AND ref_id = :bucket
                LIMIT 1 FOR UPDATE
            ");
            $find->execute([':uid' => $userId, ':bucket' => $hourBucket]);
            $existing = $find->fetch(PDO::FETCH_ASSOC);

            $title = 'New service requests';
            $link = 'officer_requests.php';

            if ($existing) {
                $previousCount = null;
                if (preg_match('/^(\d+) new service requests?/', (string)$existing['message'], $matches)) {
                    $previousCount = (int)$matches[1];
                }
                $requestCount = max($requestCount, $previousCount ?? 0);
                $message = $requestCount === 1
                    ? '1 new service request was received during this hour.'
                    : "{$requestCount} new service requests were received during this hour.";
                $update = $this->conn->prepare("
                    UPDATE notifications
                    SET title = :title, message = :message, link = :link,
                        is_read = CASE
                            WHEN is_dismissed = FALSE AND :has_new_requests THEN FALSE
                            ELSE is_read
                        END
                    WHERE id = :id
                ");
                $update->bindValue(':title', $title, PDO::PARAM_STR);
                $update->bindValue(':message', $message, PDO::PARAM_STR);
                $update->bindValue(':link', $link, PDO::PARAM_STR);
                $update->bindValue(':has_new_requests', $previousCount !== $requestCount, PDO::PARAM_BOOL);
                $update->bindValue(':id', (int)$existing['id'], PDO::PARAM_INT);
                $update->execute();
            } else {
                $message = $requestCount === 1
                    ? '1 new service request was received during this hour.'
                    : "{$requestCount} new service requests were received during this hour.";
                $this->insertNotification(
                    $userId, 'service_request', $title, $message,
                    'officer_request_hour', $hourBucket, $link
                );
            }
            $this->conn->commit();
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function createOfficerNotificationOnce(
        int $userId,
        string $type,
        string $title,
        string $message,
        string $refType,
        int $refId,
        string $link
    ): bool {
        $this->conn->beginTransaction();
        try {
            $this->lockNotificationKey("{$type}:{$userId}:{$refType}:{$refId}");
            $find = $this->conn->prepare("
                SELECT 1 FROM notifications
                WHERE user_id = :uid AND type = :type
                  AND ref_type = :ref_type AND ref_id = :ref_id
                LIMIT 1
            ");
            $find->execute([
                ':uid' => $userId,
                ':type' => $type,
                ':ref_type' => $refType,
                ':ref_id' => $refId,
            ]);
            if ($find->fetchColumn()) {
                $this->conn->commit();
                return false;
            }

            $this->insertNotification($userId, $type, $title, $message, $refType, $refId, $link);
            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function createOverdueReminderIfDue(
        int $userId,
        int $applicationId,
        string $title,
        string $message,
        string $link
    ): bool {
        $this->conn->beginTransaction();
        try {
            $this->lockNotificationKey("overdue-request:{$userId}:{$applicationId}");
            $application = $this->conn->prepare("
                SELECT status, GREATEST(submitted_at, COALESCE(updated_at, submitted_at)) AS last_activity
                FROM service_applications WHERE id = :id
                LIMIT 1 FOR UPDATE
            ");
            $application->execute([':id' => $applicationId]);
            $row = $application->fetch(PDO::FETCH_ASSOC);
            if (!$row || !in_array($row['status'], ['pending', 'action_required'], true)) {
                $this->conn->commit();
                return false;
            }
            $elapsed = $this->conn->prepare("
                SELECT CAST(:last_activity AS timestamp) <= NOW() - INTERVAL '3 days'
            ");
            $elapsed->execute([':last_activity' => $row['last_activity']]);
            if (!$elapsed->fetchColumn()) {
                $this->conn->commit();
                return false;
            }
            $lastNotice = $this->conn->prepare("
                SELECT MAX(created_at) FROM notifications
                WHERE user_id = :uid AND type = 'service_request_overdue'
                  AND ref_type = 'service_application' AND ref_id = :id
            ");
            $lastNotice->execute([':uid' => $userId, ':id' => $applicationId]);
            $lastCreated = $lastNotice->fetchColumn();
            if ($lastCreated !== false && $lastCreated !== null) {
                $due = $this->conn->prepare("
                    SELECT CAST(:last_created AS timestamp) <= NOW() - INTERVAL '3 days'
                ");
                $due->execute([':last_created' => $lastCreated]);
                if (!$due->fetchColumn()) {
                    $this->conn->commit();
                    return false;
                }
            }

            $this->insertNotification(
                $userId, 'service_request_overdue', $title, $message,
                'service_application', $applicationId, $link
            );
            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    private function lockNotificationKey(string $key): void
    {
        $stmt = $this->conn->prepare('SELECT pg_advisory_xact_lock(hashtext(:key))');
        $stmt->execute([':key' => $key]);
    }

    private function insertNotification(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $refType,
        ?int $refId,
        ?string $link
    ): void {
        $stmt = $this->conn->prepare("
            INSERT INTO notifications
                (user_id, type, title, message, ref_type, ref_id, link, is_official)
            VALUES
                (:user_id, :type, :title, :message, :ref_type, :ref_id, :link, TRUE)
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':type' => $type,
            ':title' => $title,
            ':message' => $message,
            ':ref_type' => $refType,
            ':ref_id' => $refId,
            ':link' => $link,
        ]);
    }

    public function getStats(int $userId): array
    {
        $stmt = $this->conn->prepare("
            SELECT
                COUNT(*) FILTER (WHERE is_dismissed = FALSE)                                            AS total,
                COUNT(*) FILTER (WHERE is_dismissed = FALSE AND is_read = FALSE)                         AS unread,
                COUNT(*) FILTER (WHERE is_dismissed = FALSE AND created_at >= NOW() - INTERVAL '7 days') AS this_week,
                COUNT(*) FILTER (WHERE is_dismissed = TRUE)                                              AS dismissed
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

    

    public function markRead(int $id, int $userId): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE notifications SET is_read = TRUE WHERE id = :id AND user_id = :uid AND is_dismissed = FALSE"
        );
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public function markAllRead(int $userId): void
    {
        $this->conn->prepare(
            "UPDATE notifications SET is_read = TRUE WHERE user_id = :uid AND is_dismissed = FALSE"
        )->execute([':uid' => $userId]);
    }

    public function dismiss(int $id, int $userId): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE notifications SET is_dismissed = TRUE, is_read = TRUE WHERE id = :id AND user_id = :uid"
        );
        return $stmt->execute([':id' => $id, ':uid' => $userId]);
    }

    public function getAllResidentIds(): array
    {
        $stmt = $this->conn->query(
            "SELECT u.id FROM users u
             JOIN user_status us ON us.user_id = u.id
             WHERE u.role = 'resident' AND u.is_verified = TRUE AND us.is_deleted = FALSE"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}