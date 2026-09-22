<?php

class CommentModel
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getCommentsByThread(int $thread_id, int $user_id): array
    {
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
               AND (tc.is_removed = FALSE OR tc.removed_by_mod = TRUE OR tc.removed_by_user = TRUE)
             ORDER BY tc.created_at ASC"
        );
        $stmt->execute([':uid' => $user_id, ':tid' => $thread_id]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($comments as &$comment) {
            $comment['replies'] = $this->getRepliesByComment((int)$comment['id']);
        }
        unset($comment);

        return $comments;
    }

    public function getRepliesByComment(int $comment_id): array
    {

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
               AND (cr.is_removed = FALSE OR cr.removed_by_mod = TRUE OR cr.removed_by_user = TRUE)
             ORDER BY cr.created_at ASC"
        );
        $stmt->execute([':cid' => $comment_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createComment(int $thread_id, int $author_id, string $message, int $is_mod = 0): array|false
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO thread_comments (thread_id, author_id, message, is_mod_comment)
             VALUES (:tid, :uid, :msg, :is_mod)"
        );
        $stmt->bindValue(':tid', $thread_id, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $author_id, PDO::PARAM_INT);
        $stmt->bindValue(':msg', $message, PDO::PARAM_STR);
        $stmt->bindValue(':is_mod', (bool) $is_mod, PDO::PARAM_BOOL);
        $stmt->execute();
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

    public function createReply(int $comment_id, int $author_id, string $message, int $is_mod = 0): array|false
    {
        $check = $this->conn->prepare(
            "SELECT id FROM thread_comments WHERE id = :cid AND is_removed = FALSE LIMIT 1"
        );
        $check->execute([':cid' => $comment_id]);
        if (!$check->fetch()) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO comment_replies (comment_id, author_id, message, is_mod_comment)
             VALUES (:cid, :uid, :msg, :is_mod)"
        );
        $stmt->bindValue(':cid', $comment_id, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $author_id, PDO::PARAM_INT);
        $stmt->bindValue(':msg', $message, PDO::PARAM_STR);
        $stmt->bindValue(':is_mod', (bool) $is_mod, PDO::PARAM_BOOL);
        $stmt->execute();
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

    public function threadExists(int $thread_id): bool
    {
        $check = $this->conn->prepare(
            "SELECT id FROM threads WHERE id = :id AND is_removed = FALSE"
        );
        $check->execute([':id' => $thread_id]);
        return (bool)$check->fetch();
    }

    public function removeCommentByMod(int $comment_id): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE thread_comments
             SET is_removed = TRUE, removed_by_mod = TRUE
             WHERE id = :id AND is_removed = FALSE"
        );
        return $stmt->execute([':id' => $comment_id]);
    }

    public function removeReplyByMod(int $reply_id): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE comment_replies
             SET is_removed = TRUE, removed_by_mod = TRUE
             WHERE id = :id AND is_removed = FALSE"
        );
        return $stmt->execute([':id' => $reply_id]);
    }

    public function removeCommentByUser(int $comment_id, int $author_id): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE thread_comments
             SET is_removed = TRUE, removed_by_user = TRUE
             WHERE id = :id AND author_id = :uid AND is_removed = FALSE"
        );
        $ok = $stmt->execute([':id' => $comment_id, ':uid' => $author_id]);
        return $ok && $stmt->rowCount() > 0;
    }

    public function removeReplyByUser(int $reply_id, int $author_id): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE comment_replies
             SET is_removed = TRUE, removed_by_user = TRUE
             WHERE id = :id AND author_id = :uid AND is_removed = FALSE"
        );
        $ok = $stmt->execute([':id' => $reply_id, ':uid' => $author_id]);
        return $ok && $stmt->rowCount() > 0;
    }
}