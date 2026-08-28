<?php
/*
 * RoleMiddleware
 *
 * Usage — place at the top of any protected view or controller:
 *
 *   require_once __DIR__ . '/../../backend/middleware/RoleMiddleware.php';
 *   RoleMiddleware::require('admin');                         // single role
 *   RoleMiddleware::require(['admin', 'sk_officer']);         // multiple roles
 *   RoleMiddleware::requireAuth();                            // any logged-in user
 *
 * On failure it redirects to the login page (no JSON, since views call this).
 */
class RoleMiddleware {

    private static $loginPage   = '/SKonnect/views/auth/login.php';
    private static $deniedPage  = '/SKonnect/views/public/unauthorized.php';

    // Ensure user is logged in
    public static function requireAuth(): void {
        self::startSession();
        if (empty($_SESSION['user_id'])) {
            self::redirectToLogin();
        }
    }

    // Ensure user has an assigned role
    public static function requireRole(string|array $roles): void {
        self::requireAuth();

        $roles       = (array) $roles;
        $currentRole = $_SESSION['user_role'] ?? '';

        if (!in_array($currentRole, $roles, true)) {
            self::redirectToDenied();
        }
    }

    // Role aliases
    public static function requireAdmin(): void {
        self::requireRole('admin');
    }

    public static function requireOfficerOrAbove(): void {
        self::requireRole(['admin', 'sk_officer']);
    }

    public static function requireModeratorOrAbove(): void {
        self::requireRole(['admin', 'sk_officer', 'moderator']);
    }


    public static function currentRole(): string {
        self::startSession();
        return $_SESSION['user_role'] ?? '';
    }


    public static function is(string $role): bool {
        self::startSession();
        return ($_SESSION['user_role'] ?? '') === $role;
    }

    public static function isAny(array $roles): bool {
        self::startSession();
        return in_array($_SESSION['user_role'] ?? '', $roles, true);
    }

    
    private static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private static function redirectToLogin(): void {
        header('Location: ' . self::$loginPage);
        exit;
    }

    private static function redirectToDenied(): void {
        header('Location: ' . self::$deniedPage);
        exit;
    }
}
?>