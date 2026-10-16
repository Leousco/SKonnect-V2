<?php

require_once __DIR__ . '/AIConfig.php';

class GeminiClient
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/interactions';

    private $apiKey;
    private $model;

    public function __construct()
    {
        $this->apiKey = (string) AIConfig::get('GEMINI_API_KEY', '');
        $this->model = (string) AIConfig::get('GEMINI_MODEL', 'gemini-3.8-flash');
    }

    /**
     * @return array{ok: bool, text: string, error: string}
     *         error is one of: '', 'missing_credentials', 'transport',
     *         'timeout', 'rate_limited', 'server_error', 'client_error', 'upstream_error', 'empty'
     */
    public function generate(string $systemInstruction, string $input): array
    {
        if ($this->apiKey === '') {
            error_log('Gemini request skipped: credentials are not configured.');
            return $this->fail('missing_credentials');
        }

        $body = json_encode([
            'model' => $this->model,
            'system_instruction' => $systemInstruction,
            'input' => $input,
            'store' => false, 
            'generation_config' => [
                'temperature' => 0.2, // low = sticks to the supplied data
                'max_output_tokens' => 1024,
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => $body,
        ]);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (PHP_VERSION_ID < 80000) {
            curl_close($ch);
        }

        if ($response === false) {
            // Do not log cURL details; keep provider diagnostics free of request or credential data.
            error_log('Gemini transport failure.');
            return $this->fail('transport');
        }

        if ($status === 429) {
            error_log('Gemini returned HTTP 429.');
            return $this->fail('rate_limited');
        }

        if ($status === 408) {
            error_log('Gemini returned HTTP 408.');
            return $this->fail('timeout');
        }

        if ($status < 200 || $status >= 300) {
            error_log('Gemini returned HTTP ' . $status . '.');
            if ($status >= 500) {
                return $this->fail('server_error');
            }
            if ($status >= 400) {
                return $this->fail('client_error');
            }
            return $this->fail('upstream_error');
        }

        $data = json_decode((string) $response, true);
        $text = is_array($data) ? $this->extractText($data) : '';

        if ($text === '') {
            error_log('Gemini returned no usable text.');
            return $this->fail('empty');
        }

        return ['ok' => true, 'text' => $text, 'error' => ''];
    }

    private function extractText(array $data): string
    {
        $text = '';
        $steps = isset($data['steps']) && is_array($data['steps']) ? $data['steps'] : [];

        foreach ($steps as $step) {
            if (!is_array($step) || ($step['type'] ?? '') !== 'model_output') {
                continue;
            }
            $parts = isset($step['content']) && is_array($step['content']) ? $step['content'] : [];
            foreach ($parts as $part) {
                if (
                    is_array($part) &&
                    ($part['type'] ?? '') === 'text' &&
                    isset($part['text']) &&
                    is_string($part['text'])
                ) {
                    $text .= $part['text'];
                }
            }
        }

        return trim($text);
    }

    private function fail(string $error): array
    {
        return ['ok' => false, 'text' => '', 'error' => $error];
    }
}
