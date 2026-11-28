<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/NotificationModel.php';
require_once __DIR__ . '/AIContextProvider.php';

class AIKnowledgeGap
{
    private const MAX_FAQS = 30;
    private const RANKED_FAQS = 5;
    private const SIMILARITY_THRESHOLD = 0.5;

    private PDO $conn;

    public function __construct(?PDO $connection = null)
    {
        $this->conn = $connection ?? (new Database())->getConnection();
    }

    public function loadAnsweredFaqs(string $message): array
    {
        $countStmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM chatbot_questions
             WHERE status = 'answered' AND is_active = TRUE"
        );
        $countStmt->execute();
        $count = (int) $countStmt->fetchColumn();

        if ($count <= self::MAX_FAQS) {
            $stmt = $this->conn->prepare(
                "SELECT id, question, answer, keywords
                 FROM chatbot_questions
                 WHERE status = 'answered' AND is_active = TRUE
                 ORDER BY updated_at DESC, id DESC"
            );
            $stmt->execute();
            return $this->cleanFaqRows($stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        $normalized = self::normalize($message);
        $stmt = $this->conn->prepare(
            "SELECT id, question, answer, keywords
             FROM chatbot_questions
             WHERE status = 'answered' AND is_active = TRUE
               AND (
                    search_vector @@ plainto_tsquery('simple', :query)
                    OR similarity(question_normalized, :normalized) >= :threshold
                    OR keywords ILIKE :keyword_search
               )
             ORDER BY (
                ts_rank(search_vector, plainto_tsquery('simple', :query_rank))
                + similarity(question_normalized, :normalized_rank)
                + CASE WHEN keywords ILIKE :keyword_rank THEN 1 ELSE 0 END
             ) DESC, updated_at DESC
             LIMIT " . self::RANKED_FAQS
        );
        $stmt->execute([
            ':query' => $message,
            ':query_rank' => $message,
            ':normalized' => $normalized,
            ':normalized_rank' => $normalized,
            ':threshold' => self::SIMILARITY_THRESHOLD,
            ':keyword_search' => '%' . $message . '%',
            ':keyword_rank' => '%' . $message . '%',
        ]);
        return $this->cleanFaqRows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function formatFaqContext(array $faqs): string
    {
        $blocks = [];
        foreach ($faqs as $faq) {
            $blocks[] = '<community_faq id="' . (int) $faq['id'] . '">' . "\n"
                . 'question: ' . $faq['question'] . "\n"
                . 'answer: ' . $faq['answer'] . "\n"
                . 'keywords: ' . $faq['keywords'] . "\n"
                . '</community_faq>';
        }

        return implode("\n", $blocks);
    }

    public function recordUnanswered(string $question, int $residentId): array
    {
        $question = AIContextProvider::clean($question, 2000, false);
        $normalized = self::normalize($question);
        if (mb_strlen($question, 'UTF-8') < 5 || mb_strlen($question, 'UTF-8') > 300) {
            return ['logged' => false, 'matched' => null];
        }

        $this->conn->beginTransaction();
        try {
            $lock = $this->conn->prepare('SELECT id FROM users WHERE id = :user_id FOR UPDATE');
            $lock->execute([':user_id' => $residentId]);
            if ($lock->fetchColumn() === false) {
                $this->conn->commit();
                return ['logged' => false, 'matched' => null];
            }

            $questionLock = $this->conn->prepare('SELECT pg_advisory_xact_lock(hashtext(:normalized))');
            $questionLock->execute([':normalized' => $normalized]);

            $match = $this->findSimilarQuestion($normalized);
            if ($match && $match['status'] === 'answered') {
                $this->conn->commit();
                return ['logged' => false, 'matched' => $match, 'created' => false, 'question_id' => null];
            }

            $daily = $this->conn->prepare(
                "SELECT COUNT(*) FROM chatbot_question_submissions
                 WHERE user_id = :user_id
                   AND created_at >= CURRENT_DATE
                   AND created_at < CURRENT_DATE + INTERVAL '1 day'"
            );
            $daily->execute([':user_id' => $residentId]);
            if ((int) $daily->fetchColumn() >= 10) {
                $this->conn->commit();
                return ['logged' => false, 'matched' => null];
            }

            $created = false;
            if (!$match) {
                $pending = $this->conn->prepare(
                    "SELECT COUNT(DISTINCT q.id)
                     FROM chatbot_questions q
                     LEFT JOIN chatbot_question_askers qa ON qa.question_id = q.id
                     WHERE q.status = 'pending' AND q.is_active = TRUE
                       AND (qa.user_id = :asker_id OR q.first_asked_by = :first_asker_id)"
                );
                $pending->execute([
                    ':asker_id' => $residentId,
                    ':first_asker_id' => $residentId,
                ]);
                if ((int) $pending->fetchColumn() >= 5) {
                    $this->conn->commit();
                    return ['logged' => false, 'matched' => null];
                }

                $insert = $this->conn->prepare(
                    "INSERT INTO chatbot_questions
                        (question, question_normalized, status, source, first_asked_by)
                     VALUES (:question, :normalized, 'pending', 'user', :user_id)
                     RETURNING id"
                );
                $insert->execute([
                    ':question' => $question,
                    ':normalized' => $normalized,
                    ':user_id' => $residentId,
                ]);
                $questionId = (int) $insert->fetchColumn();
                $created = true;
            } else {
                $questionId = (int) $match['id'];
                $increment = $this->conn->prepare(
                    'UPDATE chatbot_questions SET asked_count = asked_count + 1 WHERE id = :id'
                );
                $increment->execute([':id' => $questionId]);
            }

            $asker = $this->conn->prepare(
                'INSERT INTO chatbot_question_askers (question_id, user_id)
                 VALUES (:question_id, :user_id)
                 ON CONFLICT (question_id, user_id) DO NOTHING'
            );
            $asker->execute([
                ':question_id' => $questionId,
                ':user_id' => $residentId,
            ]);

            $submission = $this->conn->prepare(
                'INSERT INTO chatbot_question_submissions (question_id, user_id)
                 VALUES (:question_id, :user_id)'
            );
            $submission->execute([
                ':question_id' => $questionId,
                ':user_id' => $residentId,
            ]);

            $this->conn->commit();
            return ['logged' => true, 'matched' => null, 'created' => $created, 'question_id' => $questionId];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function notifyOfficialsForNewQuestion(int $questionId, string $question): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT id, role FROM users WHERE role IN ('admin', 'sk_officer') ORDER BY id"
        );
        $stmt->execute();
        $officials = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($officials === []) {
            return false;
        }

        $message = 'A resident asked: ' . AIContextProvider::clean($question, 120);
        $allNotified = true;
        $notifications = new NotificationModel($this->conn);
        foreach ($officials as $official) {
            try {
                $role = (string) $official['role'];
                $notifications->create(
                    (int) $official['id'],
                    'chatbot_answer',
                    'New question for SK Answer Desk',
                    $message,
                    'chatbot_question',
                    $questionId,
                    $role === 'admin' ? 'admin_answer_desk.php' : 'officer_answer_desk.php',
                    1
                );
            } catch (Throwable $e) {
                $allNotified = false;
                error_log('Answer Desk official notification insert failed.');
            }
        }

        return $allNotified;
    }

    public static function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        return preg_replace('/\s+/u', ' ', $text) ?? '';
    }

    private function findSimilarQuestion(string $normalized): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, status, answer
             FROM chatbot_questions
             WHERE status IN ('pending', 'answered') AND is_active = TRUE
               AND (question_normalized = :normalized
                    OR similarity(question_normalized, :similarity_query) >= :threshold)
             ORDER BY (question_normalized = :exact_query) DESC,
                      (status = 'pending') DESC,
                      similarity(question_normalized, :rank_query) DESC
             LIMIT 1"
        );
        $stmt->execute([
            ':normalized' => $normalized,
            ':similarity_query' => $normalized,
            ':threshold' => self::SIMILARITY_THRESHOLD,
            ':exact_query' => $normalized,
            ':rank_query' => $normalized,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    private function cleanFaqRows(array $rows): array
    {
        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'question' => AIContextProvider::clean((string) $row['question'], 300),
                'answer' => AIContextProvider::clean((string) $row['answer'], 1500),
                'keywords' => AIContextProvider::clean((string) ($row['keywords'] ?? ''), 500),
            ];
        }, $rows);
    }
}
