<?php
// backend/models/ReportModel.php

class ReportModel
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    // ── THREAD REPORTS ────────────────────────────────────────────────────────

    /**
     * Returns true if this user has already reported this thread.
     */
    public function hasReportedThread(int $thread_id, int $reporter_id): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT id FROM thread_reports
             WHERE thread_id = :tid AND reporter_id = :rid
             LIMIT 1"
        );
        $stmt->execute([':tid' => $thread_id, ':rid' => $reporter_id]);
        return (bool)$stmt->fetch();
    }

    /**
     * Insert a thread report.
     */
    public function createThreadReport(
        int     $thread_id,
        int     $reporter_id,
        string  $category,
        ?string $note
    ): bool {
        $stmt = $this->conn->prepare(
            "INSERT INTO thread_reports (thread_id, reporter_id, category, note)
             VALUES (:tid, :rid, :cat, :note)"
        );
        return $stmt->execute([
            ':tid'  => $thread_id,
            ':rid'  => $reporter_id,
            ':cat'  => $category,
            ':note' => $note,
        ]);
    }

    // ── COMMENT / REPLY REPORTS ───────────────────────────────────────────────

    /**
     * Returns true if this user has already reported this comment/reply.
     */
    public function hasReportedComment(string $target_type, int $target_id, int $reporter_id): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT id FROM comment_reports
             WHERE target_type = :tt AND target_id = :tid AND reporter_id = :rid
             LIMIT 1"
        );
        $stmt->execute([':tt' => $target_type, ':tid' => $target_id, ':rid' => $reporter_id]);
        return (bool)$stmt->fetch();
    }

    /**
     * Insert a comment or reply report.
     */
    public function createCommentReport(
        string  $target_type,
        int     $target_id,
        int     $reporter_id,
        string  $category,
        ?string $note
    ): bool {
        $stmt = $this->conn->prepare(
            "INSERT INTO comment_reports (target_type, target_id, reporter_id, category, note)
             VALUES (:tt, :tid, :rid, :cat, :note)"
        );
        return $stmt->execute([
            ':tt'   => $target_type,
            ':tid'  => $target_id,
            ':rid'  => $reporter_id,
            ':cat'  => $category,
            ':note' => $note,
        ]);
    }

    // ── MODERATION QUEUE ──────────────────────────────────────────────────────

    /**
     * Fetch all thread reports joined with thread and reporter info.
     * Pending first, then newest within each status group.
     */
    public function getThreadReports(): array
    {
        $stmt = $this->conn->query(
            "SELECT
                tr.id            AS report_id,
                tr.thread_id,
                tr.category,
                tr.note,
                tr.status        AS report_status,
                tr.created_at    AS reported_at,
                t.subject        AS thread_subject,
                t.message        AS thread_message,
                t.is_removed     AS thread_is_removed,
                t.author_id      AS thread_author_id,
                CONCAT(ta.first_name, ' ', ta.last_name) AS thread_author_name,
                CONCAT(rp.first_name, ' ', rp.last_name) AS reporter_name
             FROM thread_reports tr
             JOIN threads t  ON t.id  = tr.thread_id
             JOIN users   ta ON ta.id = t.author_id
             JOIN users   rp ON rp.id = tr.reporter_id
             ORDER BY
                FIELD(tr.status, 'pending', 'reviewed', 'dismissed'),
                tr.created_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update the status of a single thread report.
     */
    public function updateThreadReportStatus(int $report_id, string $status): bool
    {
        $allowed = ['pending', 'reviewed', 'dismissed'];
        if (!in_array($status, $allowed)) return false;

        $stmt = $this->conn->prepare(
            "UPDATE thread_reports SET status = :status WHERE id = :id"
        );
        return $stmt->execute([':status' => $status, ':id' => $report_id]);
    }

    /**
     * Stat counts for the widget row.
     */
    public function getThreadReportCounts(): array
    {
        $stmt = $this->conn->query(
            "SELECT
                COUNT(*)                          AS total,
                SUM(status = 'pending')           AS pending,
                SUM(status = 'reviewed')          AS reviewed,
                SUM(status = 'dismissed')         AS dismissed,
                SUM(category = 'harassment')      AS harassment,
                SUM(category = 'spam')            AS spam,
                SUM(category = 'inappropriate')   AS inappropriate,
                SUM(category = 'misinformation')  AS misinformation
             FROM thread_reports"
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return array_map('intval', $row ?: [
            'total' => 0, 'pending' => 0, 'reviewed' => 0, 'dismissed' => 0,
            'harassment' => 0, 'spam' => 0, 'inappropriate' => 0, 'misinformation' => 0,
        ]);
    }

    // ── NOTIFICATIONS ─────────────────────────────────────────────────────────

    /**
     * Insert an in-app notification for a user.
     */
    public function createNotification(
        int     $user_id,
        string  $type,
        string  $title,
        string  $message,
        ?string $ref_type = null,
        ?int    $ref_id   = null
    ): bool {
        $stmt = $this->conn->prepare(
            "INSERT INTO notifications (user_id, type, title, message, ref_type, ref_id)
             VALUES (:uid, :type, :title, :msg, :rtype, :rid)"
        );
        return $stmt->execute([
            ':uid'   => $user_id,
            ':type'  => $type,
            ':title' => $title,
            ':msg'   => $message,
            ':rtype' => $ref_type,
            ':rid'   => $ref_id,
        ]);
    }
}