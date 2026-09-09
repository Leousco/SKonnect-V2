<?php
/**
 * announcement_action.php
 * Handles create / update-status / delete / toggle-featured for announcements.
 * Place at: /backend/routes/announcement_action.php
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../config/database.php';

RoleMiddleware::requireAdmin();

$db     = new Database();
$conn   = $db->getConnection();
$author = $_SESSION['user_id'] ?? 0;
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

/**
 * Convert an absolute filesystem path (e.g. after move_uploaded_file())
 * into the correct web-accessible URL path, the same way
 * AnnouncementController::uploadFile() does. This matters because the
 * project may be deployed inside a subfolder (e.g. /SKonnect/) instead
 * of directly at the web server's document root — a hardcoded
 * '/assets/uploads/...' path would 404 in that case.
 */
function resolveWebPath(string $absPath): string {
    $absPath = str_replace('\\', '/', $absPath);

    if (preg_match('#/(SKonnect/.+)$#i', $absPath, $m)) {
        return '/' . $m[1];
    }

    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    if ($docRoot !== '' && str_starts_with($absPath, $docRoot)) {
        return str_replace($docRoot, '', $absPath);
    }

    // Fallback: best effort, strip everything up to the project root marker
    return $absPath;
}

/* ══════════════════════════════════════════════════════════
   ACTION: create  (multipart/form-data)
   ══════════════════════════════════════════════════════════ */
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

    // Banner upload
    $bannerPath = null;
    if (!empty($_FILES['banner']['name'])) {
        $uploadDir = __DIR__ . '/../../assets/uploads/banners/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext        = pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION);
        $filename   = uniqid('', true) . '.' . $ext;
        $destPath   = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['banner']['tmp_name'], $destPath)) {
            $bannerPath = resolveWebPath($destPath);
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

        // Attachments
        if (!empty($_FILES['attachments']['name'][0])) {
            $attDir = __DIR__ . '/../../assets/uploads/attachments/';
            if (!is_dir($attDir)) mkdir($attDir, 0755, true);
            foreach ($_FILES['attachments']['name'] as $i => $fname) {
                if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $ext2  = pathinfo($fname, PATHINFO_EXTENSION);
                $fname2 = uniqid('', true) . '.' . $ext2;
                $dest2  = $attDir . $fname2;
                if (move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $dest2)) {
                    $conn->prepare("
                        INSERT INTO announcement_files (announcement_id, file_path)
                        VALUES (:aid, :path)
                    ")->execute([
                        ':aid'  => $newId,
                        ':path' => resolveWebPath($dest2),
                    ]);
                }
            }
        }

        echo json_encode(['status' => 'success', 'id' => $newId, 'ann_status' => $status]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

/* ══════════════════════════════════════════════════════════
   ACTION: getForEdit  (GET)
   ══════════════════════════════════════════════════════════ */
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

/* ══════════════════════════════════════════════════════════
   ACTION: update  (multipart/form-data)
   ══════════════════════════════════════════════════════════ */
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

    $stmt = $conn->prepare("SELECT featured_at, banner_img, status, archived_at FROM announcements WHERE id = :id");
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
        $uploadDir = __DIR__ . '/../../assets/uploads/banners/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext      = pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('', true) . '.' . $ext;
        $destPath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['banner']['tmp_name'], $destPath)) {
            $bannerPath = resolveWebPath($destPath);
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
            $attDir = __DIR__ . '/../../assets/uploads/attachments/';
            if (!is_dir($attDir)) mkdir($attDir, 0755, true);
            foreach ($_FILES['attachments']['name'] as $i => $fname) {
                if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $ext2   = pathinfo($fname, PATHINFO_EXTENSION);
                $fname2 = uniqid('', true) . '.' . $ext2;
                $dest2  = $attDir . $fname2;
                if (move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $dest2)) {
                    $conn->prepare("
                        INSERT INTO announcement_files (announcement_id, file_path)
                        VALUES (:aid, :path)
                    ")->execute([':aid' => $id, ':path' => resolveWebPath($dest2)]);
                }
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'Announcement updated.', 'id' => $id]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

/* ══════════════════════════════════════════════════════════
   JSON body actions: set-status | delete | toggle-featured
   ══════════════════════════════════════════════════════════ */
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
            $archivedAt = $newStatus === 'archived' ? date('Y-m-d H:i:s') : null;
            $conn->prepare("
                UPDATE announcements
                SET status = :status, archived_at = :archived_at, updated_at = NOW()
                WHERE id = :id
            ")->execute([':status' => $newStatus, ':archived_at' => $archivedAt, ':id' => $id]);
            echo json_encode(['status' => 'success', 'new_status' => $newStatus]);
            break;

        case 'delete':
            // Also delete physical banner/attachment files
            $stmt = $conn->prepare("SELECT banner_img FROM announcements WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $ann = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($ann && $ann['banner_img']) {
                $localPath = $_SERVER['DOCUMENT_ROOT'] . $ann['banner_img'];
                if (file_exists($localPath)) @unlink($localPath);
            }
            // Attachment files
            $attStmt = $conn->prepare("SELECT file_path FROM announcement_files WHERE announcement_id = :id");
            $attStmt->execute([':id' => $id]);
            foreach ($attStmt->fetchAll(PDO::FETCH_COLUMN) as $path) {
                $localPath = $_SERVER['DOCUMENT_ROOT'] . $path;
                if (file_exists($localPath)) @unlink($localPath);
            }
            $conn->prepare("DELETE FROM announcements WHERE id = :id")->execute([':id' => $id]);
            echo json_encode(['status' => 'success']);
            break;

        case 'toggle-featured':
            $stmt = $conn->prepare("SELECT featured FROM announcements WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $current = (int)$stmt->fetchColumn();
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