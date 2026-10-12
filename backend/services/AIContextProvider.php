<?php

require_once __DIR__ . '/AIConfig.php';

/**
 * Collects the SMALL slice of portal data the chatbot is allowed to answer from.
 * Reads Supabase's PostgreSQL directly with PDO, using the same DB_HOST / DB_PORT / DB_NAME /
 * DB_USER / DB_PASS values your app already uses (from .env).
 *
 * Deliberately NOT included:
 *  - users, user_profiles, user_status values   (personal data)
 *  - service_applications / documents / notes   (other residents' private applications)
 *  - services.approval_message                  (contains approval codes shown only to approved residents)
 *  - notifications, activity_logs, reports, sanctions
 *  - file paths (attachments, banners)
 *  - thread comments/replies                    (add later if wanted)
 *
 * Every query below is SELECT-only. The WHERE clauses are what keep private or hidden rows out.
 */

class AIContextProvider
{

    private const MAX_FIELD_CHARS = 300;

    public function build(): string
    {
        $today = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
        $blocks = [];

        foreach ($this->sources() as $key => $source) {
            $lines = [];
            foreach ($this->fetchRows($source['sql'], !empty($source['uses_today']) ? ['today' => $today] : []) as $row) {
                $line = $this->formatRow($key, $row, $source);
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
            $blocks[] = $source['label'] . ":\n" . ($lines ? implode("\n", $lines) : '(none)');
        }

        return implode("\n\n", $blocks);
    }

    private function sources(): array
    {
        return [
            'about' => [
                'label'     => 'ABOUT THE BARANGAY AND THIS PORTAL',
                'sql'       => "SELECT \"key\", \"value\" FROM settings
                                WHERE \"key\" IN ('brgy_name','brgy_about','brgy_address','brgy_contact','brgy_email',
                                                 'brgy_municipality','brgy_province','sys_name','sys_tagline')
                                ORDER BY \"key\"",
                'max_chars' => 600,
                'html'      => false,
            ],
            'announcements' => [
                'label'      => 'ANNOUNCEMENTS (newest first; only active and not expired)',
                'sql'        => "SELECT title, category, content, published_at, expired_at
                                 FROM announcements
                                 WHERE status = 'active'
                                   AND (expired_at IS NULL OR expired_at >= CAST(:today AS date))
                                 ORDER BY published_at DESC
                                 LIMIT 10",
                'uses_today' => true,
                'max_chars'  => 1500, 
                'html'       => true, 
            ],
            'events' => [
                'label'      => 'UPCOMING EVENTS (soonest first)',
                'sql'        => "SELECT title, event_date, event_time, event_time_end, location, description
                                 FROM events
                                 WHERE event_date >= CAST(:today AS date)
                                 ORDER BY event_date ASC, event_time ASC NULLS LAST
                                 LIMIT 10",
                'uses_today' => true,
                'max_chars'  => 400,
                'html'       => false,
            ],
            'services' => [
                'label'     => 'SERVICES CURRENTLY AVAILABLE',
                'sql'       => "SELECT name, category, service_type, description, eligibility, processing_time,
                                       requirements, contact_info, max_capacity, current_count
                                FROM services
                                WHERE status = 'active'
                                ORDER BY name
                                LIMIT 30",
                'max_chars' => 500,
                'html'      => false,
            ],
            'discussions' => [
                'label'     => 'COMMUNITY DISCUSSION THREADS (newest first)',
                'sql'       => "SELECT t.subject, t.category, t.message, t.status, t.created_at
                                FROM threads t
                                WHERE t.is_removed = FALSE
                                  AND t.removed_by_user = FALSE
                                  AND t.is_flagged = FALSE
                                  AND NOT EXISTS (
                                      SELECT 1 FROM user_status us
                                      WHERE us.user_id = t.author_id AND (us.is_banned OR us.is_deleted)
                                  )
                                ORDER BY t.created_at DESC
                                LIMIT 15",
                'max_chars' => 300,
                'html'      => false,
            ],
        ];
    }

    private function fetchRows(string $sql, array $params): array
    {
        $statement = $this->pdo()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    private function pdo(): PDO
    {
        static $pdo = null;
        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $host = (string) AIConfig::get('DB_HOST', '');
        $port = (string) AIConfig::get('DB_PORT', '5432');
        $name = (string) AIConfig::get('DB_NAME', '');
        $user = (string) AIConfig::get('DB_USER', '');
        $pass = (string) AIConfig::get('DB_PASS', '');

        if ($host === '' || $name === '' || $user === '') {
            throw new RuntimeException('Database is not configured for the AI assistant (DB_HOST / DB_NAME / DB_USER).');
        }

        $pdo = new PDO(
            sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=require', $host, $port !== '' ? $port : '5432', $name),
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => true, 
                PDO::ATTR_TIMEOUT            => 8,
            ]
        );

        return $pdo;
    }

    private function formatRow(string $sourceKey, array $row, array $source): string
    {
        if ($sourceKey === 'about') {
            $row = [(string) ($row['key'] ?? '') => $row['value'] ?? null];
        } elseif ($sourceKey === 'services') {
            $row = $this->describeService($row);
        }

        $parts = [];
        foreach ($row as $column => $value) {
            if ($value === null || $value === '' || $column === '' || is_array($value)) {
                continue;
            }
            $text = self::clean((string) $value, $source['max_chars'], $source['html']);
            if ($text !== '') {
                $parts[] = $column . ': ' . $text;
            }
        }

        return $parts ? '- ' . implode(' | ', $parts) : '';
    }

    private function describeService(array $row): array
    {
        $types = [
            'document'    => 'apply online and upload documents',
            'appointment' => 'request-based (submit a request)',
            'info'        => 'information only, contact them directly',
        ];
        if (isset($row['service_type'])) {
            $row['how_to_get_it'] = $types[$row['service_type']] ?? $row['service_type'];
        }
        unset($row['service_type']);

        if (isset($row['max_capacity']) && $row['max_capacity'] !== null) {
            $row['slots_left'] = max(0, (int) $row['max_capacity'] - (int) ($row['current_count'] ?? 0));
        } else {
            $row['slots_left'] = 'unlimited';
        }
        unset($row['max_capacity'], $row['current_count']);

        return $row;
    }

    public static function clean(string $text, int $max = self::MAX_FIELD_CHARS, bool $isHtml = false): string
    {
        if ($isHtml) {
            $text = preg_replace('~</(p|div|li|ul|ol|tr|td|th|h[1-6]|table)>|<br\s*/?>~i', ' ', $text) ?? $text;
            $text = strip_tags($text);
        }

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';
        $text = str_replace(['<', '>'], ['‹', '›'], trim($text));

        return mb_strlen($text, 'UTF-8') > $max ? mb_substr($text, 0, $max, 'UTF-8') . '…' : $text;
    }
}