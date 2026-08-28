<?php
// backend/models/CommentReportModel.php

class CommentReportModel
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Fetch all comment reports with full context:
     * - reporter info
     * - the reported comment/reply message + author info
     * - the parent thread subject
     * - the reported author's current sanction level
     *
     * @param string $status  'pending' | 'dismissed' | 'reviewed' | 'all'
     */
    public function getReports(string $status = 'pending'): array
    {
        $whereClause = ($status === 'all')
            ? ''
            : "WHERE cr.status = :status";

        $sql = "
            SELECT
                cr.id              AS report_id,
                cr.target_type,
                cr.target_id,
                cr.category,
                cr.note,
                cr.status,
                cr.created_at,

                -- Reporter
                CONCAT(rp.first_name, ' ', rp.last_name) AS reporter_name,

                -- Reported content (comment or reply message)
                COALESCE(tc.message, crep.message)        AS content_message,
                COALESCE(tc.created_at, crep.created_at)  AS content_created_at,

                -- Reported author (the person who wrote the comment/reply)
                COALESCE(au_c.id,     au_r.id)            AS author_id,
                COALESCE(
                    CONCAT(au_c.first_name, ' ', au_c.last_name),
                    CONCAT(au_r.first_name, ' ', au_r.last_name)
                )                                          AS author_name,
                COALESCE(au_c.email,  au_r.email)         AS author_email,

                -- Parent thread subject
                COALESCE(t_c.subject, t_r.subject)        AS thread_subject,
                COALESCE(tc.thread_id, crep_tc.thread_id) AS thread_id,

                -- Sanction level of the reported author
                COALESCE(
                    (SELECT MAX(us.level)
                     FROM user_sanctions us
                     WHERE us.user_id = COALESCE(au_c.id, au_r.id)
                       AND us.is_active = 1),
                    0
                )                                          AS author_sanction_level

            FROM comment_reports cr

            -- Reporter user
            LEFT JOIN users rp ON rp.id = cr.reporter_id

            -- If target_type = 'comment'
            LEFT JOIN thread_comments tc   ON cr.target_type = 'comment' AND tc.id  = cr.target_id
            LEFT JOIN users au_c           ON cr.target_type = 'comment' AND au_c.id = tc.author_id
            LEFT JOIN threads t_c          ON cr.target_type = 'comment' AND t_c.id  = tc.thread_id

            -- If target_type = 'reply'
            LEFT JOIN comment_replies crep ON cr.target_type = 'reply'   AND crep.id = cr.target_id
            LEFT JOIN users au_r           ON cr.target_type = 'reply'   AND au_r.id  = crep.author_id
            LEFT JOIN thread_comments crep_tc ON cr.target_type = 'reply' AND crep_tc.id = crep.comment_id
            LEFT JOIN threads t_r          ON cr.target_type = 'reply'   AND t_r.id   = crep_tc.thread_id

            {$whereClause}
            ORDER BY cr.created_at DESC
        ";

        $stmt = $this->conn->prepare($sql);
        if ($status !== 'all') {
            $stmt->bindValue(':status', $status);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update a single report's status.
     */
    public function updateStatus(int $report_id, string $status): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE comment_reports SET status = :status WHERE id = :id"
        );
        return $stmt->execute([':status' => $status, ':id' => $report_id]);
    }

    /**
     * Fetch a single report row (lightweight, no joins).
     */
    public function getById(int $report_id): array|false
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM comment_reports WHERE id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $report_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Count of pending reports.
     */
    public function countPending(): int
    {
        $stmt = $this->conn->query(
            "SELECT COUNT(*) FROM comment_reports WHERE status = 'pending'"
        );
        return (int)$stmt->fetchColumn();
    }
}