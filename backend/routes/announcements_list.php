<?php
/**
 * announcements_list.php
 * Returns paginated announcements for the admin panel.
 * Place at: /backend/routes/announcements_list.php
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../config/database.php';

RoleMiddleware::requireAdmin();

$db   = new Database();
$conn = $db->getConnection();

$search   = trim($_GET['search']   ?? '');
$category = trim($_GET['category'] ?? '');
$status   = trim($_GET['status']   ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 10;
$offset   = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[]          = '(a.title LIKE :search OR a.content LIKE :search2)';
    $params[':search']  = "%$search%";
    $params[':search2'] = "%$search%";
}
if ($category !== '') {
    $where[]            = 'a.category = :category';
    $params[':category'] = $category;
}
if ($status !== '') {
    $where[]          = 'a.status = :status';
    $params[':status'] = $status;
}

$whereSQL = implode(' AND ', $where);

// Total count
$countStmt = $conn->prepare("SELECT COUNT(*) FROM announcements a WHERE $whereSQL");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

// Rows
$stmt = $conn->prepare("
    SELECT
        a.id,
        a.title,
        a.category,
        a.status,
        a.featured,
        a.banner_img,
        a.published_at,
        a.expired_at,
        a.updated_at,
        CONCAT(u.first_name, ' ', u.last_name) AS author
    FROM announcements a
    JOIN users u ON a.author_id = u.id
    WHERE $whereSQL
    ORDER BY
        FIELD(a.status, 'active', 'draft', 'archived'),
        a.featured DESC,
        a.published_at DESC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$statsStmt = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'active')   AS active,
        SUM(status = 'draft')    AS draft,
        SUM(status = 'archived') AS archived,
        SUM(featured = 1)        AS featured,
        SUM(category = 'urgent') AS urgent
    FROM announcements
");
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'status' => 'success',
    'data'   => [
        'rows'     => $rows,
        'total'    => $total,
        'page'     => $page,
        'perPage'  => $perPage,
        'pages'    => max(1, (int) ceil($total / $perPage)),
        'stats'    => $stats,
    ],
]);