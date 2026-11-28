<?php

require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';
require_once __DIR__ . '/../models/NotificationModel.php';
require_once __DIR__ . '/../services/AIKnowledgeGap.php';
require_once __DIR__ . '/../services/RequestOrigin.php';

RoleMiddleware::requireOfficerOrAbove();
header('Content-Type: application/json; charset=utf-8');

function answerDeskResponse(string $status, string $message, array $data = [], int $httpStatus = 200): void
{
    http_response_code($httpStatus);
    echo json_encode(
        ['status' => $status, 'message' => $message, 'data' => $data],
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

function answerDeskDate(?string $date): string|false|null
{
    if ($date === null || $date === '') {
        return null;
    }
    $parsed = DateTime::createFromFormat('!Y-m-d', $date);
    return $parsed && $parsed->format('Y-m-d') === $date ? $date : false;
}

function answerDeskBool($value): bool
{
    return $value === true || $value === 1 || $value === '1'
        || $value === 't' || $value === 'true';
}

$method = $_SERVER['REQUEST_METHOD'] ?? '';
$actorId = (int) ($_SESSION['user_id'] ?? 0);
if ($actorId < 1) {
    answerDeskResponse('error', 'Authentication required.', [], 401);
}

if ($method === 'GET' && ($_GET['action'] ?? '') === 'list') {
    $filter = $_GET['filter'] ?? 'pending';
    $filters = ['pending', 'answered', 'retracted', 'dismissed', 'flagged', 'review'];
    if (!in_array($filter, $filters, true)) {
        answerDeskResponse('error', 'Invalid filter.', [], 400);
    }

    $conditions = [];
    switch ($filter) {
        case 'pending':
            $conditions[] = "q.status = 'pending' AND q.is_active = TRUE";
            break;
        case 'answered':
            $conditions[] = "q.status = 'answered' AND q.is_active = TRUE";
            break;
        case 'retracted':
            $conditions[] = 'q.is_active = FALSE';
            break;
        case 'dismissed':
            $conditions[] = "q.status = 'dismissed'";
            break;
        case 'flagged':
            $conditions[] = "EXISTS (
                SELECT 1 FROM chatbot_answer_flags ff
                WHERE ff.question_id = q.id AND ff.resolved = FALSE
            )";
            break;
        case 'review':
            $conditions[] = "q.status = 'answered' AND q.is_active = TRUE
                             AND q.review_by IS NOT NULL AND q.review_by < CURRENT_DATE";
            break;
    }

    try {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare(
            "SELECT q.id, q.question, q.status, q.answer, q.keywords, q.asked_count,
                    q.source, q.first_asked_by, q.answered_at, q.review_by, q.flag_count,
                    q.is_active, q.created_at, q.updated_at,
                    CONCAT(answerer.first_name, ' ', answerer.last_name) AS answered_by_name,
                    CONCAT(editor.first_name, ' ', editor.last_name) AS updated_by_name,
                    COALESCE((
                        SELECT json_agg(json_build_object(
                            'id', f.id, 'reason', f.reason, 'created_at', f.created_at,
                            'resolved', f.resolved,
                            'user_name', CONCAT(flagger.first_name, ' ', flagger.last_name)
                        ) ORDER BY f.created_at DESC)
                        FROM chatbot_answer_flags f
                        LEFT JOIN users flagger ON flagger.id = f.user_id
                        WHERE f.question_id = q.id
                    ), '[]'::json) AS flags
             FROM chatbot_questions q
             LEFT JOIN users answerer ON answerer.id = q.answered_by
             LEFT JOIN users editor ON editor.id = q.updated_by
             WHERE " . implode(' AND ', $conditions) . "
             ORDER BY q.created_at DESC, q.id DESC"
        );
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as &$item) {
            $item['id'] = (int) $item['id'];
            $item['asked_count'] = (int) $item['asked_count'];
            $item['flag_count'] = (int) $item['flag_count'];
            $item['is_active'] = answerDeskBool($item['is_active']);
            $item['flags'] = json_decode((string) $item['flags'], true) ?: [];
        }
        unset($item);
        answerDeskResponse('success', 'OK', $items);
    } catch (Throwable $e) {
        error_log('Answer Desk list failed.');
        answerDeskResponse('error', 'Unable to load Answer Desk questions.', [], 500);
    }
}

if ($method !== 'POST') {
    header('Allow: GET, POST');
    answerDeskResponse('error', 'Method not allowed.', [], 405);
}
if (!RequestOrigin::isSameOrigin()) {
    answerDeskResponse('error', 'Request origin not allowed.', [], 403);
}

$rawBody = file_get_contents('php://input');
if (!is_string($rawBody) || strlen($rawBody) > 16384) {
answerDeskResponse('error', 'Request body is invalid or too large.', [], 413);
}
$request = json_decode($rawBody, true);
if (!is_array($request)) {
    answerDeskResponse('error', 'Invalid JSON request body.', [], 400);
}

$action = is_string($request['action'] ?? null) ? $request['action'] : '';
$id = filter_var($request['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$question = is_string($request['question'] ?? null) ? trim($request['question']) : '';
$answer = is_string($request['answer'] ?? null) ? trim($request['answer']) : '';
$keywords = is_string($request['keywords'] ?? null) ? trim($request['keywords']) : '';
$reviewBy = answerDeskDate(is_string($request['review_by'] ?? null) ? $request['review_by'] : null);

if ($reviewBy === false) {
    answerDeskResponse('error', 'Review date must use YYYY-MM-DD.', [], 400);
}
if (mb_strlen($keywords, 'UTF-8') > 500) {
    answerDeskResponse('error', 'Keywords must be 500 characters or fewer.', [], 400);
}
if (in_array($action, ['answer', 'edit_answer', 'manual_add'], true)) {
    if ($answer === '' || mb_strlen($answer, 'UTF-8') > 1500) {
        answerDeskResponse('error', 'Answer is required and must be 1,500 characters or fewer.', [], 400);
    }
}
if ($action === 'manual_add' && ($question === '' || mb_strlen($question, 'UTF-8') > 300)) {
    answerDeskResponse('error', 'Question is required and must be 300 characters or fewer.', [], 400);
}
if ($action !== 'manual_add' && $id === false) {
    answerDeskResponse('error', 'A valid question ID is required.', [], 400);
}

try {
    $db = (new Database())->getConnection();
    $log = new ActivityLogModel($db);
    $notifyIds = [];
    $questionId = 0;
    $db->beginTransaction();

    if ($action === 'manual_add') {
        $questionId = 0;
        $stmt = $db->prepare(
            "INSERT INTO chatbot_questions
                (question, question_normalized, status, answer, keywords, asked_count,
                 source, answered_by, updated_by, answered_at, review_by)
             VALUES
                (:question, :normalized, 'answered', :answer, :keywords, 0,
                 'manual', :answered_by, :updated_by, NOW(), :review_by)
             RETURNING id"
        );
        $stmt->execute([
            ':question' => $question,
            ':normalized' => AIKnowledgeGap::normalize($question),
            ':answer' => $answer,
            ':keywords' => $keywords !== '' ? $keywords : null,
            ':answered_by' => $actorId,
            ':updated_by' => $actorId,
            ':review_by' => $reviewBy,
        ]);
        $questionId = (int) $stmt->fetchColumn();
        $log->log($actorId, 'chatbot_manual_faq_added', [
            'target_type' => 'chatbot_question',
            'target_id' => $questionId,
            'notes' => 'Manual community FAQ added.',
        ]);
    } else {
        $current = $db->prepare(
            'SELECT id, status, is_active, answer FROM chatbot_questions WHERE id = :id FOR UPDATE'
        );
        $current->execute([':id' => $id]);
        $row = $current->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $db->rollBack();
            answerDeskResponse('error', 'Question not found.', [], 404);
        }
        $questionId = (int) $row['id'];

        if ($action === 'answer' || $action === 'edit_answer') {
            $requiredStatus = $action === 'answer' ? 'pending' : 'answered';
            if ($row['status'] !== $requiredStatus || !answerDeskBool($row['is_active'])) {
                $db->rollBack();
                answerDeskResponse('error', 'This question can no longer be changed with that action.', [], 409);
            }
            if ($action === 'edit_answer' && is_string($row['answer']) && $row['answer'] !== '') {
                $revision = $db->prepare(
                    "INSERT INTO chatbot_question_revisions (question_id, actor_id, answer, action)
                     VALUES (:question_id, :actor_id, :answer, 'edit')"
                );
                $revision->execute([
                    ':question_id' => $questionId,
                    ':actor_id' => $actorId,
                    ':answer' => $row['answer'],
                ]);
            }
            if ($action === 'answer') {
                $stmt = $db->prepare(
                    "UPDATE chatbot_questions
                     SET status = 'answered', answer = :answer, keywords = :keywords,
                         answered_by = :answered_by, answered_at = NOW(),
                         updated_by = :updated_by, review_by = :review_by
                     WHERE id = :id"
                );
                $stmt->execute([
                    ':answer' => $answer,
                    ':keywords' => $keywords !== '' ? $keywords : null,
                    ':answered_by' => $actorId,
                    ':updated_by' => $actorId,
                    ':review_by' => $reviewBy,
                    ':id' => $questionId,
                ]);
            } else {
                $stmt = $db->prepare(
                    "UPDATE chatbot_questions
                     SET answer = :answer, keywords = :keywords,
                         updated_by = :updated_by, review_by = :review_by
                     WHERE id = :id"
                );
                $stmt->execute([
                    ':answer' => $answer,
                    ':keywords' => $keywords !== '' ? $keywords : null,
                    ':updated_by' => $actorId,
                    ':review_by' => $reviewBy,
                    ':id' => $questionId,
                ]);
            }
            if ($action === 'answer') {
                $askers = $db->prepare(
                    'SELECT user_id FROM chatbot_question_askers WHERE question_id = :question_id'
                );
                $askers->execute([':question_id' => $questionId]);
                $notifyIds = array_map('intval', $askers->fetchAll(PDO::FETCH_COLUMN));
                $auditAction = 'chatbot_answer_added';
                $auditNote = 'Answer added to a resident question.';
            } else {
                $auditAction = 'chatbot_answer_edited';
                $auditNote = 'Community FAQ answer edited.';
            }
            $log->log($actorId, $auditAction, [
                'target_type' => 'chatbot_question',
                'target_id' => $questionId,
                'notes' => $auditNote,
            ]);
        } elseif ($action === 'retract') {
            if ($row['status'] !== 'answered' || !answerDeskBool($row['is_active'])) {
                $db->rollBack();
                answerDeskResponse('error', 'Only an active answer can be retracted.', [], 409);
            }
            $stmt = $db->prepare(
                "UPDATE chatbot_questions SET is_active = FALSE, updated_by = :actor
                 WHERE id = :id"
            );
            $stmt->execute([':actor' => $actorId, ':id' => $questionId]);
            $log->log($actorId, 'chatbot_question_retracted', [
                'target_type' => 'chatbot_question',
                'target_id' => $questionId,
                'notes' => 'Community FAQ retracted from Kaya.',
            ]);
        } elseif ($action === 'return_pending') {
            if ($row['status'] !== 'answered') {
                $db->rollBack();
                answerDeskResponse('error', 'Only an answered question can return to pending.', [], 409);
            }
            if (is_string($row['answer']) && $row['answer'] !== '') {
                $revision = $db->prepare(
                    "INSERT INTO chatbot_question_revisions (question_id, actor_id, answer, action)
                     VALUES (:question_id, :actor_id, :answer, 'return_to_pending')"
                );
                $revision->execute([
                    ':question_id' => $questionId,
                    ':actor_id' => $actorId,
                    ':answer' => $row['answer'],
                ]);
            }
            $stmt = $db->prepare(
                "UPDATE chatbot_questions
                 SET status = 'pending', is_active = TRUE, answer = NULL,
                     answered_by = NULL, answered_at = NULL, updated_by = :actor
                 WHERE id = :id"
            );
            $stmt->execute([':actor' => $actorId, ':id' => $questionId]);
            $log->log($actorId, 'chatbot_question_returned_to_pending', [
                'target_type' => 'chatbot_question',
                'target_id' => $questionId,
                'notes' => 'Community FAQ returned to the pending queue.',
            ]);
        } elseif ($action === 'dismiss') {
            $stmt = $db->prepare(
                "UPDATE chatbot_questions SET status = 'dismissed', updated_by = :actor
                 WHERE id = :id"
            );
            $stmt->execute([':actor' => $actorId, ':id' => $questionId]);
            $log->log($actorId, 'chatbot_question_dismissed', [
                'target_type' => 'chatbot_question',
                'target_id' => $questionId,
                'notes' => 'Community question dismissed.',
            ]);
        } elseif ($action === 'resolve_flag') {
            $flagId = filter_var($request['flag_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($flagId === false) {
                $db->rollBack();
                answerDeskResponse('error', 'A valid flag ID is required.', [], 400);
            }
            $stmt = $db->prepare(
                'UPDATE chatbot_answer_flags SET resolved = TRUE
                 WHERE id = :flag_id AND question_id = :question_id AND resolved = FALSE'
            );
            $stmt->execute([':flag_id' => $flagId, ':question_id' => $questionId]);
            if ($stmt->rowCount() < 1) {
                $db->rollBack();
                answerDeskResponse('error', 'Flag not found or already resolved.', [], 404);
            }
            $log->log($actorId, 'chatbot_flag_resolved', [
                'target_type' => 'chatbot_answer_flag',
                'target_id' => (int) $flagId,
                'notes' => 'Answer feedback flag resolved.',
            ]);
        } else {
            $db->rollBack();
            answerDeskResponse('error', 'Unknown Answer Desk action.', [], 400);
        }
    }

    $db->commit();

    if ($action === 'answer' && $notifyIds !== []) {
        $notificationModel = new NotificationModel($db);
        $snippet = mb_strimwidth($answer, 0, 130, '…', 'UTF-8');
        foreach (array_unique($notifyIds) as $notifyId) {
            try {
                $notificationModel->create(
                    $notifyId,
                    'chatbot_answer',
                    'Your question was answered',
                    'An SK official answered your question: ' . $snippet,
                    'chatbot_question',
                    $questionId,
                    'dashboard.php',
                    1
                );
            } catch (Throwable $e) {
                error_log('Answer Desk notification insert failed for a resident.');
            }
        }
    }

    answerDeskResponse('success', 'Answer Desk updated.', ['id' => $questionId]);
} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Answer Desk update failed.');
    answerDeskResponse('error', 'Unable to update the Answer Desk.', [], 500);
}
