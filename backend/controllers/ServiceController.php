<?php
// backend/controllers/ServiceController.php

require_once __DIR__ . '/../models/ServiceModel.php';

class ServiceController
{
    private ServiceModel $model;

    private array $allowedMimes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];
    private array $allowedExts      = ['pdf', 'doc', 'docx', 'xlsx'];
    private int   $maxFileSizeBytes = 10 * 1024 * 1024;
    private string $uploadDir;

    public function __construct()
    {
        $this->model     = new ServiceModel();
        $this->uploadDir = __DIR__ . '/../../uploads/forms/';

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function getAll(array $filters = []): array
    {
        return $this->model->getAll($filters);
    }

    public function getById(int $id): ?array
    {
        return $this->model->getById($id);
    }

    public function create(array $data, ?array $files, int $officerId = 0): array
    {
        $validation = $this->validate($data);
        if (!$validation['ok']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }

        $attachmentNames = [];
        $attachmentPaths = [];

        $uploadedFiles = $this->normaliseFilesArray($files);
        foreach ($uploadedFiles as $file) {
            if ($file['error'] !== UPLOAD_ERR_OK) continue;
            $upload = $this->handleUpload($file);
            if (!$upload['ok']) {
                return ['success' => false, 'errors' => [$upload['error']]];
            }
            $attachmentNames[] = $upload['name'];
            $attachmentPaths[] = $upload['path'];
        }

        $attachmentName = $attachmentNames ? implode(',', $attachmentNames) : null;
        $attachmentPath = $attachmentPaths ? implode(',', $attachmentPaths) : null;

        $newId = $this->model->insert($data, $attachmentName, $attachmentPath, $officerId);
        // In-system notification broadcast
        require_once __DIR__ . '/../services/NotificationService.php';
        NotificationService::notifyNewService($newId, $data['name'], $data['category']);
        return ['success' => true, 'id' => $newId, 'service' => $this->model->getById($newId)];
    }

    public function update(int $id, array $data, ?array $files): array
    {
        $existing = $this->model->getById($id);
        if (!$existing) {
            return ['success' => false, 'errors' => ['Service not found.']];
        }

        $validation = $this->validate($data);
        if (!$validation['ok']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }

        // Start from kept existing files (sent from frontend as comma-separated names)
        $keptNames = [];
        $keptPaths = [];
        if (!empty($data['existing_attachments'])) {
            $existingNames = array_filter(array_map('trim', explode(',', $existing['attachment_name'] ?? '')));
            $existingPaths = array_filter(array_map('trim', explode(',', $existing['attachment_path'] ?? '')));
            $keptNamesFromClient = array_filter(array_map('trim', explode(',', $data['existing_attachments'])));

            foreach ($existingNames as $i => $name) {
                if (in_array($name, $keptNamesFromClient, true)) {
                    $keptNames[] = $name;
                    $keptPaths[] = $existingPaths[$i] ?? '';
                } else {
                    // File was removed by user — delete from disk
                    $this->deleteFile($existingPaths[$i] ?? null);
                }
            }
        } elseif (isset($data['clear_attachment']) && $data['clear_attachment'] === '1') {
            // All attachments cleared
            foreach (array_filter(array_map('trim', explode(',', $existing['attachment_path'] ?? ''))) as $p) {
                $this->deleteFile($p);
            }
        } else {
            // No existing_attachments sent and no clear flag — keep all existing
            $keptNames = array_filter(array_map('trim', explode(',', $existing['attachment_name'] ?? '')));
            $keptPaths = array_filter(array_map('trim', explode(',', $existing['attachment_path'] ?? '')));
        }

        // Upload any new files
        $uploadedFiles = $this->normaliseFilesArray($files);
        foreach ($uploadedFiles as $file) {
            if ($file['error'] !== UPLOAD_ERR_OK) continue;
            $upload = $this->handleUpload($file);
            if (!$upload['ok']) {
                return ['success' => false, 'errors' => [$upload['error']]];
            }
            $keptNames[] = $upload['name'];
            $keptPaths[] = $upload['path'];
        }

        $attachmentName = $keptNames ? implode(',', array_values($keptNames)) : null;
        $attachmentPath = $keptPaths ? implode(',', array_values($keptPaths)) : null;

        $this->model->update($id, $data, $attachmentName, $attachmentPath);
        return ['success' => true, 'service' => $this->model->getById($id)];
    }

    /**
     * Normalise $_FILES['attachments'] (which may be a multi-upload array structure)
     * into a plain array of individual file arrays.
     */
    private function normaliseFilesArray(?array $files): array
    {
        if (!$files || !isset($files['name'])) return [];

        // Multi-file upload: $files['name'] is an array
        if (is_array($files['name'])) {
            $out = [];
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                $out[] = [
                    'name'     => $files['name'][$i],
                    'type'     => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $files['error'][$i],
                    'size'     => $files['size'][$i],
                ];
            }
            return $out;
        }

        // Single file
        return [$files];
    }

    public function delete(int $id): array
    {
        $existing = $this->model->getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Service not found.'];
        }

        $this->deleteFile($existing['attachment_path']);
        $this->model->delete($id);
        return ['success' => true, 'message' => "Service \"{$existing['name']}\" deleted."];
    }

    public function toggleStatus(int $id): array
    {
        $existing = $this->model->getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Service not found.'];
        }

        $newStatus = $existing['status'] === 'active' ? 'inactive' : 'active';
        $this->model->setStatus($id, $newStatus);
        return ['success' => true, 'status' => $newStatus];
    }

    private function validate(array $data): array
    {
        $errors = [];
        $validCategories   = ['medical', 'education', 'scholarship', 'livelihood', 'assistance', 'legal', 'other'];
        $validServiceTypes = ['document', 'appointment', 'info'];
        $validStatuses     = ['active', 'inactive'];

        if (empty(trim($data['name'] ?? ''))) {
            $errors[] = 'Service name is required.';
        } elseif (strlen($data['name']) > 100) {
            $errors[] = 'Service name must be 100 characters or fewer.';
        }

        if (empty($data['category']) || !in_array($data['category'], $validCategories)) {
            $errors[] = 'A valid category is required.';
        }

        if (empty($data['service_type']) || !in_array($data['service_type'], $validServiceTypes)) {
            $errors[] = 'A valid service type is required.';
        }

        if (empty(trim($data['description'] ?? ''))) {
            $errors[] = 'Description is required.';
        }

        if (empty(trim($data['approval_message'] ?? '')) && ($data['service_type'] ?? '') !== 'info') {
            $errors[] = 'Approval message is required.';
        }

        if (($data['service_type'] ?? '') === 'info' && empty(trim($data['contact_info'] ?? ''))) {
            $errors[] = 'Contact information is required for Info / Walk-in services.';
        }

        if (!empty($data['max_capacity']) && (int)$data['max_capacity'] < 1) {
            $errors[] = 'Maximum capacity must be at least 1.';
        }

        if (!empty($data['status']) && !in_array($data['status'], $validStatuses)) {
            $errors[] = 'Invalid status value.';
        }

        return ['ok' => empty($errors), 'errors' => $errors];
    }

    private function handleUpload(array $file): array
    {
        if ($file['size'] > $this->maxFileSizeBytes) {
            return ['ok' => false, 'error' => 'File is too large. Maximum allowed size is 10MB.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedExts)) {
            return ['ok' => false, 'error' => 'Only PDF, DOC, DOCX, and XLSX files are allowed.'];
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedMimes)) {
            return ['ok' => false, 'error' => 'Invalid file type detected.'];
        }

        $safeName    = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($file['name']));
        $uniqueName  = uniqid('form_', true) . '_' . $safeName;
        $destination = $this->uploadDir . $uniqueName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['ok' => false, 'error' => 'Failed to save uploaded file.'];
        }

        return [
            'ok'   => true,
            'name' => $file['name'],
            'path' => '/uploads/forms/' . $uniqueName,
        ];
    }

    private function deleteFile(?string $path): void
    {
        if (!$path) return;
        $full = __DIR__ . '/../../' . ltrim($path, '/');
        if (file_exists($full)) @unlink($full);
    }
}
