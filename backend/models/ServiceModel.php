<?php
// backend/models/ServiceModel.php

require_once __DIR__ . '/../config/Database.php';

class ServiceModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function getAll(array $filters = []): array
    {
        $sql    = "SELECT * FROM services WHERE 1=1";
        $params = [];

        if (!empty($filters['category'])) {
            $sql .= " AND category = :category";
            $params[':category'] = $filters['category'];
        }
        if (!empty($filters['service_type'])) {
            $sql .= " AND service_type = :service_type";
            $params[':service_type'] = $filters['service_type'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND name LIKE :search";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM services WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function insert(array $data, ?string $attachmentName, ?string $attachmentPath, int $officerId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO services
                (name, category, service_type, description, approval_message, eligibility,
                 processing_time, requirements, contact_info,
                 attachment_name, attachment_path,
                 max_capacity, status, created_by)
            VALUES
                (:name, :category, :service_type, :description, :approval_message, :eligibility,
                 :processing_time, :requirements, :contact_info,
                 :attachment_name, :attachment_path,
                 :max_capacity, :status, :created_by)
        ");

        $stmt->execute([
            ':name'             => trim($data['name']),
            ':category'         => $data['category'],
            ':service_type'     => $data['service_type'],
            ':description'      => trim($data['description']),
            ':approval_message' => trim($data['approval_message']),
            ':eligibility'      => isset($data['eligibility'])     ? trim($data['eligibility'])     : null,
            ':processing_time'  => isset($data['processing_time']) ? trim($data['processing_time']) : null,
            ':requirements'     => isset($data['requirements'])    ? trim($data['requirements'])    : null,
            ':contact_info'     => isset($data['contact_info'])    ? trim($data['contact_info'])    : null,
            ':attachment_name'  => $attachmentName,
            ':attachment_path'  => $attachmentPath,
            ':max_capacity'     => isset($data['max_capacity']) && (int)$data['max_capacity'] > 0
                                    ? (int)$data['max_capacity'] : null,
            ':status'           => $data['status'] ?? 'active',
            ':created_by'       => $officerId ?: null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data, ?string $attachmentName, ?string $attachmentPath): void
    {
        $stmt = $this->db->prepare("
            UPDATE services SET
                name             = :name,
                category         = :category,
                service_type     = :service_type,
                description      = :description,
                approval_message = :approval_message,
                eligibility      = :eligibility,
                processing_time  = :processing_time,
                requirements     = :requirements,
                contact_info     = :contact_info,
                attachment_name  = :attachment_name,
                attachment_path  = :attachment_path,
                max_capacity     = :max_capacity,
                status           = :status
            WHERE id = :id
        ");

        $stmt->execute([
            ':name'             => trim($data['name']),
            ':category'         => $data['category'],
            ':service_type'     => $data['service_type'],
            ':description'      => trim($data['description']),
            ':approval_message' => trim($data['approval_message']),
            ':eligibility'      => isset($data['eligibility'])     ? trim($data['eligibility'])     : null,
            ':processing_time'  => isset($data['processing_time']) ? trim($data['processing_time']) : null,
            ':requirements'     => isset($data['requirements'])    ? trim($data['requirements'])    : null,
            ':contact_info'     => isset($data['contact_info'])    ? trim($data['contact_info'])    : null,
            ':attachment_name'  => $attachmentName,
            ':attachment_path'  => $attachmentPath,
            ':max_capacity'     => isset($data['max_capacity']) && (int)$data['max_capacity'] > 0
                                    ? (int)$data['max_capacity'] : null,
            ':status'           => $data['status'] ?? 'active',
            ':id'               => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM services WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public function setStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare("UPDATE services SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $id]);
    }
}