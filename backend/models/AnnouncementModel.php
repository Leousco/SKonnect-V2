<?php
require_once __DIR__ . '/../config/database.php';

class AnnouncementModel {

    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // CREATE

    public function create(array $data): int|false {
        $sql = "INSERT INTO announcements
                    (title, content, category, featured, featured_at, banner_img, author_id,
                     published_at, expired_at, status)
                VALUES
                    (:title, :content, :category, :featured, :featured_at, :banner_img, :author_id,
                     :published_at, :expired_at, :status)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':title'        => $data['title'],
            ':content'      => $data['content'],
            ':category'     => $data['category'],
            ':featured'     => $data['featured'] ? 1 : 0,
            ':featured_at'  => $data['featured'] ? date('Y-m-d H:i:s') : null,
            ':banner_img'   => $data['banner_img'] ?? null,
            ':author_id'    => $data['author_id'],
            ':published_at' => $data['published_at'] ?? date('Y-m-d H:i:s'),
            ':expired_at'   => $data['expired_at'] ?? null,
            ':status'       => $data['status'] ?? 'active',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    // SAVE ATTACHMENT FILE RECORD

    public function addFile(int $announcementId, string $filePath): void {
        $stmt = $this->conn->prepare(
            "INSERT INTO announcement_files (announcement_id, file_path) VALUES (:aid, :fp)"
        );
        $stmt->execute([':aid' => $announcementId, ':fp' => $filePath]);
    }

    // READ: List (officer panel - all statuses)

    public function getAll(array $filters = []): array {
        $where  = [];
        $params = [];

        if (!empty($filters['category'])) {
            $where[]              = 'a.category = :category';
            $params[':category']  = $filters['category'];
        }

        if (!empty($filters['status'])) {
            $where[]            = 'a.status = :status';
            $params[':status']  = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[]            = '(a.title LIKE :search OR a.content LIKE :search)';
            $params[':search']  = '%' . $filters['search'] . '%';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) AS author_name
                FROM announcements a
                JOIN users u ON u.id = a.author_id
                {$whereClause}
                ORDER BY a.published_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* READ: LIST (public / portal — active only) */

    public function getActive(array $filters = []): array {
        $where  = ["a.status = 'active'"];
        $params = [];

        // Auto-expire: exclude past expiry
        $where[] = "(a.expired_at IS NULL OR a.expired_at >= CURDATE())";

        if (!empty($filters['category'])) {
            $where[]             = 'a.category = :category';
            $params[':category'] = $filters['category'];
        }

        if (!empty($filters['search'])) {
            $where[]            = '(a.title LIKE :search OR a.content LIKE :search)';
            $params[':search']  = '%' . $filters['search'] . '%';
        }

        $sort = match($filters['sort'] ?? 'newest') {
            'oldest' => 'a.published_at ASC',
            default  => 'a.published_at DESC',
        };

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $sql = "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) AS author_name
                FROM announcements a
                JOIN users u ON u.id = a.author_id
                {$whereClause}
                ORDER BY {$sort}";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* READ: FEATURED (latest one) */

    public function getFeatured(): array|false {
        $stmt = $this->conn->prepare(
            "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) AS author_name
             FROM announcements a
             JOIN users u ON u.id = a.author_id
             WHERE a.featured = 1 AND a.status = 'active'
               AND (a.expired_at IS NULL OR a.expired_at >= CURDATE())
             ORDER BY a.featured_at DESC
             LIMIT 1"
        );
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* READ: SINGLE */

    public function getById(int $id): array|false {
        $stmt = $this->conn->prepare(
            "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) AS author_name
             FROM announcements a
             JOIN users u ON u.id = a.author_id
             WHERE a.id = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* READ: FILES FOR AN ANNOUNCEMENT */

    public function getFiles(int $announcementId): array {
        $stmt = $this->conn->prepare(
            "SELECT * FROM announcement_files WHERE announcement_id = :aid"
        );
        $stmt->execute([':aid' => $announcementId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* UPDATE */

    public function update(int $id, array $data): bool {
        $allowed = ['title','content','category','featured','banner_img',
                    'published_at','expired_at','status'];
        $sets   = [];
        $params = [':id' => $id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[]          = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        // Auto-set featured_at when featured status changes
        if (array_key_exists('featured', $data)) {
            $sets[]              = "featured_at = :featured_at";
            $params[':featured_at'] = $data['featured'] ? date('Y-m-d H:i:s') : null;
        }

        if (!$sets) return false;

        $stmt = $this->conn->prepare(
            "UPDATE announcements SET " . implode(', ', $sets) . " WHERE id = :id"
        );
        return $stmt->execute($params);
    }

    /* ARCHIVE (soft) */

    public function archive(int $id): bool {
        $stmt = $this->conn->prepare(
            "UPDATE announcements SET status = 'archived', archived_at = NOW() WHERE id = :id"
        );
        return $stmt->execute([':id' => $id]);
    }

    /* RESTORE to active/published */

    public function restore(int $id): bool {
        $stmt = $this->conn->prepare(
            "UPDATE announcements SET status = 'active', archived_at = NULL WHERE id = :id"
        );
        return $stmt->execute([':id' => $id]);
    }

    /* DELETE (hard) */

    public function delete(int $id): bool {
        $stmt = $this->conn->prepare(
            "DELETE FROM announcements WHERE id = :id"
        );
        return $stmt->execute([':id' => $id]);
    }

    public function deleteFileById(int $fileId): bool {
        $stmt = $this->conn->prepare(
            "DELETE FROM announcement_files WHERE id = :id"
        );
        return $stmt->execute([':id' => $fileId]);
    }

    public function deleteFiles(int $announcementId): array {
        // Returns file paths so controller can delete physical files
        $files = $this->getFiles($announcementId);
        $stmt  = $this->conn->prepare(
            "DELETE FROM announcement_files WHERE announcement_id = :aid"
        );
        $stmt->execute([':aid' => $announcementId]);
        return $files;
    }

    /* GET ALL RESIDENT EMAILS */

    public function getResidentEmails(): array {
        $stmt = $this->conn->prepare(
            "SELECT email, CONCAT(first_name, ' ', last_name) AS full_name
             FROM users
             WHERE role = 'resident'"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* STATS (for officer dashboard) */

    public function getStats(): array {
        $stmt = $this->conn->query(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'active')   AS published,
                SUM(status = 'draft')    AS drafts,
                SUM(status = 'archived') AS archived,
                SUM(featured = 1 AND status = 'active') AS featured,
                SUM(category = 'urgent' AND status = 'active') AS urgent
             FROM announcements"
        );
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* AUTO-ARCHIVE WHEN EXPIRED */

    public function archiveExpired(): int {
        $stmt = $this->conn->prepare(
            "UPDATE announcements
             SET status = 'archived', archived_at = NOW()
             WHERE status = 'active'
               AND expired_at IS NOT NULL
               AND expired_at < CURDATE()
               AND archived_at IS NULL"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }
}
?>