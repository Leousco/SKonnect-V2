<?php
require_once __DIR__ . '/../config/database.php';

class BookmarkModel
{
    private $conn;

    public function __construct()
    {
        $db         = new Database();
        $this->conn = $db->getConnection();
    }

    public function toggle(int $userId, int $announcementId): bool
    {
        if ($this->isBookmarked($userId, $announcementId)) {
            $stmt = $this->conn->prepare(
                "DELETE FROM announcement_bookmarks
                 WHERE user_id = :uid AND announcement_id = :aid"
            );
            $stmt->execute([':uid' => $userId, ':aid' => $announcementId]);
            return false; 
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO announcement_bookmarks (user_id, announcement_id)
             VALUES (:uid, :aid)"
        );
        $stmt->execute([':uid' => $userId, ':aid' => $announcementId]);
        return true; 
    }

    /* Check whether a specific announcement is bookmarked by a user. */
    public function isBookmarked(int $userId, int $announcementId): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT id FROM announcement_bookmarks
             WHERE user_id = :uid AND announcement_id = :aid
             LIMIT 1"
        );
        $stmt->execute([':uid' => $userId, ':aid' => $announcementId]);
        return (bool) $stmt->fetch();
    }

    public function getByUser(int $userId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT a.*,
                    CONCAT(u.first_name, ' ', u.last_name) AS author_name,
                    b.created_at AS bookmarked_at
             FROM announcement_bookmarks b
             JOIN announcements a ON a.id = b.announcement_id
             JOIN users         u ON u.id = a.author_id
             WHERE b.user_id   = :uid
               AND a.status   != 'archived'
               AND (a.expired_at IS NULL OR a.expired_at >= CURDATE())
             ORDER BY b.created_at DESC"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBookmarkedIds(int $userId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT announcement_id
             FROM announcement_bookmarks
             WHERE user_id = :uid"
        );
        $stmt->execute([':uid' => $userId]);
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'announcement_id');
    }
}
?>