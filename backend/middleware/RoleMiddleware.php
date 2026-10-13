<?php
require_once __DIR__ . '/../config/paths.php';

class RoleMiddleware {

    public static function requireAuth(): void {
        self::startSession();
        if (empty($_SESSION['user_id'])) {
            self::redirectToLogin();
        }
    }

    public static function requireRole(string|array $roles): void {
        self::requireAuth();

        $roles       = (array) $roles;
        $currentRole = $_SESSION['user_role'] ?? '';

        if (!in_array($currentRole, $roles, true)) {
            self::redirectToDenied();
        }
    }

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
        header('Location: ' . appUrl('views/auth/login.php'));
        exit;
    }

    private static function redirectToDenied(): void {
        header('Location: ' . appUrl('views/public/unauthorized.php'));
        exit;
    }
}
?>