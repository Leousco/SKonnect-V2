<?php
// backend/services/NotificationService.php

require_once __DIR__ . '/../models/NotificationModel.php';

/**
 * NotificationService
 *
 * Static helper class.  Every controller that needs to create a notification
 * calls one of these methods — no direct DB work in the callers.
 *
 * Usage (anywhere after session + DB are available):
 *   require_once __DIR__ . '/../services/NotificationService.php';
 *   NotificationService::notifyServiceStatus($residentId, $appId, 'approved', $serviceName);
 */
class NotificationService
{
    // ── SERVICE REQUEST ───────────────────────────────────────────────────────

    /**
     * Notify the resident when their service request status changes.
     * Supported statuses: 'approved' | 'rejected' | 'action_required'
     */
    public static function notifyServiceStatus(
        int    $residentId,
        int    $applicationId,
        string $newStatus,
        string $serviceName
    ): void {
        $model = new NotificationModel();
        $link  = "my_requests_page.php?id={$applicationId}";

        switch ($newStatus) {
            case 'approved':
                $model->create(
                    $residentId,
                    'service',
                    "Request Approved: {$serviceName}",
                    "Your request for \"{$serviceName}\" (REQ-#{$applicationId}) has been approved. "
                    . "Please check your request page for next steps and any additional instructions.",
                    'service_application', $applicationId, $link
                );
                break;

            case 'rejected':
                $model->create(
                    $residentId,
                    'service',
                    "Request Declined: {$serviceName}",
                    "Your request for \"{$serviceName}\" (REQ-#{$applicationId}) was not approved. "
                    . "Visit your request page to see the reason. You may be eligible to reapply.",
                    'service_application', $applicationId, $link
                );
                break;

            case 'action_required':
                $model->create(
                    $residentId,
                    'service',
                    "Action Required: {$serviceName}",
                    "An officer has added a note to your \"{$serviceName}\" request (REQ-#{$applicationId}) "
                    . "and is asking for additional information or documents. Please review and respond.",
                    'service_application', $applicationId, $link
                );
                break;
        }
    }

    // ── NEW ANNOUNCEMENT (broadcast) ──────────────────────────────────────────

    /**
     * Notify all verified residents when a new announcement is published.
     * @param string $snippet  Plain-text excerpt of the announcement content.
     */
    public static function notifyNewAnnouncement(
        int    $announcementId,
        string $title,
        string $snippet
    ): void {
        $model       = new NotificationModel();
        $residentIds = $model->getAllResidentIds();

        if (empty($residentIds)) return;

        $link    = "announcements_page.php?id={$announcementId}";
        $message = "A new announcement has been posted: \"{$title}\". {$snippet}";

        foreach ($residentIds as $uid) {
            $model->create(
                (int) $uid,
                'announcement',
                "New Announcement: {$title}",
                $message,
                'announcement', $announcementId, $link
            );
        }
    }

    // ── NEW SERVICE (broadcast) ───────────────────────────────────────────────

    /**
     * Notify all verified residents when a new service is published.
     */
    public static function notifyNewService(
        int    $serviceId,
        string $serviceName,
        string $category
    ): void {
        $model       = new NotificationModel();
        $residentIds = $model->getAllResidentIds();

        if (empty($residentIds)) return;

        $link  = "services_page.php?id={$serviceId}";
        $label = ucfirst(str_replace('_', ' ', $category));

        foreach ($residentIds as $uid) {
            $model->create(
                (int) $uid,
                'new_service',
                "New Service Available: {$serviceName}",
                "A new {$label} service \"{$serviceName}\" is now available on the portal. "
                . "Visit the Services page to view eligibility requirements and apply.",
                'service', $serviceId, $link
            );
        }
    }

    // ── THREAD COMMENT ────────────────────────────────────────────────────────

    /**
     * Notify the thread author when someone comments on their thread.
     * When $isMod = true, the notification is flagged as an official response.
     */
    public static function notifyThreadComment(
        int    $threadAuthorId,
        int    $commentAuthorId,
        string $commenterName,
        int    $threadId,
        string $threadSubject,
        string $commentSnippet,
        bool   $isMod = false
    ): void {
        if ($threadAuthorId === $commentAuthorId) return;

        $model   = new NotificationModel();
        $link    = "thread_view.php?id={$threadId}";
        $snippet = mb_strimwidth($commentSnippet, 0, 100, '…');

        if ($isMod) {
            $title   = "Official Response on Your Thread";
            $message = "A moderator ({$commenterName}) has officially responded to your thread "
                     . "\"{$threadSubject}\": \"{$snippet}\"";
        } else {
            $title   = "New Comment on Your Thread";
            $message = "{$commenterName} commented on your thread "
                     . "\"{$threadSubject}\": \"{$snippet}\"";
        }

        $model->create(
            $threadAuthorId,
            'thread',
            $title,
            $message,
            'thread', $threadId, $link,
            $isMod ? 1 : 0
        );
    }

    // ── COMMENT REPLY ─────────────────────────────────────────────────────────

    /**
     * Notify the comment author when someone replies to their comment.
     * When $isMod = true, the notification is flagged as an official response.
     */
    public static function notifyCommentReply(
        int    $commentAuthorId,
        int    $replyAuthorId,
        string $replierName,
        int    $threadId,
        string $threadSubject,
        string $replySnippet,
        bool   $isMod = false
    ): void {
        if ($commentAuthorId === $replyAuthorId) return;

        $model   = new NotificationModel();
        $link    = "thread_view.php?id={$threadId}";
        $snippet = mb_strimwidth($replySnippet, 0, 100, '…');

        if ($isMod) {
            $title   = "Official Reply to Your Comment";
            $message = "A moderator ({$replierName}) officially replied to your comment on "
                     . "\"{$threadSubject}\": \"{$snippet}\"";
        } else {
            $title   = "New Reply to Your Comment";
            $message = "{$replierName} replied to your comment on "
                     . "\"{$threadSubject}\": \"{$snippet}\"";
        }

        $model->create(
            $commentAuthorId,
            'thread',
            $title,
            $message,
            'thread', $threadId, $link,
            $isMod ? 1 : 0
        );
    }
}