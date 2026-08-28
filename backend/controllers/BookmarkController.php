<?php
require_once __DIR__ . '/../models/BookmarkModel.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';

class BookmarkController
{
    private BookmarkModel $model;

    public function __construct()
    {
        $this->model = new BookmarkModel();
    }

    /* POST — toggle a bookmark on/off */
    public function toggle(): void
    {
        RoleMiddleware::requireAuth();
        $this->requireMethod('POST');

        $announcementId = (int) ($_POST['announcement_id'] ?? 0);
        if (!$announcementId) {
            $this->json(['status' => 'error', 'message' => 'Missing announcement_id.'], 422);
        }

        $userId     = (int) $_SESSION['user_id'];
        $bookmarked = $this->model->toggle($userId, $announcementId);

        $this->json([
            'status'     => 'success',
            'bookmarked' => $bookmarked,
        ]);
    }

    /* GET — return just the bookmarked IDs (lightweight, used for page init) */
    public function ids(): void
    {
        RoleMiddleware::requireAuth();

        $userId = (int) $_SESSION['user_id'];
        $ids    = $this->model->getBookmarkedIds($userId);

        $this->json([
            'status' => 'success',
            'ids'    => $ids,
        ]);
    }

    // Helpers

    private function json(array $payload, int $httpCode = 200): never
    {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }

    private function requireMethod(string $method): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
            $this->json(['status' => 'error', 'message' => 'Method not allowed.'], 405);
        }
    }
}
?>