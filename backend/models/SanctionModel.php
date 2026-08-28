<?php
// backend/models/SanctionModel.php

class SanctionModel
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getActiveLevel(int $user_id): int
    {
        $this->expireOldBans($user_id);

        $stmt = $this->conn->prepare(
            "SELECT MAX(level) FROM user_sanctions
             WHERE user_id = :uid AND is_active = 1"
        );
        $stmt->execute([':uid' => $user_id]);
        return (int)$stmt->fetchColumn();
    }

    public function getByUser(int $user_id): array
    {
        $stmt = $this->conn->prepare(
            "SELECT us.*,
                    CONCAT(m.first_name, ' ', m.last_name) AS issued_by_name
             FROM user_sanctions us
             JOIN users m ON m.id = us.issued_by
             WHERE us.user_id = :uid
             ORDER BY us.created_at DESC"
        );
        $stmt->execute([':uid' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function issue(
        int    $user_id,
        int    $issued_by,
        int    $level,
        string $reason,
        ?int   $report_id = null
    ): int {
        $expires_at = $level === 2 ? date('Y-m-d H:i:s', strtotime('+7 days')) : null;

        $stmt = $this->conn->prepare(
            "INSERT INTO user_sanctions
                (user_id, issued_by, level, reason, report_id, expires_at, is_active)
             VALUES
                (:user_id, :issued_by, :level, :reason, :report_id, :expires_at, 1)"
        );
        $stmt->execute([
            ':user_id'    => $user_id,
            ':issued_by'  => $issued_by,
            ':level'      => $level,
            ':reason'     => $reason,
            ':report_id'  => $report_id,
            ':expires_at' => $expires_at,
        ]);

        $sanction_id = (int)$this->conn->lastInsertId();
        $this->syncUserBanFlag($user_id, $level, $expires_at);

        return $sanction_id;
    }

    public function getNextLevel(int $user_id): int
    {
        return min($this->getActiveLevel($user_id) + 1, 3);
    }

    public function clearSanctions(int $user_id): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE user_sanctions SET is_active = 0 WHERE user_id = :uid AND is_active = 1"
        );
        $ok = $stmt->execute([':uid' => $user_id]);
        if ($ok) {
            $this->syncUserBanFlag($user_id, 0, null);
        }
        return $ok;
    }

    public function getStats(): array
    {
        $row = $this->conn->query(
            "SELECT
                COUNT(*)                                      AS total_active,
                SUM(level = 1 AND is_active = 1)             AS level1,
                SUM(level = 2 AND is_active = 1)             AS level2,
                SUM(level = 3 AND is_active = 1)             AS level3,
                SUM(DATE(created_at) = CURDATE())            AS today
             FROM user_sanctions"
        )->fetch(PDO::FETCH_ASSOC);

        return $row ?: ['total_active' => 0, 'level1' => 0, 'level2' => 0, 'level3' => 0, 'today' => 0];
    }

    // ── PRIVATE HELPERS ───────────────────────────────────────────

    private function expireOldBans(int $user_id): void
    {
        $this->conn->prepare(
            "UPDATE user_sanctions
             SET is_active = 0
             WHERE user_id = :uid
               AND level = 2
               AND is_active = 1
               AND expires_at IS NOT NULL
               AND expires_at < NOW()"
        )->execute([':uid' => $user_id]);

        $remaining = $this->conn->prepare(
            "SELECT COUNT(*) FROM user_sanctions
             WHERE user_id = :uid AND is_active = 1 AND level >= 2"
        );
        $remaining->execute([':uid' => $user_id]);

        if ((int)$remaining->fetchColumn() === 0) {
            $perm = $this->conn->prepare(
                "SELECT COUNT(*) FROM user_sanctions
                 WHERE user_id = :uid AND is_active = 1 AND level = 3"
            );
            $perm->execute([':uid' => $user_id]);
            if ((int)$perm->fetchColumn() === 0) {
                $this->syncUserBanFlag($user_id, 0, null);
            }
        }
    }

    private function syncUserBanFlag(int $user_id, int $level, ?string $expires_at): void
    {
        $this->conn->prepare(
            "UPDATE user_status
             SET feed_ban_level   = :level,
                 feed_ban_expires = :expires
             WHERE user_id = :uid"
        )->execute([
            ':level'   => $level,
            ':expires' => $expires_at,
            ':uid'     => $user_id,
        ]);
    }
}