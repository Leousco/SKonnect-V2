<?php
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../services/StorageService.php';
RoleMiddleware::requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ThreadModel.php';

$db    = new Database();
$conn  = $db->getConnection();
$model = new ThreadModel($conn);

$author_id = $_SESSION['user_id'] ?? null;
if (!$author_id) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated.']);
    exit;
}


$allowed_categories = ['inquiry', 'complaint', 'suggestion', 'event_question', 'other'];

$category = trim($_POST['category'] ?? '');
$subject  = trim($_POST['subject']  ?? '');
$message  = trim($_POST['message']  ?? '');


$errors = [];
if (!in_array($category, $allowed_categories)) $errors[] = 'Invalid category.';
if (strlen($subject) < 5)                      $errors[] = 'Subject must be at least 5 characters.';
if (strlen($message) < 10)                     $errors[] = 'Message must be at least 10 characters.';

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(' ', $errors)]);
    exit;
}


$thread_id = $model->createThread((int)$author_id, $category, $subject, $message);


$allowed_mime  = ['image/jpeg', 'image/png'];
$max_size      = 5 * 1024 * 1024;
$upload_errors = [];

if (!empty($_FILES['images']['name'][0])) {
    $files = $_FILES['images'];
    $count = count($files['name']);

    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

        $tmp  = $files['tmp_name'][$i];
        $size = $files['size'][$i];
        $mime = mime_content_type($tmp);
        $orig = basename($files['name'][$i]);

        if (!in_array($mime, $allowed_mime)) {
            $upload_errors[] = "$orig: only JPEG and PNG allowed.";
            continue;
        }
        if ($size > $max_size) {
            $upload_errors[] = "$orig: exceeds 5MB limit.";
            continue;
        }

        try {
            $url = StorageService::uploadImage($tmp, $mime, $thread_id . '_');
            $model->addThreadImage($thread_id, $orig, $url);
        } catch (Throwable $e) {
            error_log("Thread image upload failed: " . $e->getMessage());
            $upload_errors[] = "$orig: upload failed.";
        }
    }
}

echo json_encode([
    'status'        => 'success',
    'message'       => 'Thread posted successfully.',
    'thread_id'     => $thread_id,
    'upload_errors' => $upload_errors,
]);