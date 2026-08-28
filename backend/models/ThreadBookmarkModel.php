<?php
// backend/models/ThreadBookmarkModel.php

class BookmarkModel
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Toggle bookmark state for a thread.
     * Returns true if now bookmarked, false if removed.
     */
    public function toggle(int $thread_id, int $user_id): bool
    {
        $check = $this->conn->prepare(
            "SELECT id FROM thread_bookmarks WHERE thread_id = :tid AND user_id = :uid"
        );
        $check->execute([':tid' => $thread_id, ':uid' => $user_id]);

        if ($check->fetch()) {
            $this->conn->prepare(
                "DELETE FROM thread_bookmarks WHERE thread_id = :tid AND user_id = :uid"
            )->execute([':tid' => $thread_id, ':uid' => $user_id]);
            return false;
        }

        $this->conn->prepare(
            "INSERT INTO thread_bookmarks (thread_id, user_id) VALUES (:tid, :uid)"
        )->execute([':tid' => $thread_id, ':uid' => $user_id]);
        return true;
    }

    /**
     * Fetch all threads bookmarked by a user, with counts and user state.
     */
    public function getBookmarkedThreads(int $user_id): array
    {
        $stmt = $this->conn->prepare(
            "SELECT
                t.id,
                t.category,
                t.subject,
                t.message,
                t.status,
                t.created_at,
                CONCAT(u.first_name, ' ', u.last_name) AS author_name,
                (SELECT COUNT(*) FROM thread_comments  tc  WHERE tc.thread_id  = t.id AND tc.is_removed = 0)           AS comment_count,
                (SELECT COUNT(*) FROM thread_supports  ts  WHERE ts.thread_id  = t.id)                                AS support_count,
                (SELECT COUNT(*) FROM thread_supports  ts2 WHERE ts2.thread_id = t.id AND ts2.user_id = :uid2)         AS user_supported,
                tb.created_at AS bookmarked_at
             FROM thread_bookmarks tb
             JOIN threads t ON t.id = tb.thread_id
             JOIN users   u ON u.id = t.author_id
             WHERE tb.user_id = :uid1
               AND t.is_removed = 0
             ORDER BY tb.created_at DESC"
        );
        $stmt->execute([':uid1' => $user_id, ':uid2' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}