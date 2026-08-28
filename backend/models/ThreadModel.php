<?php
// backend/models/ThreadModel.php

class ThreadModel
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getFeedThreads(int $user_id): array
    {
        $stmt = $this->conn->prepare(
            "SELECT
                t.id,
                t.category,
                t.subject,
                t.message,
                t.status,
                t.is_pinned,
                t.created_at,
                CONCAT(u.first_name, ' ', u.last_name) AS author_name,
                (SELECT COUNT(*) FROM thread_comments  tc  WHERE tc.thread_id  = t.id AND tc.is_removed = 0)           AS comment_count,
                (SELECT COUNT(*) FROM thread_supports  ts  WHERE ts.thread_id  = t.id)                                AS support_count,
                (SELECT COUNT(*) FROM thread_bookmarks tb  WHERE tb.thread_id  = t.id AND tb.user_id = :uid1)          AS is_bookmarked,
                (SELECT COUNT(*) FROM thread_supports  ts2 WHERE ts2.thread_id = t.id AND ts2.user_id = :uid2)         AS user_supported
             FROM threads t
             JOIN users u ON u.id = t.author_id
             WHERE t.is_removed = 0
             ORDER BY t.is_pinned DESC, t.created_at DESC"
        );
        $stmt->execute([':uid1' => $user_id, ':uid2' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getThreadById(int $thread_id, int $user_id): array|false
    {
        $stmt = $this->conn->prepare(
            "SELECT
                t.*,
                CONCAT(u.first_name, ' ', u.last_name) AS author_name,
                (SELECT COUNT(*) FROM thread_supports  ts  WHERE ts.thread_id  = t.id)                                AS support_count,
                (SELECT COUNT(*) FROM thread_comments  tc  WHERE tc.thread_id  = t.id AND tc.is_removed = 0)           AS comment_count,
                (SELECT COUNT(*) FROM thread_bookmarks tb  WHERE tb.thread_id  = t.id AND tb.user_id = :uid1)          AS is_bookmarked,
                (SELECT COUNT(*) FROM thread_supports  ts2 WHERE ts2.thread_id = t.id AND ts2.user_id = :uid2)         AS user_supported
             FROM threads t
             JOIN users u ON u.id = t.author_id
             WHERE t.id = :tid AND t.is_removed = 0
             LIMIT 1"
        );
        $stmt->execute([':uid1' => $user_id, ':uid2' => $user_id, ':tid' => $thread_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getThreadImages(int $thread_id): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM thread_images WHERE thread_id = :tid ORDER BY id ASC"
        );
        $stmt->execute([':tid' => $thread_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createThread(int $author_id, string $category, string $subject, string $message): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO threads (author_id, category, subject, message)
             VALUES (:author_id, :category, :subject, :message)"
        );
        $stmt->execute([
            ':author_id' => $author_id,
            ':category'  => $category,
            ':subject'   => $subject,
            ':message'   => $message,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function addThreadImage(int $thread_id, string $file_name, string $file_path): void
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO thread_images (thread_id, file_name, file_path)
             VALUES (:thread_id, :file_name, :file_path)"
        );
        $stmt->execute([
            ':thread_id' => $thread_id,
            ':file_name' => $file_name,
            ':file_path' => $file_path,
        ]);
    }

    public function updateThreadStatus(int $thread_id, string $status): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE threads SET status = :status WHERE id = :tid"
        );
        return $stmt->execute([':status' => $status, ':tid' => $thread_id]);
    }

    public function setThreadFlag(int $thread_id, int $flagged): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE threads SET is_flagged = :flagged WHERE id = :tid"
        );
        return $stmt->execute([':flagged' => $flagged, ':tid' => $thread_id]);
    }

    public function setThreadRemoved(int $thread_id, int $removed): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE threads SET is_removed = :removed WHERE id = :tid"
        );
        return $stmt->execute([':removed' => $removed, ':tid' => $thread_id]);
    }

    public function setThreadPinned(int $thread_id, int $pinned): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE threads SET is_pinned = :pinned WHERE id = :tid"
        );
        return $stmt->execute([':pinned' => $pinned, ':tid' => $thread_id]);
    }

    public function getModFeedThreads(): array
    {
        $sql = "
            SELECT
                t.id,
                t.category,
                t.subject,
                t.message,
                t.status,
                t.is_removed,
                t.removed_by_user,
                t.is_flagged,
                t.is_pinned,
                t.created_at,
                CONCAT(u.first_name, ' ', u.last_name) AS author_name,
                (SELECT COUNT(*) FROM thread_comments tc WHERE tc.thread_id = t.id AND tc.is_removed = 0) AS comment_count,
                (SELECT COUNT(*) FROM thread_supports ts WHERE ts.thread_id = t.id) AS support_count
            FROM threads t
            JOIN users u ON u.id = t.author_id
            GROUP BY t.id
            ORDER BY t.is_pinned DESC, t.created_at DESC
        ";

        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function removeThreadByUser(int $thread_id, int $author_id): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE threads
             SET is_removed = 1, removed_by_user = 1
             WHERE id = :tid AND author_id = :uid AND is_removed = 0"
        );
        $ok = $stmt->execute([':tid' => $thread_id, ':uid' => $author_id]);
        return $ok && $stmt->rowCount() > 0;
    }

    // ── EMAIL + NOTIFICATION HELPERS ──────────────────────────────────────────

    /**
     * Returns author email, full name, user ID, and thread subject for a thread.
     * Used by PostCommentController to notify the thread author.
     */
    public function getThreadAuthor(int $thread_id): array|false
    {
        $stmt = $this->conn->prepare(
            "SELECT
                u.id    AS author_id,
                u.email,
                CONCAT(u.first_name, ' ', u.last_name) AS name,
                t.subject,
                t.status
             FROM threads t
             JOIN users u ON u.id = t.author_id
             WHERE t.id = :tid
             LIMIT 1"
        );
        $stmt->execute([':tid' => $thread_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Given a comment ID, returns the parent thread author's info.
     * Used by PostReplyController to notify the thread author of a reply.
     */
    public function getThreadAuthorByComment(int $comment_id): array|false
    {
        $stmt = $this->conn->prepare(
            "SELECT
                u.id    AS author_id,
                u.email,
                CONCAT(u.first_name, ' ', u.last_name) AS name,
                t.subject
             FROM thread_comments tc
             JOIN threads t ON t.id = tc.thread_id
             JOIN users   u ON u.id = t.author_id
             WHERE tc.id = :cid
             LIMIT 1"
        );
        $stmt->execute([':cid' => $comment_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Given a comment ID, returns the COMMENT author's user ID, the parent
     * thread ID, and the thread subject.
     * Used by PostReplyController to notify the comment author.
     */
    public function getCommentAuthorAndThread(int $comment_id): array|false
    {
        $stmt = $this->conn->prepare(
            "SELECT
                tc.author_id AS comment_author_id,
                tc.thread_id,
                t.subject    AS thread_subject
             FROM thread_comments tc
             JOIN threads t ON t.id = tc.thread_id
             WHERE tc.id = :cid
             LIMIT 1"
        );
        $stmt->execute([':cid' => $comment_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}