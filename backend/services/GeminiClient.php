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
     *         error is one of: '', 'rate_limited', 'unavailable', 'empty'
     */
    public function generate(string $systemInstruction, string $input): array
    {
        if ($this->apiKey === '') {
            error_log('Gemini: GEMINI_API_KEY is not configured.');
            return $this->fail('unavailable');
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
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => $body,
        ]);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        if (PHP_VERSION_ID < 80000) {
            curl_close($ch);
        }

        if ($response === false) {
            // On XAMPP/Windows an "SSL certificate problem" here means curl.cainfo is not set in php.ini.
            error_log('Gemini cURL error: ' . $curlError);
            return $this->fail('unavailable');
        }

        if ($status === 429) {
            error_log('Gemini HTTP 429: ' . mb_substr((string) $response, 0, 600));
            return $this->fail('rate_limited');
        }

        if ($status < 200 || $status >= 300) {
            error_log('Gemini HTTP ' . $status . ': ' . mb_substr((string) $response, 0, 500));
            return $this->fail('unavailable');
        }

        $data = json_decode((string) $response, true);
        $text = is_array($data) ? $this->extractText($data) : '';

        if ($text === '') {
            error_log('Gemini: no text found in response: ' . mb_substr((string) $response, 0, 800));
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
                if (is_array($part) && ($part['type'] ?? '') === 'text' && isset($part['text'])) {
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