<?php

/*
  AnnouncementController
  All public methods return JSON. Called by:
    backend/routes/announcements.php  →  ?action=<method>
*/

require_once __DIR__ . '/../models/AnnouncementModel.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../services/EmailService.php';

class AnnouncementController
{

    private AnnouncementModel $model;

    // Where uploaded banners are stored (relative to project root)
    private string $bannerDir  = 'assets/uploads/banners/';
    // Where attachments are stored
    private string $attachDir  = 'assets/uploads/attachments/';

    public function __construct()
    {
        $this->model = new AnnouncementModel();
    }

    // SK Officer Actions
    /*
        POST — create a new announcement (publish or draft)
        Body fields: title, content, category, featured, publish_date, expiry_date, status (active | draft)
        Files: banner (optional), attachments[] (optional, multiple)
    */

    public function create(): void
    {
        RoleMiddleware::requireRole('sk_officer');
        $this->requireMethod('POST');

        $title    = trim($_POST['title']    ?? '');
        $content  = trim($_POST['content']  ?? '');
        $category = trim($_POST['category'] ?? '');
        $featured = isset($_POST['featured']) && $_POST['featured'] === '1';
        $status   = in_array($_POST['status'] ?? '', ['active', 'draft', 'archived']) ? $_POST['status'] : 'active';

        if (!$title || !$content || !$category) {
            $this->json(['status' => 'error', 'message' => 'Title, content and category are required.'], 422);
        }

        $validCategories = ['event', 'program', 'meeting', 'notice', 'urgent'];
        if (!in_array($category, $validCategories, true)) {
            $this->json(['status' => 'error', 'message' => 'Invalid category.'], 422);
        }

        // Published / expiry dates
        $publishedAt = !empty($_POST['publish_date'])
            ? date('Y-m-d H:i:s', strtotime($_POST['publish_date']))
            : date('Y-m-d H:i:s');

        $expiredAt = !empty($_POST['expiry_date'])
            ? date('Y-m-d', strtotime($_POST['expiry_date']))
            : null;

        // Banner upload
        $bannerPath = null;
        if (!empty($_FILES['banner']['tmp_name'])) {
            $bannerPath = $this->uploadFile($_FILES['banner'], $this->bannerDir, ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
            if (!$bannerPath) {
                $this->json(['status' => 'error', 'message' => 'Invalid banner image.'], 422);
            }
        }

        // Create the announcement record
        $id = $this->model->create([
            'title'        => $title,
            'content'      => $content,
            'category'     => $category,
            'featured'     => $featured,
            'banner_img'   => $bannerPath,
            'author_id'    => $_SESSION['user_id'],
            'published_at' => $publishedAt,
            'expired_at'   => $expiredAt,
            'status'       => $status,
        ]);

        if (!$id) {
            $this->json(['status' => 'error', 'message' => 'Failed to create announcement.'], 500);
        }

        // Attachments (multiple)
        if (!empty($_FILES['attachments']['tmp_name'])) {
            $files = $this->normaliseFileArray($_FILES['attachments']);
            foreach ($files as $file) {
                $path = $this->uploadFile($file, $this->attachDir, null, 10 * 1024 * 1024);
                if ($path) {
                    $this->model->addFile($id, $path);
                }
            }
        }

        // Send email notifications to all residents if published (not draft)
        if ($status === 'active') {
            $announcement = $this->model->getById($id);
            // In-system notification broadcast
            require_once __DIR__ . '/../services/NotificationService.php';
            $snippet = mb_strimwidth(strip_tags($content), 0, 120, '…');
            NotificationService::notifyNewAnnouncement($id, $title, $snippet);
            
            $residents    = $this->model->getResidentEmails();
            $emailService = new EmailService();
            foreach ($residents as $resident) {
                $emailService->sendAnnouncementNotification(
                    $resident['email'],
                    $resident['full_name'],
                    $announcement
                );
            }
        }

        $this->json([
            'status'  => 'success',
            'message' => $status === 'active' ? 'Announcement published.' : 'Draft saved.',
            'id'      => $id,
        ]);
    }

    /*
      POST  — update an existing announcement
      Body: id (required) + same fields as create (all optional)
    */
    public function update(): void
    {
        RoleMiddleware::requireRole('sk_officer');
        $this->requireMethod('POST');

        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) $this->json(['status' => 'error', 'message' => 'Missing announcement ID.'], 422);

        $existing = $this->model->getById($id);
        if (!$existing) $this->json(['status' => 'error', 'message' => 'Announcement not found.'], 404);

        $data = [];

        if (isset($_POST['title']))    $data['title']   = trim($_POST['title']);
        if (isset($_POST['content']))  $data['content']  = trim($_POST['content']);
        if (isset($_POST['category'])) $data['category'] = trim($_POST['category']);
        if (isset($_POST['featured'])) $data['featured'] = $_POST['featured'] === '1' ? 1 : 0;
        if (isset($_POST['status']))   $data['status']   = in_array($_POST['status'], ['active', 'draft', 'archived']) ? $_POST['status'] : $existing['status'];

        if (isset($_POST['publish_date']) && $_POST['publish_date'] !== '') {
            $data['published_at'] = date('Y-m-d H:i:s', strtotime($_POST['publish_date']));
        }
        if (isset($_POST['expiry_date'])) {
            $data['expired_at'] = $_POST['expiry_date'] !== '' ? date('Y-m-d', strtotime($_POST['expiry_date'])) : null;
        }

        // Replace banner if a new one was uploaded
        if (!empty($_FILES['banner']['tmp_name'])) {
            $bannerPath = $this->uploadFile($_FILES['banner'], $this->bannerDir, ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
            if ($bannerPath) {
                // Remove old banner file
                if ($existing['banner_img'] && file_exists($existing['banner_img'])) {
                    @unlink($existing['banner_img']);
                }
                $data['banner_img'] = $bannerPath;
            }
        }

        $this->model->update($id, $data);

        // Append new attachment files if sent
        if (!empty($_FILES['attachments']['tmp_name'])) {
            $files = $this->normaliseFileArray($_FILES['attachments']);
            foreach ($files as $file) {
                $path = $this->uploadFile($file, $this->attachDir, null, 10 * 1024 * 1024);
                if ($path) {
                    $this->model->addFile($id, $path);
                }
            }
        }

        $this->json(['status' => 'success', 'message' => 'Announcement updated.']);
    }

    /*
      POST  — archive one announcement (soft delete)
      Body: id
    */
    public function archive(): void
    {
        RoleMiddleware::requireRole('sk_officer');
        $this->requireMethod('POST');

        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) $this->json(['status' => 'error', 'message' => 'Missing ID.'], 422);

        $this->model->archive($id)
            ? $this->json(['status' => 'success', 'message' => 'Announcement archived.'])
            : $this->json(['status' => 'error',   'message' => 'Archive failed.'], 500);
    }

    /*
      POST  — restore an archived announcement back to active
      Body: id
    */
    public function restore(): void
    {
        RoleMiddleware::requireRole('sk_officer');
        $this->requireMethod('POST');

        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) $this->json(['status' => 'error', 'message' => 'Missing ID.'], 422);

        $this->model->restore($id)
            ? $this->json(['status' => 'success', 'message' => 'Announcement restored.'])
            : $this->json(['status' => 'error',   'message' => 'Restore failed.'], 500);
    }

    /*
      POST  — remove a single attachment file from an announcement
      Body: file_id, announcement_id
    */
    public function removeFile(): void
    {
        RoleMiddleware::requireRole('sk_officer');
        $this->requireMethod('POST');

        $fileId         = (int) ($_POST['file_id']         ?? 0);
        $announcementId = (int) ($_POST['announcement_id'] ?? 0);
        if (!$fileId || !$announcementId) {
            $this->json(['status' => 'error', 'message' => 'Missing file_id or announcement_id.'], 422);
        }

        // Verify the file belongs to this announcement
        $files = $this->model->getFiles($announcementId);
        $target = null;
        foreach ($files as $f) {
            if ((int) $f['id'] === $fileId) {
                $target = $f;
                break;
            }
        }
        if (!$target) {
            $this->json(['status' => 'error', 'message' => 'File not found.'], 404);
        }

        // Delete physical file
        $absPath = str_replace('\\', '/', dirname(__DIR__, 2)) . $target['file_path'];
        if (file_exists($absPath)) @unlink($absPath);

        // Delete DB record
        $this->model->deleteFileById($fileId);

        $this->json(['status' => 'success', 'message' => 'Attachment removed.']);
    }

    /*
      POST  — permanently delete an announcement + its files
      Body: id
    */
    public function delete(): void
    {
        RoleMiddleware::requireRole('sk_officer');
        $this->requireMethod('POST');

        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) $this->json(['status' => 'error', 'message' => 'Missing ID.'], 422);

        $existing = $this->model->getById($id);
        if (!$existing) $this->json(['status' => 'error', 'message' => 'Not found.'], 404);

        // Delete physical attachment files
        $files = $this->model->deleteFiles($id);
        foreach ($files as $f) {
            if (file_exists($f['file_path'])) @unlink($f['file_path']);
        }

        // Delete banner
        if ($existing['banner_img'] && file_exists($existing['banner_img'])) {
            @unlink($existing['banner_img']);
        }

        $this->model->delete($id)
            ? $this->json(['status' => 'success', 'message' => 'Announcement deleted.'])
            : $this->json(['status' => 'error',   'message' => 'Delete failed.'], 500);
    }

    // READ ACTIONS (officer + public/resident views)

    /*
      GET  — list for the officer management panel (all statuses)
      Params: search, category, status
    */
    public function listAll(): void
    {
        RoleMiddleware::requireRole('sk_officer');

        // Run auto-expire before returning the list
        $this->model->archiveExpired();

        $filters = [
            'search'   => $_GET['search']   ?? '',
            'category' => $_GET['category'] ?? '',
            'status'   => $_GET['status']   ?? '',
        ];

        $announcements = $this->model->getAll($filters);
        $stats         = $this->model->getStats();

        $this->json([
            'status' => 'success',
            'data'   => $announcements,
            'stats'  => $stats,
        ]);
    }

    /*
      GET  — list for the public / portal view (active only)
      Params: search, category, sort
    */
    public function listPublic(): void
    {
        RoleMiddleware::requireAuth();

        $filters = [
            'search'   => $_GET['search']   ?? '',
            'category' => $_GET['category'] ?? '',
            'sort'     => $_GET['sort']      ?? 'newest',
        ];

        $announcements = $this->model->getActive($filters);
        $featured      = $this->model->getFeatured();

        $this->json([
            'status'   => 'success',
            'data'     => $announcements,
            'featured' => $featured ?: null,
        ]);
    }

    /*
      GET  — single announcement (public view)
      Params: id
    */
    public function single(): void
    {
        RoleMiddleware::requireAuth();

        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) $this->json(['status' => 'error', 'message' => 'Missing ID.'], 422);

        $ann = $this->model->getById($id);
        if (!$ann || $ann['status'] !== 'active') {
            $this->json(['status' => 'error', 'message' => 'Announcement not found.'], 404);
        }

        $files = $this->model->getFiles($id);

        $this->json(['status' => 'success', 'data' => $ann, 'files' => $files]);
    }

    /*
      GET  — single announcement for the officer edit form
      Params: id
    */
    public function getForEdit(): void
    {
        RoleMiddleware::requireRole('sk_officer');

        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) $this->json(['status' => 'error', 'message' => 'Missing ID.'], 422);

        $ann = $this->model->getById($id);
        if (!$ann) $this->json(['status' => 'error', 'message' => 'Not found.'], 404);

        $files = $this->model->getFiles($id);
        $this->json(['status' => 'success', 'data' => $ann, 'files' => $files]);
    }

    // HELPERS

    private function json(array $payload, int $httpCode = 200): never
    {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }

    private function requireMethod(string $method): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
            $this->json(['status' => 'error', 'message' => 'Method not allowed.'], 405);
        }
    }

    private function uploadFile(array $file, string $dir, ?array $allowedMimes = null, int $maxBytes = 5 * 1024 * 1024): string|false
    {
        if ($file['error'] !== UPLOAD_ERR_OK) return false;
        if ($file['size'] > $maxBytes)        return false;

        if ($allowedMimes !== null) {
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if (!in_array($mimeType, $allowedMimes, true)) return false;
        }

        $projectRoot = str_replace('\\', '/', dirname(__DIR__, 2)) . '/';
        $absDir      = $projectRoot . $dir;

        if (!is_dir($absDir)) mkdir($absDir, 0755, true);

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('', true) . '.' . strtolower($ext);
        $destAbs  = str_replace('\\', '/', $absDir . $filename);

        if (!move_uploaded_file($file['tmp_name'], $destAbs)) return false;

        if (preg_match('#/(SKonnect/.+)$#i', $destAbs, $m)) {
            return '/' . $m[1];
        }

        $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
        return str_replace($docRoot, '', $destAbs);
    }

    private function normaliseFileArray(array $files): array
    {
        $result = [];
        if (!is_array($files['name'])) {
            $result[] = $files;
            return $result;
        }
        for ($i = 0; $i < count($files['name']); $i++) {
            $result[] = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
        }
        return $result;
    }
}
