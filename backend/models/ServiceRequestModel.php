<?php
// backend/models/ServiceRequestModel.php

require_once __DIR__ . '/../config/Database.php';

class ServiceRequestModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // ──────────────────────────────────────────────────────────
    //  READ
    // ──────────────────────────────────────────────────────────

    /**
     * Get all applications with joined resident + service data.
     * Supports filtering by status, category, search term.
     */
    public function getAll(array $filters = []): array
    {
        $sql = "
            SELECT
                sa.id,
                sa.service_id,
                sa.resident_id,
                sa.full_name,
                sa.contact,
                sa.email,
                sa.address,
                sa.purpose,
                sa.status,
                sa.submitted_at,
                sa.updated_at,
                -- Resident info from users table
                u.first_name,
                u.last_name,
                u.middle_name,
                u.email      AS resident_email,
                -- Service info
                sv.name      AS service_name,
                sv.category  AS service_category,
                sv.service_type,
                -- Document count sub-query
                (SELECT COUNT(*) FROM application_documents ad WHERE ad.application_id = sa.id) AS doc_count,
                -- Latest note (for list preview)
                (SELECT an.note FROM application_notes an WHERE an.application_id = sa.id ORDER BY an.created_at DESC LIMIT 1) AS latest_note
            FROM service_applications sa
            INNER JOIN users    u  ON u.id  = sa.resident_id
            INNER JOIN services sv ON sv.id = sa.service_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND sa.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['category'])) {
            $sql .= " AND sv.category = :category";
            $params[':category'] = $filters['category'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (sa.full_name LIKE :search OR sv.name LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY sa.submitted_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a single application with all documents and notes thread.
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                sa.*,
                u.first_name,
                u.last_name,
                u.middle_name,
                u.email      AS resident_email,
                sv.name      AS service_name,
                sv.category  AS service_category,
                sv.service_type,
                sv.eligibility,
                sv.processing_time,
                sv.requirements
            FROM service_applications sa
            INNER JOIN users    u  ON u.id  = sa.resident_id
            INNER JOIN services sv ON sv.id = sa.service_id
            WHERE sa.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        // Attach documents
        $row['documents'] = $this->getDocuments($id);
        // Attach notes thread
        $row['notes'] = $this->getNotes($id);
        return $row;
    }

    public function getDocuments(int $applicationId): array
    {
        $stmt = $this->db->prepare("
            SELECT id, file_name, file_path, file_size, mime_type, uploaded_at
            FROM application_documents
            WHERE application_id = :aid
            ORDER BY uploaded_at ASC
        ");
        $stmt->execute([':aid' => $applicationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get the officer notes thread for an application, newest first.
     */
    public function getNotes(int $applicationId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                an.id,
                an.application_id,
                an.officer_id,
                an.note,
                an.created_at,
                COALESCE(CONCAT(u.first_name, ' ', u.last_name), 'SK Officer') AS officer_name
            FROM application_notes an
            LEFT JOIN users u ON u.id = an.officer_id
            WHERE an.application_id = :aid
            ORDER BY an.created_at ASC
        ");
        $stmt->execute([':aid' => $applicationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function residentHasActiveApplication(int $residentId, int $serviceId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM service_applications
            WHERE resident_id = :rid
              AND service_id  = :sid
              AND status NOT IN ('rejected', 'cancelled')
        ");
        $stmt->execute([':rid' => $residentId, ':sid' => $serviceId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function getServiceCapacity(int $serviceId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT max_capacity, current_count, status
            FROM services
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $serviceId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function isServiceAvailable(int $serviceId): bool
    {
        $capacity = $this->getServiceCapacity($serviceId);
        if (!$capacity) return false;
        if ($capacity['status'] !== 'active') return false;
        if ($capacity['max_capacity'] === null) return true;
        return (int)$capacity['current_count'] < (int)$capacity['max_capacity'];
    }

    // ──────────────────────────────────────────────────────────
    //  WRITE
    // ──────────────────────────────────────────────────────────

    public function insert(array $data, int $residentId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO service_applications
                (service_id, resident_id, full_name, contact, email, address, purpose, status)
            VALUES
                (:service_id, :resident_id, :full_name, :contact, :email, :address, :purpose, 'pending')
        ");
        $stmt->execute([
            ':service_id'  => (int)$data['service_id'],
            ':resident_id' => $residentId,
            ':full_name'   => trim($data['full_name']),
            ':contact'     => trim($data['contact']),
            ':email'       => trim($data['email']),
            ':address'     => trim($data['address']),
            ':purpose'     => isset($data['purpose']) ? trim($data['purpose']) : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update the editable fields of an existing application (used for reapply).
     * Does NOT touch status — that is handled separately via updateStatus().
     */
    public function updateApplication(int $id, array $data): void
    {
        $stmt = $this->db->prepare("
            UPDATE service_applications
            SET full_name = :full_name,
                contact   = :contact,
                email     = :email,
                address   = :address,
                purpose   = :purpose
            WHERE id = :id
        ");
        $stmt->execute([
            ':full_name' => $data['full_name'],
            ':contact'   => $data['contact'],
            ':email'     => $data['email'],
            ':address'   => $data['address'],
            ':purpose'   => $data['purpose'] ?? null,
            ':id'        => $id,
        ]);
    }

    public function insertDocument(int $applicationId, string $fileName, string $filePath, int $fileSize, string $mimeType): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO application_documents (application_id, file_name, file_path, file_size, mime_type)
            VALUES (:app_id, :file_name, :file_path, :file_size, :mime_type)
        ");
        $stmt->execute([
            ':app_id'    => $applicationId,
            ':file_name' => $fileName,
            ':file_path' => $filePath,
            ':file_size' => $fileSize,
            ':mime_type' => $mimeType,
        ]);
    }

    /**
     * Delete a specific document belonging to an application.
     * The application_id guard prevents a resident from deleting docs on other applications.
     */
    public function deleteDocument(int $documentId, int $applicationId): void
    {
        $stmt = $this->db->prepare("
            DELETE FROM application_documents
            WHERE id = :doc_id AND application_id = :app_id
        ");
        $stmt->execute([
            ':doc_id' => $documentId,
            ':app_id' => $applicationId,
        ]);
    }

    /**
     * Insert a new officer note into the thread.
     * If $setActionRequired is true, moves status to 'action_required' when still pending.
     * When called from approval/rejection flows, pass false so the finalized status is preserved.
     */
    public function insertNote(int $applicationId, int $officerId, string $note, bool $setActionRequired = true): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO application_notes (application_id, officer_id, note)
            VALUES (:app_id, :officer_id, :note)
        ");
        $stmt->execute([
            ':app_id'     => $applicationId,
            ':officer_id' => $officerId,
            ':note'       => $note,
        ]);

        // Only move to action_required if still pending AND the caller wants this side-effect.
        // Approval/rejection thread messages must NOT touch the finalized status.
        if ($setActionRequired) {
            $this->db->prepare("
                UPDATE service_applications
                SET status = 'action_required'
                WHERE id = :id AND status = 'pending'
            ")->execute([':id' => $applicationId]);
        }
    }

    public function updateStatus(int $id, string $status, ?string $fulfillmentFile = null): void
    {
        if ($fulfillmentFile !== null) {
            $stmt = $this->db->prepare("
                UPDATE service_applications
                SET status = :status, fulfillment_file = :file
                WHERE id = :id
            ");
            $stmt->execute([':status' => $status, ':file' => $fulfillmentFile, ':id' => $id]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE service_applications SET status = :status WHERE id = :id
            ");
            $stmt->execute([':status' => $status, ':id' => $id]);
        }
    }

    public function cancelApplication(int $applicationId, int $residentId): bool
    {
        $stmt = $this->db->prepare("
            SELECT sa.status, sv.max_capacity
            FROM service_applications sa
            INNER JOIN services sv ON sv.id = sa.service_id
            WHERE sa.id = :id AND sa.resident_id = :rid
            LIMIT 1
        ");
        $stmt->execute([':id' => $applicationId, ':rid' => $residentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !in_array($row['status'], ['pending', 'action_required'], true)) {
            return false;
        }

        $this->db->prepare("
            UPDATE service_applications SET status = 'cancelled' WHERE id = :id
        ")->execute([':id' => $applicationId]);

        if ($row['max_capacity'] !== null) {
            $this->db->prepare("
                UPDATE services
                SET current_count = GREATEST(0, current_count - 1)
                WHERE id = (SELECT service_id FROM service_applications WHERE id = :id)
            ")->execute([':id' => $applicationId]);
        }

        return true;
    }

    public function getApprovalMessage(int $serviceId): ?string
    {
        $stmt = $this->db->prepare("
            SELECT approval_message FROM services WHERE id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $serviceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['approval_message'] : null;
    }

    // ──────────────────────────────────────────────────────────
    //  STATS (for officer dashboard widgets)
    // ──────────────────────────────────────────────────────────

    public function getStatusCounts(): array
    {
        $stmt = $this->db->query("
            SELECT status, COUNT(*) AS cnt
            FROM service_applications
            GROUP BY status
        ");
        $rows   = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $counts = ['pending' => 0, 'action_required' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0];
        foreach ($rows as $row) {
            if (array_key_exists($row['status'], $counts)) {
                $counts[$row['status']] = (int)$row['cnt'];
            }
        }
        return $counts;
    }

    /**
     * Get all applications submitted by a specific resident, newest first.
     * Includes notes and documents for each application.
     */
    public function getByResident(int $residentId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                sa.id,
                sa.service_id,
                sa.full_name,
                sa.contact,
                sa.email,
                sa.address,
                sa.purpose,
                sa.status,
                sa.submitted_at,
                sa.updated_at,
                sa.fulfillment_file,
                sv.name      AS service_name,
                sv.category  AS service_category,
                sv.service_type,
                (SELECT COUNT(*) FROM application_documents ad WHERE ad.application_id = sa.id) AS doc_count,
                (SELECT COUNT(*) FROM application_notes an WHERE an.application_id = sa.id) AS note_count
            FROM service_applications sa
            INNER JOIN services sv ON sv.id = sa.service_id
            WHERE sa.resident_id = :rid
            ORDER BY sa.submitted_at DESC
        ");
        $stmt->execute([':rid' => $residentId]);
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Attach notes and documents to each application
        foreach ($applications as &$app) {
            $app['notes']     = $this->getNotes($app['id']);
            $app['documents'] = $this->getDocuments($app['id']);
        }

        return $applications;
    }
}