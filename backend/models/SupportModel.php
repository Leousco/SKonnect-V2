<?php
// backend/models/SupportModel.php

class SupportModel
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Toggle support on a thread.
     * Returns ['supported' => bool, 'total' => int]
     */
    public function toggleThread(int $thread_id, int $user_id): array
    {
        $check = $this->conn->prepare(
            "SELECT id FROM thread_supports WHERE thread_id = :tid AND user_id = :uid"
        );
        $check->execute([':tid' => $thread_id, ':uid' => $user_id]);

        if ($check->fetch()) {
            $this->conn->prepare(
                "DELETE FROM thread_supports WHERE thread_id = :tid AND user_id = :uid"
            )->execute([':tid' => $thread_id, ':uid' => $user_id]);
            $supported = false;
        } else {
            $this->conn->prepare(
                "INSERT INTO thread_supports (thread_id, user_id) VALUES (:tid, :uid)"
            )->execute([':tid' => $thread_id, ':uid' => $user_id]);
            $supported = true;
        }

        $count = $this->conn->prepare(
            "SELECT COUNT(*) FROM thread_supports WHERE thread_id = :tid"
        );
        $count->execute([':tid' => $thread_id]);

        return ['supported' => $supported, 'total' => (int)$count->fetchColumn()];
    }

    /**
     * Toggle support on a comment.
     * Returns ['supported' => bool, 'total' => int]
     */
    public function toggleComment(int $comment_id, int $user_id): array
    {
        $check = $this->conn->prepare(
            "SELECT id FROM comment_supports WHERE comment_id = :cid AND user_id = :uid"
        );
        $check->execute([':cid' => $comment_id, ':uid' => $user_id]);

        if ($check->fetch()) {
            $this->conn->prepare(
                "DELETE FROM comment_supports WHERE comment_id = :cid AND user_id = :uid"
            )->execute([':cid' => $comment_id, ':uid' => $user_id]);
            $supported = false;
        } else {
            $this->conn->prepare(
                "INSERT INTO comment_supports (comment_id, user_id) VALUES (:cid, :uid)"
            )->execute([':cid' => $comment_id, ':uid' => $user_id]);
            $supported = true;
        }

        $count = $this->conn->prepare(
            "SELECT COUNT(*) FROM comment_supports WHERE comment_id = :cid"
        );
        $count->execute([':cid' => $comment_id]);

        return ['supported' => $supported, 'total' => (int)$count->fetchColumn()];
    }
}