<?php

header('Content-Type: application/json; charset=utf-8');

function answerFlagResponse(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    answerFlagResponse(405, ['success' => false, 'message' => 'Method not allowed.']);
}

require_once __DIR__ . '/../services/RequestOrigin.php';
if (!RequestOrigin::isSameOrigin()) {
    answerFlagResponse(403, ['success' => false, 'message' => 'Request origin not allowed.']);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../middleware/RoleMiddleware.php';

$residentId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
if ($residentId === false || $residentId === null) {
    answerFlagResponse(401, ['success' => false, 'message' => 'Authentication required.']);
}
if (!RoleMiddleware::is('resident')) {
    answerFlagResponse(403, ['success' => false, 'message' => 'Resident access required.']);
}

$now = time();
$hits = is_array($_SESSION['chatbot_answer_flag_hits'] ?? null)
    ? array_values(array_filter($_SESSION['chatbot_answer_flag_hits'], static function ($hit) use ($now): bool {
        return is_int($hit) && $hit > $now - 86400;
    }))
    : [];
if (count($hits) >= 20) {
    answerFlagResponse(429, ['success' => false, 'message' => 'You have reported the maximum number of answers today.']);
}

$rawBody = file_get_contents('php://input');
if (!is_string($rawBody) || strlen($rawBody) > 4096) {
    answerFlagResponse(413, ['success' => false, 'message' => 'Request body is invalid or too large.']);
}
$request = json_decode($rawBody, true);
$rawIds = is_array($request) ? ($request['faq_ids'] ?? null) : null;
$reason = is_array($request) && is_string($request['reason'] ?? null)
    ? trim($request['reason'])
    : null;
if (!is_array($rawIds) || $rawIds === [] || count($rawIds) > 10 || ($reason !== null && mb_strlen($reason, 'UTF-8') > 300)) {
    answerFlagResponse(400, ['success' => false, 'message' => 'Invalid answer feedback request.']);
}

$ids = [];
foreach ($rawIds as $rawId) {
    if (!is_int($rawId) || $rawId < 1) {
        answerFlagResponse(400, ['success' => false, 'message' => 'FAQ IDs must be positive integers.']);
    }
    $ids[$rawId] = $rawId;
}

require_once __DIR__ . '/../config/database.php';
try {
    $db = (new Database())->getConnection();
    $db->beginTransaction();
    $flag = $db->prepare(
        "WITH inserted_flag AS (
            INSERT INTO chatbot_answer_flags (question_id, user_id, reason)
            SELECT :question_id, :user_id, :reason
            WHERE EXISTS (
                SELECT 1 FROM chatbot_questions
                WHERE id = :active_question_id AND status = 'answered' AND is_active = TRUE
            )
            ON CONFLICT (question_id, user_id) DO UPDATE
            SET resolved = FALSE, reason = EXCLUDED.reason, created_at = NOW()
            WHERE chatbot_answer_flags.resolved = TRUE
            RETURNING question_id
        )
        UPDATE chatbot_questions
        SET flag_count = flag_count + 1
        WHERE id IN (SELECT question_id FROM inserted_flag)"
    );

    foreach ($ids as $id) {
        $flag->execute([
            ':question_id' => $id,
            ':user_id' => (int) $residentId,
            ':reason' => $reason,
            ':active_question_id' => $id,
        ]);
    }
    $db->commit();
    $hits[] = $now;
    $_SESSION['chatbot_answer_flag_hits'] = $hits;
    answerFlagResponse(200, ['success' => true, 'message' => 'Thank you for your feedback.']);
} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Chatbot answer feedback could not be saved.');
    answerFlagResponse(500, ['success' => false, 'message' => 'Unable to save answer feedback.']);
}
