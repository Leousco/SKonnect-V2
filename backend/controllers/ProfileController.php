<?php
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../models/UserProfileModel.php';

RoleMiddleware::requireAuth();
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated.']);
    exit;
}

$raw    = file_get_contents('php://input');
$data   = json_decode($raw, true) ?? [];
$action = $data['action'] ?? ($_POST['action'] ?? ($_GET['action'] ?? ''));

$model = new UserProfileModel();

try {
    switch ($action) {

        case 'get_profile':
            $profile = $model->getProfile((int)$userId);
            if (!$profile) respond(404, 'Profile not found.');
            echo json_encode(['status' => 'success', 'profile' => $profile]);
            break;

        case 'get_activity':
            $summary = $model->getActivitySummary((int)$userId);
            $recent  = $model->getRecentRequests((int)$userId, 5);
            $threads = $model->getUserThreads((int)$userId);
            echo json_encode([
                'status'  => 'success',
                'summary' => $summary,
                'recent'  => $recent,
                'threads' => $threads,
            ]);
            break;

        case 'save_personal':
            $required = ['first_name', 'last_name', 'birth_date', 'gender'];
            foreach ($required as $f) {
                if (empty(trim((string)($data[$f] ?? '')))) respond(400, "Field '$f' is required.");
            }
            if (!in_array($data['gender'], ['male', 'female', 'other'], true)) respond(400, 'Invalid gender value.');
            if (!validateDate($data['birth_date'])) respond(400, 'Invalid date of birth.');
            if (!in_array($data['civil_status'] ?? '', ['single', 'married', 'widowed', 'separated', 'annulled', ''], true)) respond(400, 'Invalid civil status value.');

            $model->savePersonal((int)$userId, $data);
            $profile = $model->getProfile((int)$userId);
            echo json_encode(['status' => 'success', 'message' => 'Personal information saved.', 'profile' => $profile]);
            break;

        case 'save_contact':
            $email  = trim($data['email']         ?? '');
            $mobile = trim($data['mobile_number'] ?? '');
            $purok  = trim($data['purok']         ?? '');

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) respond(400, 'A valid email address is required.');
            if (empty($mobile)) respond(400, 'Mobile number is required.');
            if (empty($purok))  respond(400, 'Purok / Zone is required.');

            $mobileClean = preg_replace('/\s+/', '', $mobile);
            if (!preg_match('/^(09|\+639)\d{9}$/', $mobileClean)) respond(400, 'Enter a valid PH mobile number (e.g. 0917 123 4567).');
            $data['mobile_number'] = $mobileClean;

            $model->saveContact((int)$userId, $data);
            $profile = $model->getProfile((int)$userId);
            echo json_encode(['status' => 'success', 'message' => 'Contact information saved.', 'profile' => $profile]);
            break;

        case 'save_membership':
            $allowedEdu = ['elementary', 'high_school', 'senior_high', 'vocational', 'college_level', 'college_graduate', 'post_graduate', ''];
            $allowedEmp = ['student', 'employed', 'unemployed', 'self_employed', ''];

            if (!in_array($data['educational_attainment'] ?? '', $allowedEdu, true)) respond(400, 'Invalid educational attainment value.');
            if (!in_array($data['employment_status'] ?? '', $allowedEmp, true)) respond(400, 'Invalid employment status value.');

            $model->saveMembership((int)$userId, $data);
            $profile = $model->getProfile((int)$userId);
            echo json_encode(['status' => 'success', 'message' => 'Membership information saved.', 'profile' => $profile]);
            break;

        case 'complete_setup':
            $mobile = trim($data['mobile_number'] ?? '');
            $purok  = trim($data['purok']         ?? '');

            if (empty($mobile)) respond(400, 'Mobile number is required.');
            if (empty($purok))  respond(400, 'Purok / Zone is required.');

            $mobileClean = preg_replace('/\s+/', '', $mobile);
            if (!preg_match('/^(09|\+639)\d{9}$/', $mobileClean)) respond(400, 'Enter a valid PH mobile number (e.g. 0917 123 4567).');
            $data['mobile_number'] = $mobileClean;

            $model->completeSetup((int)$userId, $data);
            $profile = $model->getProfile((int)$userId);
            echo json_encode([
                'status'   => 'success',
                'message'  => 'Profile setup complete! Welcome aboard.',
                'profile'  => $profile,
                'complete' => true,
            ]);
            break;

        case 'upload_avatar':
            if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) respond(400, 'No valid file uploaded.');

            $file         = $_FILES['avatar'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $finfo        = new finfo(FILEINFO_MIME_TYPE);
            $mimeType     = $finfo->file($file['tmp_name']);

            if (!in_array($mimeType, $allowedTypes, true)) respond(400, 'Only JPG, PNG, WebP, and GIF images are allowed.');
            if ($file['size'] > 2 * 1024 * 1024) respond(400, 'Image must be under 2 MB.');

            $uploadDir = __DIR__ . '/../../assets/uploads/avatars/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

            $ext = match ($mimeType) {
                'image/jpeg' => 'jpg', 'image/png' => 'png',
                'image/webp' => 'webp', 'image/gif' => 'gif', default => 'jpg',
            };
            $filename    = 'avatar_' . $userId . '_' . time() . '.' . $ext;
            $destPath    = $uploadDir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $destPath)) respond(500, 'Failed to save the image. Check server write permissions.');

            $relativePath = '/SKonnect/assets/uploads/avatars/' . $filename;
            $model->saveAvatar((int)$userId, $relativePath);

            echo json_encode(['status' => 'success', 'message' => 'Profile photo updated.', 'path' => $relativePath]);
            break;

        default:
            respond(400, "Unknown action: '$action'");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

function respond(int $code, string $message): never
{
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

function validateDate(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}