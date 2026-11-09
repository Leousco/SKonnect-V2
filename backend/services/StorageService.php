<?php
require_once __DIR__ . '/AIConfig.php';

class StorageService
{
    private const BUCKET = 'thread-images';

    private static function config(string $key): string
    {
        $value = (string) AIConfig::get($key, '');
        if ($value === '') {
            $env = getenv($key);
            $value = ($env !== false) ? (string) $env : '';
        }
        return $value;
    }

    /**
     * Uploads a local file to Supabase Storage.
     * Returns the public URL, or throws RuntimeException on failure.
     */
    public static function uploadImage(string $tmpPath, string $mime, string $prefix = ''): string
    {
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($extensions[$mime])) {
            throw new RuntimeException('Unsupported image type.');
        }

        $base = rtrim(self::config('SUPABASE_URL'), '/');
        $key  = self::config('SUPABASE_SERVICE_KEY');
        if ($base === '' || $key === '') {
            throw new RuntimeException('Storage is not configured. Set SUPABASE_URL and SUPABASE_SERVICE_KEY.');
        }

        $name = $prefix . bin2hex(random_bytes(12)) . '.' . $extensions[$mime];

        $body = file_get_contents($tmpPath);
        if ($body === false) {
            throw new RuntimeException('Unable to read the uploaded image.');
        }

        $ch = curl_init("{$base}/storage/v1/object/" . self::BUCKET . "/{$name}");
        if ($ch === false) {
            throw new RuntimeException('Unable to initialize the storage request.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer {$key}",
                "apikey: {$key}",
                "Content-Type: {$mime}",
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException("Storage request failed: {$error}");
        }
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException("Storage error (HTTP {$status}): {$response}");
        }

        return "{$base}/storage/v1/object/public/" . self::BUCKET . "/{$name}";
    }
}