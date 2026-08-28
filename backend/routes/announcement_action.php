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
            $bannerPath = '/SKonnect/assets/uploads/banners/' . $filename;
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
                        ':path' => '/SKonnect/assets/uploads/attachments/' . $fname2,
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