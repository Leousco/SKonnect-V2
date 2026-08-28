<?php
// backend/models/CommentModel.php

class CommentModel
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Fetch all visible comments for a thread, with support counts and user state.
     * Each comment row will have a 'replies' key populated separately via getReplies().
     */
    public function getCommentsByThread(int $thread_id, int $user_id): array
    {
        // Include mod-removed comments (removed_by_mod = 1) so a tombstone can be
        // shown in their place. Comments removed by other means (is_removed = 1 but
        // removed_by_mod = 0) are still hidden entirely.
        $stmt = $this->conn->prepare(
            "SELECT
                tc.id,
                tc.thread_id,
                tc.message,
                tc.is_mod_comment,
                tc.is_removed,
                tc.removed_by_mod,
                tc.removed_by_user,
                tc.created_at,
                CONCAT(u.first_name, ' ', u.last_name) AS author_name,
                tc.author_id,
                (SELECT COUNT(*) FROM comment_supports cs  WHERE cs.comment_id  = tc.id)                        AS support_count,
                (SELECT COUNT(*) FROM comment_supports cs2 WHERE cs2.comment_id = tc.id AND cs2.user_id = :uid) AS user_supported
             FROM thread_comments tc
             JOIN users u ON u.id = tc.author_id
             WHERE tc.thread_id = :tid
               AND (tc.is_removed = 0 OR tc.removed_by_mod = 1 OR tc.removed_by_user = 1)
             ORDER BY tc.created_at ASC"
        );
        $stmt->execute([':uid' => $user_id, ':tid' => $thread_id]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Attach replies to each comment
        foreach ($comments as &$comment) {
            $comment['replies'] = $this->getRepliesByComment((int)$comment['id']);
        }
        unset($comment);

        return $comments;
    }

    /**
     * Fetch all visible replies for a comment.
     */
    public function getRepliesByComment(int $comment_id): array
    {
        // Include mod-removed replies so a tombstone can be shown.
        $stmt = $this->conn->prepare(
            "SELECT
                cr.id,
                cr.message,
                cr.is_mod_comment,
                cr.is_removed,
                cr.removed_by_mod,
                cr.removed_by_user,
                cr.created_at,
                CONCAT(u.first_name, ' ', u.last_name) AS author_name,
                cr.author_id
             FROM comment_replies cr
             JOIN users u ON u.id = cr.author_id
             WHERE cr.comment_id = :cid
               AND (cr.is_removed = 0 OR cr.removed_by_mod = 1 OR cr.removed_by_user = 1)
             ORDER BY cr.created_at ASC"
        );
        $stmt->execute([':cid' => $comment_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Insert a new comment. Returns the full inserted row (with author info).
     */
    public function createComment(int $thread_id, int $author_id, string $message, int $is_mod = 0): array|false
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO thread_comments (thread_id, author_id, message, is_mod_comment)
             VALUES (:tid, :uid, :msg, :is_mod)"
        );
        $stmt->execute([':tid' => $thread_id, ':uid' => $author_id, ':msg' => $message, ':is_mod' => $is_mod]);
        $comment_id = (int)$this->conn->lastInsertId();

        $fetch = $this->conn->prepare(
            "SELECT tc.id, tc.thread_id, tc.message, tc.is_mod_comment, tc.created_at,
                    u.first_name, u.last_name
             FROM thread_comments tc
             JOIN users u ON u.id = tc.author_id
             WHERE tc.id = :cid"
        );
        $fetch->execute([':cid' => $comment_id]);
        return $fetch->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Insert a new reply to a comment. Returns the full inserted row.
     */
    public function createReply(int $comment_id, int $author_id, string $message, int $is_mod = 0): array|false
    {
        // Verify parent comment exists and is not removed
        $check = $this->conn->prepare(
            "SELECT id FROM thread_comments WHERE id = :cid AND is_removed = 0 LIMIT 1"
        );
        $check->execute([':cid' => $comment_id]);
        if (!$check->fetch()) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO comment_replies (comment_id, author_id, message, is_mod_comment)
             VALUES (:cid, :uid, :msg, :is_mod)"
        );
        $stmt->execute([':cid' => $comment_id, ':uid' => $author_id, ':msg' => $message, ':is_mod' => $is_mod]);
        $reply_id = (int)$this->conn->lastInsertId();

        $fetch = $this->conn->prepare(
            "SELECT cr.id, cr.comment_id, cr.message, cr.is_mod_comment, cr.created_at,
                    u.first_name, u.last_name
             FROM comment_replies cr
             JOIN users u ON u.id = cr.author_id
             WHERE cr.id = :rid"
        );
        $fetch->execute([':rid' => $reply_id]);
        return $fetch->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check whether a thread exists and is not removed.
     */
    public function threadExists(int $thread_id): bool
    {
        $check = $this->conn->prepare(
            "SELECT id FROM threads WHERE id = :id AND is_removed = 0"
        );
        $check->execute([':id' => $thread_id]);
        return (bool)$check->fetch();
    }

    /**
     * Mod-remove a comment (sets is_removed = 1, removed_by_mod = 1).
     * The row is kept in the DB so a tombstone placeholder can be displayed.
     */
    public function removeCommentByMod(int $comment_id): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE thread_comments
             SET is_removed = 1, removed_by_mod = 1
             WHERE id = :id AND is_removed = 0"
        );
        return $stmt->execute([':id' => $comment_id]);
    }

    /**
     * Mod-remove a reply (sets is_removed = 1, removed_by_mod = 1).
     */
    public function removeReplyByMod(int $reply_id): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE comment_replies
             SET is_removed = 1, removed_by_mod = 1
             WHERE id = :id AND is_removed = 0"
        );
        return $stmt->execute([':id' => $reply_id]);
    }

    /**
     * User self-delete a comment (sets is_removed = 1, removed_by_user = 1).
     * Ownership is enforced: only the comment's own author_id matches.
     */
    public function removeCommentByUser(int $comment_id, int $author_id): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE thread_comments
             SET is_removed = 1, removed_by_user = 1
             WHERE id = :id AND author_id = :uid AND is_removed = 0"
        );
        $ok = $stmt->execute([':id' => $comment_id, ':uid' => $author_id]);
        return $ok && $stmt->rowCount() > 0;
    }

    /**
     * User self-delete a reply (sets is_removed = 1, removed_by_user = 1).
     * Ownership is enforced: only the reply's own author_id matches.
     */
    public function removeReplyByUser(int $reply_id, int $author_id): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE comment_replies
             SET is_removed = 1, removed_by_user = 1
             WHERE id = :id AND author_id = :uid AND is_removed = 0"
        );
        $ok = $stmt->execute([':id' => $reply_id, ':uid' => $author_id]);
        return $ok && $stmt->rowCount() > 0;
    }
}