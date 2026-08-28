<?php
require_once '../models/User.php';

class LoginController {

    private const MAX_ATTEMPTS    = 3;
    private const MAX_LOCKOUT_MIN = 30;

    private $roleRedirects = [
        'resident'   => '../portal/dashboard.php',
        'moderator'  => '../management/moderator/mod_dashboard.php',
        'sk_officer' => '../management/officer/officer_dashboard.php',
        'admin'      => '../management/admin/admin_dashboard.php',
    ];

    public function login() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
            exit;
        }

        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!$email || !$password) {
            echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
            exit;
        }

        $user        = new User();
        $user->email = $email;

        if (!$user->getUserByEmail() || $user->is_deleted) {
            echo json_encode(['status' => 'error', 'message' => 'Email not found.']);
            exit;
        }

        if ($user->is_banned) {
            echo json_encode([
                'status'  => 'banned',
                'message' => 'Your account has been restricted.',
                'reason'  => $user->banned_reason ?: 'No reason provided.',
            ]);
            exit;
        }

        // Check active lockout
        if ($user->lockout_until && strtotime($user->lockout_until) > time()) {
            $remaining = strtotime($user->lockout_until) - time();
            echo json_encode([
                'status'    => 'locked',
                'message'   => 'Account temporarily locked.',
                'remaining' => $remaining,
            ]);
            exit;
        }

        if ($user->is_verified != 1) {
            $_SESSION['verify_email'] = $user->email;
            echo json_encode([
                'status'   => 'unverified',
                'message'  => 'Your account is not verified. Redirecting to verification...',
                'redirect' => '../../views/auth/verify_email.php',
            ]);
            exit;
        }

        if (!password_verify($password, $user->password)) {
            $this->handleFailedAttempt($user);
            exit;
        }

        // Success — reset lockout state and open session
        $user->login_attempts = 0;
        $user->lockout_until  = null;
        $user->lockout_level  = 0;
        $user->updateLockoutState();

        $_SESSION['user_id']    = $user->id;
        $_SESSION['user_name']  = $user->first_name . ' ' . $user->last_name;
        $_SESSION['user_role']  = $user->role;
        $_SESSION['user_email'] = $user->email;

        $redirect = $this->roleRedirects[$user->role] ?? $this->roleRedirects['resident'];

        echo json_encode([
            'status'   => 'success',
            'message'  => 'Login successful.',
            'role'     => $user->role,
            'redirect' => $redirect,
        ]);
        exit;
    }

    private function handleFailedAttempt(User $user): void {
        $user->login_attempts++;

        if ($user->login_attempts >= self::MAX_ATTEMPTS) {
            // Escalating lockout: 5, 10, 15, 20, 25, 30 (capped)
            $minutes = min(self::MAX_LOCKOUT_MIN, 5 + $user->lockout_level * 5);

            $user->lockout_until  = date('Y-m-d H:i:s', time() + $minutes * 60);
            $user->lockout_level  = $user->lockout_level + 1;
            $user->login_attempts = 0;
            $user->updateLockoutState();

            echo json_encode([
                'status'    => 'locked',
                'message'   => "Too many failed attempts. Try again in {$minutes} minute" . ($minutes !== 1 ? 's' : '') . ".",
                'remaining' => $minutes * 60,
            ]);
            return;
        }

        $user->updateLockoutState();

        $left = self::MAX_ATTEMPTS - $user->login_attempts;
        echo json_encode([
            'status'  => 'error',
            'message' => 'Invalid input. Please try again.',
        ]);
    }
}