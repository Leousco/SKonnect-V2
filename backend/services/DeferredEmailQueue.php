<?php

class DeferredEmailQueue
{
    public static function enqueue(array $notification): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            error_log('Deferred email queue unavailable without an active session; using synchronous delivery.');
            return false;
        }
        if (empty($_SESSION['user_id'])) {
            error_log('Deferred email queue unavailable without an authenticated user; using synchronous delivery.');
            return false;
        }
        if (headers_sent($file, $line)) {
            error_log("Deferred email headers unavailable because output started at {$file}:{$line}; using synchronous delivery.");
            return false;
        }

        try {
            $token = bin2hex(random_bytes(32));
        } catch (Throwable $e) {
            error_log('Unable to create deferred email token: ' . $e->getMessage());
            return false;
        }

        $_SESSION['deferred_email_notifications'][$token] = [
            'user_id' => (int)($_SESSION['user_id'] ?? 0),
            'notification' => $notification,
        ];
        $_SESSION['deferred_email_notification_tokens'][] = $token;
        header(
            'X-SKonnect-Email-Dispatch: ' . implode(',', $_SESSION['deferred_email_notification_tokens'])
        );

        return true;
    }
}
