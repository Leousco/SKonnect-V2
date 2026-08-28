<?php
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/EmailService.php';
require_once __DIR__ . '/../models/UserAdminModel.php';
require_once __DIR__ . '/ActivityLogController.php';

RoleMiddleware::requireAdmin();
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

$raw    = file_get_contents('php://input');
$data   = json_decode($raw, true) ?? [];
$action = $data['action'] ?? ($_GET['action'] ?? '');

$actorId      = $_SESSION['user_id'] ?? null;
$emailService = new EmailService();

const ALLOWED_ROLES   = ['admin', 'moderator', 'sk_officer', 'resident'];
const ALLOWED_GENDERS = ['male', 'female', 'other'];
const ROLE_LABELS     = [
    'admin'      => 'System Admin',
    'moderator'  => 'Moderator',
    'sk_officer' => 'SK Officer',
    'resident'   => 'Resident',
];

try {
    $db    = (new Database())->getConnection();
    $model = new UserAdminModel($db);

    switch ($action) {

        case 'get_users':
            $users  = $model->getAll();
            $counts = array_fill_keys(array_keys(ROLE_LABELS), 0);
            foreach ($users as $u) {
                if (isset($counts[$u['role']])) $counts[$u['role']]++;
            }
            echo json_encode(['status' => 'success', 'data' => compact('users', 'counts')]);
            break;

        case 'add_user':
            foreach (['first_name', 'last_name', 'email', 'password', 'role', 'gender', 'birth_date'] as $f) {
                if (empty($data[$f])) respond(400, "Field '$f' is required.");
            }
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) respond(400, 'Invalid email format.');
            if (!in_array($data['role'],   ALLOWED_ROLES))          respond(400, 'Invalid role.');
            if (!in_array($data['gender'], ALLOWED_GENDERS))        respond(400, 'Invalid gender.');

            $email = strtolower(trim($data['email']));
            if ($model->emailExists($email)) respond(409, 'Email is already in use.');

            $age          = (int)(new DateTime())->diff(new DateTime($data['birth_date']))->y;
            $fullName     = trim($data['first_name']) . ' ' . trim($data['last_name']);
            $verifyToken  = bin2hex(random_bytes(32));
            $verifyUrl    = 'http://' . $_SERVER['HTTP_HOST'] . '/SKonnect/views/auth/verify_account.php?token=' . $verifyToken;

            $newId = $model->create([
                'first_name'   => trim($data['first_name']),
                'last_name'    => trim($data['last_name']),
                'middle_name'  => trim($data['middle_name'] ?? ''),
                'gender'       => $data['gender'],
                'birth_date'   => $data['birth_date'],
                'age'          => $age,
                'email'        => $email,
                'password'     => password_hash($data['password'], PASSWORD_BCRYPT),
                'role'         => $data['role'],
                'verify_token' => $verifyToken,
            ]);

            ActivityLogController::log(
                $db, $actorId, 'created',
                "Created new user account for <strong>{$fullName}</strong> with role <strong>" . ROLE_LABELS[$data['role']] . "</strong>"
            );

            $emailService->sendVerificationLinkEmail(
                email:      $email,
                name:       $fullName,
                role:       ROLE_LABELS[$data['role']],
                verifyUrl:  $verifyUrl
            );

            echo json_encode(['status' => 'success', 'message' => 'User created. A verification email has been sent.', 'id' => $newId]);
            break;

        case 'update_user':
            if (empty($data['id'])) respond(400, 'User ID is required.');

            $user = $model->findById((int)$data['id']);
            if (!$user) respond(404, 'User not found.');

            $email = strtolower(trim($data['email'] ?? $user['email']));
            if ($email !== $user['email']) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond(400, 'Invalid email format.');
                if ($model->emailExists($email, (int)$data['id'])) respond(409, 'Email already in use by another account.');
            }

            $gender     = in_array($data['gender'] ?? '', ALLOWED_GENDERS) ? $data['gender'] : $user['gender'];
            $birth_date = $data['birth_date'] ?? $user['birth_date'];
            $newFirst   = trim($data['first_name']  ?? $user['first_name']);
            $newLast    = trim($data['last_name']   ?? $user['last_name']);
            $newMiddle  = trim($data['middle_name'] ?? $user['middle_name']);
            $age        = (int)(new DateTime())->diff(new DateTime($birth_date))->y;

            $model->update((int)$data['id'], compact('email', 'gender', 'birth_date', 'age') + [
                'first_name'  => $newFirst,
                'last_name'   => $newLast,
                'middle_name' => $newMiddle,
            ]);

            $changes = [];
            if ($newFirst   !== $user['first_name'])  $changes[] = "First name → <strong>{$newFirst}</strong>";
            if ($newLast    !== $user['last_name'])   $changes[] = "Last name → <strong>{$newLast}</strong>";
            if ($newMiddle  !== $user['middle_name']) $changes[] = "Middle name → <strong>{$newMiddle}</strong>";
            if ($email      !== $user['email'])       $changes[] = "Email → <strong>" . htmlspecialchars($email) . "</strong>";
            if ($gender     !== $user['gender'])      $changes[] = "Gender → <strong>" . ucfirst($gender) . "</strong>";
            if ($birth_date !== $user['birth_date'])  $changes[] = "Birth date → <strong>{$birth_date}</strong>";

            $fullName    = $newFirst . ' ' . $newLast;
            $changesList = $changes
                ? '<ul style="margin:8px 0 0;padding-left:18px;">'
                    . implode('', array_map(fn($c) => "<li style='margin-bottom:4px;'>{$c}</li>", $changes))
                    . '</ul>'
                : '<p>Minor profile details were updated.</p>';

            ActivityLogController::log(
                $db, $actorId, 'updated',
                "Updated profile of <strong>{$fullName}</strong>: "
                    . ($changes ? implode(', ', array_map('strip_tags', $changes)) : 'minor details')
            );

            $emailService->sendAdminActionNotification(
                email:      $email,
                name:       $fullName,
                subject:    'Your SKonnect Profile Has Been Updated',
                badge:      '✏️ Profile Updated',
                badgeColor: '#60a5fa',
                title:      'An admin has updated your profile information',
                bodyHtml:   "<p>The following changes were made to your account:</p>{$changesList}
                             <p style='margin-top:12px;color:rgba(255,255,255,0.6);font-size:12px;'>
                                 If you did not expect these changes, please contact the barangay office.</p>",
                bodyPlain:  "An admin updated your profile. Changes: " . implode(', ', array_map('strip_tags', $changes))
            );

            echo json_encode(['status' => 'success', 'message' => 'User info updated successfully.']);
            break;

        case 'update_role':
            if (empty($data['id']) || empty($data['role'])) respond(400, 'User ID and role are required.');
            if (!in_array($data['role'], ALLOWED_ROLES))    respond(400, 'Invalid role.');

            $user = $model->findById((int)$data['id']);
            if (!$user) respond(404, 'User not found.');

            $model->updateRole((int)$data['id'], $data['role']);

            $oldLabel = ROLE_LABELS[$user['role']] ?? $user['role'];
            $newLabel = ROLE_LABELS[$data['role']] ?? $data['role'];
            $fullName = $user['first_name'] . ' ' . $user['last_name'];

            ActivityLogController::log(
                $db, $actorId, 'updated',
                "Changed role of <strong>{$fullName}</strong>: {$oldLabel} → <strong>{$newLabel}</strong>"
            );

            $emailService->sendAdminActionNotification(
                email:      $user['email'],
                name:       $fullName,
                subject:    'Your SKonnect Role Has Been Updated',
                badge:      '🔄 Role Changed',
                badgeColor: '#a78bfa',
                title:      'Your account role has been changed',
                bodyHtml:   "<p>An administrator has updated your role on the SKonnect portal:</p>
                             <p style='margin:14px 0;font-size:15px;'>
                                 <span style='color:rgba(255,255,255,0.5);'>{$oldLabel}</span>
                                 &nbsp;→&nbsp;
                                 <strong style='color:#facc15;'>{$newLabel}</strong>
                             </p>
                             <p>Your new role may come with different permissions. Log in to see your updated access.</p>",
                bodyPlain:  "Your SKonnect role has been changed from {$user['role']} to {$data['role']}."
            );

            echo json_encode(['status' => 'success', 'message' => 'Role updated successfully.']);
            break;

        case 'toggle_user':
            if (empty($data['id'])) respond(400, 'User ID is required.');
            if ($actorId == $data['id']) respond(403, 'You cannot deactivate your own account.');

            $user = $model->findById((int)$data['id']);
            if (!$user) respond(404, 'User not found.');

            $newActive = $user['is_active'] ? 0 : 1;
            $model->setActive((int)$data['id'], $newActive);

            $fullName = $user['first_name'] . ' ' . $user['last_name'];
            $verb     = $newActive ? 'reactivated' : 'deactivated';

            ActivityLogController::log(
                $db, $actorId, 'updated',
                "Account <strong>{$verb}</strong> for <strong>{$fullName}</strong>"
            );

            $emailService->sendAdminActionNotification(
                email:      $user['email'],
                name:       $fullName,
                subject:    $newActive ? 'Your SKonnect Account Has Been Reactivated' : 'Your SKonnect Account Has Been Deactivated',
                badge:      $newActive ? '✅ Account Reactivated' : '🚫 Account Deactivated',
                badgeColor: $newActive ? '#4ade80' : '#fbbf24',
                title:      $newActive ? 'Your account is now active again' : 'Your account has been temporarily deactivated',
                bodyHtml:   $newActive
                    ? "<p>Your SKonnect account has been reactivated by an administrator.</p>
                       <p>You can now log in and access all your account features.</p>"
                    : "<p>Your SKonnect account has been temporarily deactivated by an administrator.</p>
                       <p>You will not be able to log in while your account is deactivated.</p>
                       <p style='color:rgba(255,255,255,0.6);font-size:12px;'>If you believe this is a mistake, please contact the barangay office.</p>",
                bodyPlain:  "Your SKonnect account has been {$verb}."
            );

            echo json_encode([
                'status'    => 'success',
                'message'   => "Account {$verb} for {$fullName}.",
                'is_active' => $newActive,
            ]);
            break;

        case 'ban_user':
            if (empty($data['id'])) respond(400, 'User ID is required.');
            if ($actorId == $data['id']) respond(403, 'You cannot ban your own account.');

            $user = $model->findById((int)$data['id']);
            if (!$user) respond(404, 'User not found.');

            $newBanned = $user['is_banned'] ? 0 : 1;
            $reason    = $newBanned ? (trim($data['reason'] ?? '') ?: 'Banned by admin') : null;
            $model->setBanned((int)$data['id'], $newBanned, $reason);

            $fullName = $user['first_name'] . ' ' . $user['last_name'];

            ActivityLogController::log(
                $db, $actorId, $newBanned ? 'declined' : 'updated',
                $newBanned
                    ? "Banned user <strong>{$fullName}</strong>. Reason: " . htmlspecialchars($reason ?? '')
                    : "Lifted ban for user <strong>{$fullName}</strong>"
            );

            $emailService->sendAdminActionNotification(
                email:      $user['email'],
                name:       $fullName,
                subject:    $newBanned ? 'Your SKonnect Account Has Been Restricted' : 'Your SKonnect Account Restriction Has Been Lifted',
                badge:      $newBanned ? '⛔ Account Banned' : '🔓 Account Unbanned',
                badgeColor: $newBanned ? '#f87171' : '#4ade80',
                title:      $newBanned ? 'Your account has been restricted' : 'Your account restriction has been removed',
                bodyHtml:   $newBanned
                    ? "<p>Your SKonnect account has been restricted by an administrator.</p>
                       <p><strong>Reason:</strong> " . htmlspecialchars($reason) . "</p>
                       <p style='color:rgba(255,255,255,0.6);font-size:12px;'>If you believe this is a mistake, please contact the barangay office.</p>"
                    : "<p>Your SKonnect account restriction has been lifted by an administrator.</p>
                       <p>You now have full access to the portal again.</p>",
                bodyPlain:  $newBanned
                    ? "Your SKonnect account has been restricted.\nReason: {$reason}"
                    : "Your SKonnect account restriction has been lifted."
            );

            echo json_encode([
                'status'    => 'success',
                'message'   => "{$fullName} has been " . ($newBanned ? 'banned' : 'unbanned') . ".",
                'is_banned' => $newBanned,
            ]);
            break;

        case 'delete_user':
            if (empty($data['id'])) respond(400, 'User ID is required.');
            if ($actorId == $data['id']) respond(403, 'You cannot delete your own account.');

            $user = $model->findById((int)$data['id']);
            if (!$user) respond(404, 'User not found or already deleted.');

            $fullName = $user['first_name'] . ' ' . $user['last_name'];

            ActivityLogController::log(
                $db, $actorId, 'deleted',
                "Permanently deleted account: <strong>{$fullName}</strong> ({$user['email']})"
            );

            $emailService->sendAdminActionNotification(
                email:      $user['email'],
                name:       $fullName,
                subject:    'Your SKonnect Account Has Been Removed',
                badge:      '🗑️ Account Deleted',
                badgeColor: '#f87171',
                title:      'Your SKonnect account has been permanently removed',
                bodyHtml:   "<p>Your SKonnect account has been permanently deleted by an administrator.</p>
                             <p>All your account data has been removed from the system.</p>
                             <p style='color:rgba(255,255,255,0.6);font-size:12px;'>If you believe this was done in error, please contact the barangay office.</p>",
                bodyPlain:  "Your SKonnect account has been permanently deleted."
            );

            $model->softDelete((int)$data['id']);

            echo json_encode(['status' => 'success', 'message' => "User \"{$fullName}\" has been permanently deleted."]);
            break;

        default:
            respond(400, "Unknown action: '$action'");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

function respond(int $code, string $message): void
{
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}