<?php






header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../services/OfficerNotificationService.php';
require_once __DIR__ . '/../services/AdminNotificationService.php';
require_once __DIR__ . '/../services/StorageService.php';

RoleMiddleware::requireAdmin();

$db     = new Database();
$conn   = $db->getConnection();
$author = $_SESSION['user_id'] ?? 0;
$action = $_GET['action'] ?? ($_POST['action'] ?? '');









function resolveWebPath(string $absPath): string {
    return publicAssetUrl($absPath);
}

function notifyAdminsOfAnnouncementChange(PDO $connection, string $action, int $id, string $title): void
{
    try {
        (new AdminNotificationService($connection))->notifyAnnouncementChange($action, $id, $title);
    } catch (Throwable $e) {
        error_log('Unable to notify administrators about an announcement change: ' . $e->getMessage());
    }
}




if ($action === 'create') {

    $title      = trim($_POST['title']        ?? '');
    $content    = trim($_POST['content']      ?? '');
    $category   = trim($_POST['category']     ?? '');
    $featured   = isset($_POST['featured']) && $_POST['featured'] === '1' ? 1 : 0;
    $publishAt  = trim($_POST['publish_at']   ?? '') ?: date('Y-m-d H:i:s');
    $expiredAt  = trim($_POST['expired_at']   ?? '') ?: null;
    $saveAsDraft = isset($_POST['draft']) && $_POST['draft'] === '1';
    $status     = $saveAsDraft ? 'draft' : 'active';

    $validCategories = ['event','program','notice','meeting','urgent'];

    if (!$title || !$content || !in_array($category, $validCategories)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Title, content, and a valid category are required.']);
        exit;
    }

    
    $bannerPath = null;
    if (!empty($_FILES['banner']['name'])) {
        $banner = $_FILES['banner'];
        if ($banner['error'] !== UPLOAD_ERR_OK || $banner['size'] > 5 * 1024 * 1024) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Failed to upload banner image.']);
            exit;
        }
        $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $banner['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Failed to upload banner image.']);
            exit;
        }
        try {
            $bannerPath = StorageService::uploadAnnouncementBanner($banner['tmp_name'], $mime, 'banner_');
        } catch (Throwable $e) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Failed to upload banner image.']);
            exit;
        }
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO announcements
                (title, content, category, featured, featured_at, banner_img, author_id, published_at, expired_at, status)
            VALUES
                (:title, :content, :category, :featured, :featured_at, :banner, :author, :published_at, :expired_at, :status)
        ");
        $stmt->execute([
            ':title'        => $title,
            ':content'      => $content,
            ':category'     => $category,
            ':featured'     => $featured,
            ':featured_at'  => $featured ? date('Y-m-d H:i:s') : null,
            ':banner'       => $bannerPath,
            ':author'       => $author,
            ':published_at' => $publishAt,
            ':expired_at'   => $expiredAt,
            ':status'       => $status,
        ]);
        $newId = $conn->lastInsertId();

        
        if (!empty($_FILES['attachments']['name'][0])) {
            foreach ($_FILES['attachments']['name'] as $i => $fname) {
                if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) continue;
                if ($_FILES['attachments']['size'][$i] > 10 * 1024 * 1024) continue;
                $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $_FILES['attachments']['tmp_name'][$i]);
                try {
                    $path = StorageService::uploadFile(
                        $_FILES['attachments']['tmp_name'][$i],
                        $mime,
                        $fname,
                        'announcement-attachments',
                        'attachment_',
                        StorageService::ANNOUNCEMENT_ATTACHMENT_MIMES
                    );
                    $conn->prepare("
                        INSERT INTO announcement_files (announcement_id, file_path)
                        VALUES (:aid, :path)
                    ")->execute([
                        ':aid'  => $newId,
                        ':path' => $path,
                    ]);
                } catch (Throwable $e) {
                    error_log('Announcement attachment upload failed.');
                }
            }
        }

        notifyAdminsOfAnnouncementChange($conn, 'created', (int)$newId, $title);
        echo json_encode(['status' => 'success', 'id' => $newId, 'ann_status' => $status]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}




if ($action === 'getForEdit') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $conn->prepare("SELECT * FROM announcements WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Announcement not found.']);
        exit;
    }
    $filesStmt = $conn->prepare("SELECT id, file_path FROM announcement_files WHERE announcement_id = :id");
    $filesStmt->execute([':id' => $id]);
    echo json_encode(['status' => 'success', 'data' => $row, 'files' => $filesStmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}




if ($action === 'update') {

    $id         = (int)($_POST['id'] ?? 0);
    $title      = trim($_POST['title']       ?? '');
    $content    = trim($_POST['content']     ?? '');
    $category   = trim($_POST['category']    ?? '');
    $featured   = isset($_POST['featured']) && $_POST['featured'] === '1' ? 1 : 0;
    $status     = trim($_POST['status'] ?? 'active');
    $publishAt  = trim($_POST['publish_date'] ?? '') ?: date('Y-m-d H:i:s');
    $expiredAt  = trim($_POST['expiry_date']  ?? '') ?: null;

    $validCategories = ['event','program','notice','meeting','urgent'];
    $validStatuses   = ['active','draft','archived'];

    if (!$id || !$title || !$content || !in_array($category, $validCategories) || !in_array($status, $validStatuses)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Title, content, a valid category, and status are required.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT title, author_id, featured_at, banner_img, status, archived_at FROM announcements WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Announcement not found.']);
        exit;
    }

    $featuredAt = $featured ? ($existing['featured_at'] ?: date('Y-m-d H:i:s')) : null;
    $archivedAt = $status === 'archived' ? ($existing['archived_at'] ?: date('Y-m-d H:i:s')) : null;
    $bannerPath = $existing['banner_img'];

    if (!empty($_FILES['banner']['name'])) {
        $banner = $_FILES['banner'];
        if ($banner['error'] !== UPLOAD_ERR_OK || $banner['size'] > 5 * 1024 * 1024) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Failed to upload banner image.']);
            exit;
        }
        $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $banner['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Failed to upload banner image.']);
            exit;
        }
        try {
            $newBannerPath = StorageService::uploadAnnouncementBanner($banner['tmp_name'], $mime, 'banner_');
            if ($existing['banner_img'] && str_contains($existing['banner_img'], 'supabase.co/storage/v1/object/public/')) {
                try { StorageService::deleteFile($existing['banner_img']); } catch (Throwable $e) {}
            } elseif ($existing['banner_img'] && file_exists($existing['banner_img'])) {
                @unlink($existing['banner_img']);
            }
            $bannerPath = $newBannerPath;
        } catch (Throwable $e) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Failed to upload banner image.']);
            exit;
        }
    }

    try {
        $conn->prepare("
            UPDATE announcements
            SET title = :title, content = :content, category = :category,
                featured = :featured, featured_at = :featured_at,
                banner_img = :banner, status = :status, archived_at = :archived_at,
                published_at = :published_at, expired_at = :expired_at, updated_at = NOW()
            WHERE id = :id
        ")->execute([
            ':title'        => $title,
            ':content'      => $content,
            ':category'     => $category,
            ':featured'     => $featured,
            ':featured_at'  => $featuredAt,
            ':banner'       => $bannerPath,
            ':status'       => $status,
            ':archived_at'  => $archivedAt,
            ':published_at' => $publishAt,
            ':expired_at'   => $expiredAt,
            ':id'           => $id,
        ]);

        if (!empty($_FILES['attachments']['name'][0])) {
            foreach ($_FILES['attachments']['name'] as $i => $fname) {
                if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) continue;
                if ($_FILES['attachments']['size'][$i] > 10 * 1024 * 1024) continue;
                $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $_FILES['attachments']['tmp_name'][$i]);
                try {
                    $path = StorageService::uploadFile(
                        $_FILES['attachments']['tmp_name'][$i],
                        $mime,
                        $fname,
                        'announcement-attachments',
                        'attachment_',
                        StorageService::ANNOUNCEMENT_ATTACHMENT_MIMES
                    );
                    $conn->prepare("
                        INSERT INTO announcement_files (announcement_id, file_path)
                        VALUES (:aid, :path)
                    ")->execute([':aid' => $id, ':path' => $path]);
                } catch (Throwable $e) {
                    error_log('Announcement attachment upload failed.');
                }
            }
        }

        try {
            (new OfficerNotificationService($conn))->notifyAdminUpdate(
                (int)$existing['author_id'],
                'announcement',
                $id,
                $title
            );
        } catch (PDOException $e) {
            error_log('Unable to notify an announcement author about an admin edit: ' . $e->getMessage());
        }

        notifyAdminsOfAnnouncementChange($conn, 'updated', $id, $title);
        echo json_encode(['status' => 'success', 'message' => 'Announcement updated.', 'id' => $id]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}




$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $action ?: ($input['action'] ?? '');
$id     = (int)($input['id'] ?? 0);

if (!$id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing announcement ID.']);
    exit;
}

try {
    switch ($action) {

        case 'set-status':
            $newStatus = $input['status'] ?? '';
            if (!in_array($newStatus, ['active', 'draft', 'archived'])) {
                throw new Exception('Invalid status.');
            }
            $ownerStmt = $conn->prepare('SELECT title, author_id FROM announcements WHERE id = :id');
            $ownerStmt->execute([':id' => $id]);
            $owner = $ownerStmt->fetch(PDO::FETCH_ASSOC);
            $archivedAt = $newStatus === 'archived' ? date('Y-m-d H:i:s') : null;
            $conn->prepare("
                UPDATE announcements
                SET status = :status, archived_at = :archived_at, updated_at = NOW()
                WHERE id = :id
            ")->execute([':status' => $newStatus, ':archived_at' => $archivedAt, ':id' => $id]);
            if ($owner) notifyAdminsOfAnnouncementChange($conn, 'status', $id, (string)$owner['title']);
            if ($owner) {
                try {
                    (new OfficerNotificationService($conn))->notifyAdminUpdate(
                        (int)$owner['author_id'],
                        'announcement',
                        $id,
                        $owner['title']
                    );
                } catch (PDOException $e) {
                    error_log('Unable to notify an announcement author about an admin status change: ' . $e->getMessage());
                }
            }
            echo json_encode(['status' => 'success', 'new_status' => $newStatus]);
            break;

        case 'delete':
            
            $stmt = $conn->prepare("SELECT banner_img, title, author_id FROM announcements WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $ann = $stmt->fetch(PDO::FETCH_ASSOC);
            $attStmt = $conn->prepare("SELECT file_path FROM announcement_files WHERE announcement_id = :id");
            $attStmt->execute([':id' => $id]);
            $attachmentPaths = $attStmt->fetchAll(PDO::FETCH_COLUMN);
            $conn->prepare("DELETE FROM announcements WHERE id = :id")->execute([':id' => $id]);

            if ($ann && $ann['banner_img']) {
                if (str_contains($ann['banner_img'], 'supabase.co/storage/v1/object/public/')) {
                    try { StorageService::deleteFile($ann['banner_img']); } catch (Throwable $e) {}
                } else {
                    $localPath = $_SERVER['DOCUMENT_ROOT'] . $ann['banner_img'];
                    if (file_exists($localPath)) @unlink($localPath);
                }
            }
            
            foreach ($attachmentPaths as $path) {
                if (str_contains($path, 'supabase.co/storage/v1/object/public/')) {
                    try { StorageService::deleteFile($path); } catch (Throwable $e) {}
                } else {
                    $localPath = $_SERVER['DOCUMENT_ROOT'] . $path;
                    if (file_exists($localPath)) @unlink($localPath);
                }
            }
            if ($ann) {
                notifyAdminsOfAnnouncementChange($conn, 'deleted', $id, (string)$ann['title']);
                try {
                    (new OfficerNotificationService($conn))->notifyAdminUpdate(
                        (int)$ann['author_id'],
                        'announcement',
                        $id,
                        $ann['title']
                    );
                } catch (PDOException $e) {
                    error_log('Unable to notify an announcement author about an admin deletion: ' . $e->getMessage());
                }
            }
            echo json_encode(['status' => 'success']);
            break;

        case 'toggle-featured':
            $stmt = $conn->prepare("SELECT featured, title, author_id FROM announcements WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
            $current = (int)($announcement['featured'] ?? 0);
            $newVal  = $current ? 0 : 1;
            $conn->prepare("
                UPDATE announcements
                SET featured = :featured, featured_at = :featured_at, updated_at = NOW()
                WHERE id = :id
            ")->execute([
                ':featured'    => $newVal,
                ':featured_at' => $newVal ? date('Y-m-d H:i:s') : null,
                ':id'          => $id,
            ]);
            if ($announcement) {
                notifyAdminsOfAnnouncementChange($conn, 'featured', $id, (string)$announcement['title']);
                try {
                    (new OfficerNotificationService($conn))->notifyAdminUpdate(
                        (int)$announcement['author_id'],
                        'announcement',
                        $id,
                        $announcement['title']
                    );
                } catch (PDOException $e) {
                    error_log('Unable to notify an announcement author about an admin feature change: ' . $e->getMessage());
                }
            }
            echo json_encode(['status' => 'success', 'featured' => $newVal]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
