<?php
require_once __DIR__ . '/../config/database.php';

class AnnouncementModel {

    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    

    public function create(array $data): int|false {
        $sql = "INSERT INTO announcements
                    (title, content, category, featured, featured_at, banner_img, author_id,
                     published_at, expired_at, status)
                VALUES
                    (:title, :content, :category, :featured, :featured_at, :banner_img, :author_id,
                     :published_at, :expired_at, :status)";

        $stmt = $this->conn->prepare($sql);
        try {
            $stmt->execute([
                ':title'        => $data['title'],
                ':content'      => $data['content'],
                ':category'     => $data['category'],
                ':featured'     => !empty($data['featured']) ? 1 : 0,
                ':featured_at'  => $data['featured'] ? date('Y-m-d H:i:s') : null,
                ':banner_img'   => $data['banner_img'] ?? null,
                ':author_id'    => $data['author_id'],
                ':published_at' => $data['published_at'] ?? date('Y-m-d H:i:s'),
                ':expired_at'   => $data['expired_at'] ?? null,
                ':status'       => $data['status'] ?? 'active',
            ]);
        } catch (PDOException $e) {
            error_log('AnnouncementModel::create failed: ' . $e->getMessage());
            return false;
        }

        return (int) $this->conn->lastInsertId();
    }

    

    public function addFile(int $announcementId, string $filePath): void {
        $stmt = $this->conn->prepare(
            "INSERT INTO announcement_files (announcement_id, file_path) VALUES (:aid, :fp)"
        );
        $stmt->execute([':aid' => $announcementId, ':fp' => $filePath]);
    }

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
            $where[]            = '(a.title ILIKE :search OR a.content ILIKE :search)';
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

    public function getActive(array $filters = []): array {
        $where  = ["a.status = 'active'"];
        $params = [];

        $where[] = "(a.expired_at IS NULL OR a.expired_at >= CURRENT_DATE)";

        if (!empty($filters['category'])) {
            $where[]             = 'a.category = :category';
            $params[':category'] = $filters['category'];
        }

        if (!empty($filters['search'])) {
            $where[]            = '(a.title ILIKE :search OR a.content ILIKE :search)';
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

    public function getFeatured(): array|false {
        $stmt = $this->conn->prepare(
            "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) AS author_name
             FROM announcements a
             JOIN users u ON u.id = a.author_id
             WHERE a.featured = TRUE AND a.status = 'active'
               AND (a.expired_at IS NULL OR a.expired_at >= CURRENT_DATE)
             ORDER BY a.featured_at DESC
             LIMIT 1"
        );
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

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

    public function getFiles(int $announcementId): array {
        $stmt = $this->conn->prepare(
            "SELECT * FROM announcement_files WHERE announcement_id = :aid"
        );
        $stmt->execute([':aid' => $announcementId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(int $id, array $data): bool {
        $allowed = ['title','content','category','featured','banner_img',
                    'published_at','expired_at','status'];
        $sets   = [];
        $params = [':id' => $id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[]          = "{$field} = :{$field}";
                $params[":{$field}"] = $field === 'featured' ? (!empty($data[$field]) ? 1 : 0) : $data[$field];
            }
        }

        if (array_key_exists('featured', $data)) {
            $sets[]              = "featured_at = :featured_at";
            $params[':featured_at'] = $data['featured'] ? date('Y-m-d H:i:s') : null;
        }

        if (!$sets) return false;

        $stmt = $this->conn->prepare(
            "UPDATE announcements SET " . implode(', ', $sets) . " WHERE id = :id"
        );

        try {
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('AnnouncementModel::update failed: ' . $e->getMessage());
            return false;
        }
    }

    public function archive(int $id): bool {
        $stmt = $this->conn->prepare(
            "UPDATE announcements SET status = 'archived', archived_at = NOW() WHERE id = :id"
        );
        return $stmt->execute([':id' => $id]);
    }

    public function restore(int $id): bool {
        $stmt = $this->conn->prepare(
            "UPDATE announcements SET status = 'active', archived_at = NULL WHERE id = :id"
        );
        return $stmt->execute([':id' => $id]);
    }

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
        $files = $this->getFiles($announcementId);
        $stmt  = $this->conn->prepare(
            "DELETE FROM announcement_files WHERE announcement_id = :aid"
        );
        $stmt->execute([':aid' => $announcementId]);
        return $files;
    }

    public function getResidentEmails(): array {
        $stmt = $this->conn->prepare(
            "SELECT email, CONCAT(first_name, ' ', last_name) AS full_name
             FROM users
             WHERE role = 'resident'"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats(): array {
        $stmt = $this->conn->query(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'active'   THEN 1 ELSE 0 END) AS published,
                SUM(CASE WHEN status = 'draft'    THEN 1 ELSE 0 END) AS drafts,
                SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) AS archived,
                SUM(CASE WHEN featured = TRUE AND status = 'active' THEN 1 ELSE 0 END) AS featured,
                SUM(CASE WHEN category = 'urgent' AND status = 'active' THEN 1 ELSE 0 END) AS urgent
             FROM announcements"
        );
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function archiveExpired(): int {
        $stmt = $this->conn->prepare(
            "UPDATE announcements
             SET status = 'archived', archived_at = NOW()
             WHERE status = 'active'
               AND expired_at IS NOT NULL
               AND expired_at < CURRENT_DATE
               AND archived_at IS NULL"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }
}
?>