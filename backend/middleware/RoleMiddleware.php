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
        if (self::isJsonRequest()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Your session has expired. Please sign in again.']);
            exit;
        }
        header('Location: ' . appUrl('views/auth/login.php'));
        exit;
    }

    private static function redirectToDenied(): void {
        if (self::isJsonRequest()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'You are not authorized to access this resource.']);
            exit;
        }
        header('Location: ' . appUrl('views/public/unauthorized.php'));
        exit;
    }

    private static function isJsonRequest(): bool {
        $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
        $requestedWith = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        return strpos($accept, 'application/json') !== false
            || $requestedWith === 'xmlhttprequest'
            || strpos($_SERVER['SCRIPT_NAME'] ?? '', '/backend/routes/') !== false;
    }
}
?>
