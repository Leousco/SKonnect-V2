<?php
require_once __DIR__ . '/AIConfig.php';

class StorageService
{
    private const BUCKET = 'thread-images';
    private const BANNER_BUCKET = 'announcement-banners';

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
        return self::uploadToStorage($tmpPath, $mime, $prefix, self::BUCKET);
    }

    /**
     * Uploads an announcement banner to Supabase Storage.
     * Returns the public URL, or throws RuntimeException on failure.
     */
    public static function uploadAnnouncementBanner(string $tmpPath, string $mime, string $prefix = ''): string
    {
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        if (!isset($extensions[$mime])) {
            throw new RuntimeException('Unsupported image type. Only JPEG, PNG, WebP, and GIF are allowed.');
        }
        return self::uploadToStorage($tmpPath, $mime, $prefix, self::BANNER_BUCKET);
    }

    /**
     * Generic upload method to Supabase Storage bucket.
     * Returns the public URL, or throws RuntimeException on failure.
     */
    private static function uploadToStorage(string $tmpPath, string $mime, string $prefix, string $bucket): string
    {
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
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

        $ch = curl_init("{$base}/storage/v1/object/{$bucket}/{$name}");
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

        return "{$base}/storage/v1/object/public/{$bucket}/{$name}";
    }

    /**
     * Deletes a file from Supabase Storage.
     * Returns true on success, or throws RuntimeException on failure.
     */
    public static function deleteFile(string $url): bool
    {
        $base = rtrim(self::config('SUPABASE_URL'), '/');
        $key  = self::config('SUPABASE_SERVICE_KEY');
        
        if ($base === '' || $key === '') {
            throw new RuntimeException('Storage is not configured.');
        }

        // Extract bucket and file path from URL
        // URL format: https://...supabase.co/storage/v1/object/public/{bucket}/{filename}
        if (!preg_match('#/storage/v1/object/public/([^/]+)/(.+)$#', $url, $matches)) {
            throw new RuntimeException('Invalid storage URL format.');
        }

        $bucket = $matches[1];
        $filename = $matches[2];

        $ch = curl_init("{$base}/storage/v1/object/{$bucket}/{$filename}");
        if ($ch === false) {
            throw new RuntimeException('Unable to initialize delete request.');
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer {$key}",
                "apikey: {$key}",
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);
        
        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException("Delete request failed: {$error}");
        }
        if ($status < 200 || $status >= 300) {
            // Log but don't throw - file might already be deleted
            error_log("Storage delete warning (HTTP {$status}): {$response}");
        }

        return true;
    }
}