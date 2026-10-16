<?php

require_once __DIR__ . '/AIConfig.php';

class GroqClient
{
    private const ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';

    private $apiKey;
    private $model;

    public function __construct()
    {
        $this->apiKey = (string) AIConfig::get('GROQ_API_KEY', '');
        // Keep the application fallback aligned with the model already configured locally.
        $this->model = (string) AIConfig::get('GROQ_MODEL', 'openai/gpt-oss-20b');
    }

    /**
     * @return array{ok: bool, text: string, error: string}
     *         error is one of: '', 'missing_credentials', 'transport',
     *         'rate_limited', 'server_error', 'client_error', 'upstream_error', 'empty'
     */
    public function generate(string $systemInstruction, string $input): array
    {
        if ($this->apiKey === '') {
            error_log('Groq request skipped: credentials are not configured.');
            return $this->fail('missing_credentials');
        }

        $body = json_encode([
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemInstruction],
                ['role' => 'user', 'content' => $input],
            ],
            'temperature' => 0.2,
            'max_completion_tokens' => 512,
            'stream' => false,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        if ($body === false) {
            error_log('Groq request could not be encoded.');
            return $this->fail('upstream_error');
        }

        $ch = curl_init(self::ENDPOINT);
        if ($ch === false) {
            error_log('Groq request could not be initialized.');
            return $this->fail('transport');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => $body,
        ]);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (PHP_VERSION_ID < 80000) {
            curl_close($ch);
        }

        if ($response === false) {
            error_log('Groq transport failure.');
            return $this->fail('transport');
        }

        if ($status === 429) {
            error_log('Groq returned HTTP 429.');
            return $this->fail('rate_limited');
        }

        if ($status < 200 || $status >= 300) {
            error_log('Groq returned HTTP ' . $status . '.');
            if ($status >= 500) {
                return $this->fail('server_error');
            }
            if ($status >= 400) {
                return $this->fail('client_error');
            }
            return $this->fail('upstream_error');
        }

        $data = json_decode((string) $response, true);
        $text = '';
        if (is_array($data)) {
            $content = $data['choices'][0]['message']['content'] ?? '';
            if (is_string($content)) {
                $text = trim($content);
            }
        }

        if ($text === '') {
            error_log('Groq returned no usable text.');
            return $this->fail('empty');
        }

        return ['ok' => true, 'text' => $text, 'error' => ''];
    }

    private function fail(string $error): array
    {
        return ['ok' => false, 'text' => '', 'error' => $error];
    }
}
