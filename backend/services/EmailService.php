<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService
{

    private $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
    }

    /* ── PRIVATE: SMTP SETUP ───────────────────────────────────── */

    private function configureSMTP(): void
    {
        $this->mail = new PHPMailer(true);
        $this->mail->SMTPDebug = 0;
        $this->mail->isSMTP();
        $this->mail->Host       = 'smtp.gmail.com';
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = 'skonnect.system@gmail.com';
        $this->mail->Password   = 'dktl mpvg fwfu hqnt';
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port       = 587;
        $this->mail->setFrom('skonnect.system@gmail.com', 'SKonnect - Barangay Sauyo');
    }

    /* ── PRIVATE: SHARED EMAIL WRAPPER ────────────────────────── */

    /**
     * Builds and sends a styled notification email.
     *
     * @param string $email       Recipient email
     * @param string $name        Recipient display name
     * @param string $subject     Email subject line
     * @param string $badge       Small label above the title (e.g. "New Comment")
     * @param string $badgeColor  CSS hex color for the badge text
     * @param string $title       Main heading inside the card
     * @param string $bodyHtml    Body paragraph(s) as HTML
     * @param string $bodyPlain   Plain-text fallback body
     * @param string $ctaLabel    Call-to-action button label (pass '' to omit)
     * @param string $ctaUrl      Call-to-action button URL
     */
    private function sendNotification(
        string $email,
        string $name,
        string $subject,
        string $badge,
        string $badgeColor,
        string $title,
        string $bodyHtml,
        string $bodyPlain,
        string $ctaLabel = 'View Thread',
        string $ctaUrl   = 'http://localhost/SKonnect/views/portal/feed.php'
    ): bool {
        try {
            $this->configureSMTP();
            $this->mail->addAddress($email, $name);
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;

            $ctaButton = $ctaLabel
                ? "<a href='{$ctaUrl}'
                      style='display:inline-block;background:#facc15;color:#0f2545;font-weight:700;
                             font-size:13px;padding:11px 22px;border-radius:8px;text-decoration:none;
                             margin-top:4px;'>
                       {$ctaLabel}
                   </a>"
                : '';

            $this->mail->Body = "
                <div style='font-family:Segoe UI,sans-serif;max-width:600px;margin:auto;
                            background:#f4f6f9;padding:24px;border-radius:12px;'>
                    <div style='background:linear-gradient(135deg,#0f2545,#1e5fa8);border-radius:10px;
                                padding:28px 32px;border-left:5px solid #facc15;'>
                        <p style='color:{$badgeColor};font-size:11px;font-weight:700;
                                  text-transform:uppercase;letter-spacing:1px;margin:0 0 8px;'>
                            {$badge}
                        </p>
                        <h2 style='color:#ffffff;font-size:20px;font-weight:800;
                                   margin:0 0 12px;line-height:1.3;'>
                            {$title}
                        </h2>
                        <div style='color:rgba(255,255,255,0.80);font-size:13.5px;
                                    line-height:1.7;margin:0 0 20px;'>
                            {$bodyHtml}
                        </div>
                        {$ctaButton}
                    </div>
                    <p style='color:#94a3b8;font-size:11px;text-align:center;margin-top:20px;'>
                        You received this because you are a registered resident of Barangay Sauyo.<br>
                        SKonnect &mdash; Sangguniang Kabataan Portal
                    </p>
                </div>
            ";
            $this->mail->AltBody = "{$subject}\n\n{$bodyPlain}\n\nVisit: {$ctaUrl}";

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("EmailService error [{$subject}] for {$email}: " . $this->mail->ErrorInfo);
            return false;
        }
    }

    /* ── OTP VERIFICATION EMAIL ────────────────────────────────── */

    public function sendOTP(string $email, string $otp, string $name): bool
    {
        try {
            $this->configureSMTP();
            $this->mail->addAddress($email, $name);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'SKonnect - Email Verification OTP';
            $this->mail->Body    = "Your SKonnect verification OTP is: <strong>{$otp}</strong><br><br>This code will expire in 10 minutes.";
            $this->mail->AltBody = "Your SKonnect verification OTP is: {$otp}\n\nThis code will expire in 10 minutes.";
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $this->mail->ErrorInfo);
            return false;
        }
    }

    /* ── ANNOUNCEMENT NOTIFICATION EMAIL ──────────────────────── */

    public function sendAnnouncementNotification(string $email, string $name, array $announcement): bool
    {
        $title    = htmlspecialchars($announcement['title']);
        $category = ucfirst(htmlspecialchars($announcement['category']));
        $excerpt  = htmlspecialchars(mb_substr(strip_tags($announcement['content']), 0, 200));
        $date     = date('F j, Y', strtotime($announcement['published_at']));

        return $this->sendNotification(
            email: $email,
            name: $name,
            subject: "New Announcement: {$title}",
            badge: "New Announcement — {$category}",
            badgeColor: '#facc15',
            title: $title,
            bodyHtml: "<p>{$excerpt}...</p><p style='color:rgba(255,255,255,0.5);font-size:11.5px;'>Posted on {$date}</p>",
            bodyPlain: "{$excerpt}...\n\nPosted on {$date}",
            ctaLabel: 'View Announcement',
            ctaUrl: 'http://localhost/SKonnect/views/public/announcement_view.php'
        );
    }

    /* ── THREAD NOTIFICATION EMAILS ───────────────────────────── */

    /**
     * Notify thread author that a moderator posted a comment on their thread.
     *
     * @param string $email         Author's email
     * @param string $name          Author's full name
     * @param string $threadSubject The thread subject/title
     * @param string $commentSnippet First ~180 chars of the moderator's comment
     */
    public function sendModCommentNotification(
        string $email,
        string $name,
        string $threadSubject,
        string $commentSnippet
    ): bool {
        $subject = htmlspecialchars($threadSubject);
        $snippet = htmlspecialchars(mb_substr($commentSnippet, 0, 180));

        return $this->sendNotification(
            email: $email,
            name: $name,
            subject: "An SK Official commented on your thread",
            badge: 'SK Official Comment',
            badgeColor: '#60a5fa',
            title: "New comment on &ldquo;{$subject}&rdquo;",
            bodyHtml: "<p>An SK Official has left a comment on your thread:</p>
                         <blockquote style='border-left:3px solid #facc15;margin:12px 0;
                                            padding:8px 14px;color:rgba(255,255,255,0.7);
                                            font-style:italic;'>
                             &ldquo;{$snippet}&rdquo;
                         </blockquote>
                         <p>Log in to view the full comment and respond.</p>",
            bodyPlain: "An SK Official commented on your thread \"{$threadSubject}\":\n\n\"{$snippet}\"\n\nLog in to view and respond."
        );
    }

    public function sendModCommentRespondedNotification(
        string $email,
        string $name,
        string $threadSubject,
        string $commentSnippet
    ): bool {
        $subject = htmlspecialchars($threadSubject);
        $snippet = htmlspecialchars(mb_substr($commentSnippet, 0, 180));

        return $this->sendNotification(
            email: $email,
            name: $name,
            subject: "An SK Official responded to your thread",
            badge: 'SK Official · Thread Responded',
            badgeColor: '#60a5fa',
            title: "Your thread &ldquo;{$subject}&rdquo; has been responded to",
            bodyHtml: "<p>An SK Official has left a comment on your thread.</p>
                         <blockquote style='border-left:3px solid #facc15;margin:12px 0;
                                            padding:8px 14px;color:rgba(255,255,255,0.7);
                                            font-style:italic;'>
                             &ldquo;{$snippet}&rdquo;
                         </blockquote>
                         <p>Log in to view the full comment and follow the conversation.</p>",
            bodyPlain: "An SK Official responded to your thread \"{$threadSubject}\".\n\nComment:\n\"{$snippet}\"\n\nYour thread status has been updated to: Responded.\n\nLog in to view the full comment."
        );
    }

    /**
     * Notify thread author that a moderator replied to a comment on their thread.
     *
     * @param string $email         Author's email
     * @param string $name          Author's full name
     * @param string $threadSubject The thread subject/title
     * @param string $replySnippet  First ~180 chars of the moderator's reply
     */
    public function sendModReplyNotification(
        string $email,
        string $name,
        string $threadSubject,
        string $replySnippet
    ): bool {
        $subject = htmlspecialchars($threadSubject);
        $snippet = htmlspecialchars(mb_substr($replySnippet, 0, 180));

        return $this->sendNotification(
            email: $email,
            name: $name,
            subject: "A moderator replied on your thread",
            badge: 'Moderator Reply',
            badgeColor: '#60a5fa',
            title: "New reply on &ldquo;{$subject}&rdquo;",
            bodyHtml: "<p>A moderator has replied to a comment on your thread:</p>
                         <blockquote style='border-left:3px solid #facc15;margin:12px 0;
                                            padding:8px 14px;color:rgba(255,255,255,0.7);
                                            font-style:italic;'>
                             &ldquo;{$snippet}&rdquo;
                         </blockquote>
                         <p>Log in to view the full reply and follow the conversation.</p>",
            bodyPlain: "A moderator replied on your thread \"{$threadSubject}\":\n\n\"{$snippet}\"\n\nLog in to view the full reply."
        );
    }

    /**
     * Notify thread author of a status change (responded / resolved / pending).
     *
     * @param string $email         Author's email
     * @param string $name          Author's full name
     * @param string $threadSubject The thread subject/title
     * @param string $newStatus     The new status string
     */
    public function sendStatusChangeNotification(
        string $email,
        string $name,
        string $threadSubject,
        string $newStatus
    ): bool {
        $subject       = htmlspecialchars($threadSubject);
        $statusLabels  = [
            'pending'    => ['label' => 'Pending',    'color' => '#94a3b8', 'note' => 'Your thread is under review by moderators.'],
            'responded'  => ['label' => 'Responded',  'color' => '#60a5fa', 'note' => 'A moderator has responded to your thread. Log in to view their response.'],
            'resolved'   => ['label' => 'Resolved',   'color' => '#4ade80', 'note' => 'Your thread has been marked as resolved by a moderator.'],
        ];
        $info = $statusLabels[$newStatus] ?? ['label' => ucfirst($newStatus), 'color' => '#facc15', 'note' => ''];

        return $this->sendNotification(
            email: $email,
            name: $name,
            subject: "Your thread status has been updated to \"{$info['label']}\"",
            badge: 'Thread Status Update',
            badgeColor: $info['color'],
            title: "&ldquo;{$subject}&rdquo; is now {$info['label']}",
            bodyHtml: "<p>{$info['note']}</p>",
            bodyPlain: "Your thread \"{$threadSubject}\" has been updated to status: {$info['label']}.\n\n{$info['note']}"
        );
    }

    /**
     * Notify thread author that their thread was pinned or unpinned.
     *
     * @param string $email         Author's email
     * @param string $name          Author's full name
     * @param string $threadSubject The thread subject/title
     * @param bool   $isPinned      true = pinned, false = unpinned
     */
    public function sendPinStatusNotification(
        string $email,
        string $name,
        string $threadSubject,
        bool $isPinned
    ): bool {
        $subject = htmlspecialchars($threadSubject);
        $action  = $isPinned ? 'pinned' : 'unpinned';
        $note    = $isPinned
            ? 'Your thread has been pinned by a moderator and will appear at the top of the community feed.'
            : 'Your thread has been unpinned by a moderator and will appear in the regular feed order.';

        return $this->sendNotification(
            email: $email,
            name: $name,
            subject: "Your thread has been {$action}",
            badge: $isPinned ? '📌 Thread Pinned' : 'Thread Unpinned',
            badgeColor: $isPinned ? '#facc15' : '#94a3b8',
            title: "&ldquo;{$subject}&rdquo; has been {$action}",
            bodyHtml: "<p>{$note}</p>",
            bodyPlain: "Your thread \"{$threadSubject}\" has been {$action}.\n\n{$note}"
        );
    }

    /**
     * Notify thread author that their thread was removed or restored.
     *
     * @param string $email         Author's email
     * @param string $name          Author's full name
     * @param string $threadSubject The thread subject/title
     * @param bool   $isRemoved     true = removed/hidden, false = restored
     */
    public function sendRemovalStatusNotification(
        string $email,
        string $name,
        string $threadSubject,
        bool $isRemoved
    ): bool {
        $subject = htmlspecialchars($threadSubject);
        $action  = $isRemoved ? 'removed' : 'restored';
        $note    = $isRemoved
            ? 'Your thread has been temporarily hidden from the community feed by a moderator pending review. If you believe this is a mistake, please contact a moderator.'
            : 'Your thread has been restored to the community feed by a moderator and is now publicly visible again.';

        return $this->sendNotification(
            email: $email,
            name: $name,
            subject: "Your thread has been {$action}",
            badge: $isRemoved ? '⚠️ Thread Removed' : '✅ Thread Restored',
            badgeColor: $isRemoved ? '#f87171' : '#4ade80',
            title: "&ldquo;{$subject}&rdquo; has been {$action}",
            bodyHtml: "<p>{$note}</p>",
            bodyPlain: "Your thread \"{$threadSubject}\" has been {$action}.\n\n{$note}",
            ctaLabel: $isRemoved ? '' : 'View Thread',
        );
    }

    public function sendWarningNotification($email, $name, $reason, $threadSubject)
    {
        $subject = "Community Notice: Warning Regarding Your $threadSubject";

        $message = "
        <html>
        <head>
            <title>SKonnect Community Notice</title>
        </head>
        <body>
            <h2>Hello, $name</h2>
            <p>This is a formal warning from the SKonnect Moderation Team.</p>
            <p>Your recent <strong>$threadSubject</strong> was reported and reviewed by our team.</p>
            <p><strong>Reason for Warning:</strong> $reason</p>
            <hr>
            <p>Please review our community guidelines to avoid further sanctions or account suspension.</p>
            <p>Best regards,<br>SKonnect Team</p>
        </body>
        </html>
        ";

        // Set content-type header for sending HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: noreply@skonnect-qcu.com" . "\r\n";

        // The mail() function returns true if accepted for delivery
        return mail($email, $subject, $message, $headers);
    }

    public function sendSanctionNotification(
        string  $email,
        string  $name,
        int     $level,
        string  $reason          = '',   
        ?string $reportedContent = null, 
        ?string $threadSubject   = null  
    ): bool {
        $safeContent = $reportedContent
            ? htmlspecialchars(mb_substr($reportedContent, 0, 400))
            : null;

        $safeThread  = $threadSubject
            ? htmlspecialchars($threadSubject)
            : null;

        $safeReason  = $reason ? htmlspecialchars($reason) : null;

        // ── Build the "your message" block ───────────────────────
        $contentBlock = '';
        if ($safeContent) {
            $contextLine = $safeThread
                ? "<p style='color:rgba(255,255,255,0.55);font-size:11px;margin:0 0 6px;'>
                       From thread: <em>{$safeThread}</em>
                   </p>"
                : '';

            $contentBlock = "
                <p>The following message you posted was reviewed by a moderator and resulted in this sanction:</p>
                {$contextLine}
                <blockquote style='border-left:3px solid #fbbf24;margin:12px 0;
                                   padding:10px 14px;background:rgba(255,255,255,0.06);
                                   border-radius:0 6px 6px 0;
                                   color:rgba(255,255,255,0.85);font-style:italic;
                                   font-size:13.5px;line-height:1.6;'>
                    &ldquo;{$safeContent}&rdquo;
                </blockquote>";
        } else {
            $contentBlock = "<p>A moderator has reviewed your activity in the community feed.</p>";
        }

        // ── Optional moderator note ───────────────────────────────
        $reasonBlock = $safeReason
            ? "<p style='margin-top:14px;font-size:12.5px;color:rgba(255,255,255,0.6);'>
                   <strong style='color:rgba(255,255,255,0.8);'>Moderator note:</strong>
                   {$safeReason}
               </p>"
            : '';

        // ── Level-specific content ────────────────────────────────
        $levelMeta = [
            1 => [
                'badge'      => '⚠️ Community Warning — Level 1',
                'badgeColor' => '#fbbf24',
                'title'      => 'You have received a warning',
                'bodyHtml'   => "{$contentBlock}
                                 <p style='margin-top:12px;'>Please review our community guidelines to avoid further sanctions.
                                 Continued violations may result in a posting ban.</p>
                                 {$reasonBlock}",
                'bodyPlain'  => "A Level 1 Warning has been issued on your account.\n\n"
                    . ($reportedContent ? "Reported message:\n\"{$reportedContent}\"\n\n" : "")
                    . ($threadSubject   ? "Thread: {$threadSubject}\n\n" : "")
                    . "Please review our community guidelines.\n"
                    . ($reason ? "Moderator note: {$reason}" : ""),
                'subject'    => 'SKonnect: Community Warning Issued',
            ],
            2 => [
                'badge'      => '🚫 7-Day Posting Ban — Level 2',
                'badgeColor' => '#fb923c',
                'title'      => 'Your posting privileges have been suspended for 7 days',
                'bodyHtml'   => "{$contentBlock}
                                 <p style='margin-top:12px;'>Due to this violation, your account has been issued a
                                 <strong>7-Day Posting Ban</strong>. During this period you can still
                                 <strong>view</strong> threads and posts, but you cannot create threads,
                                 comment, or reply.</p>
                                 <p>Your posting privileges will be automatically restored after 7 days.</p>
                                 {$reasonBlock}",
                'bodyPlain'  => "A 7-Day Posting Ban has been issued on your account.\n\n"
                    . ($reportedContent ? "Reported message:\n\"{$reportedContent}\"\n\n" : "")
                    . ($threadSubject   ? "Thread: {$threadSubject}\n\n" : "")
                    . "You may still view content but cannot post, comment, or reply for 7 days.\n"
                    . ($reason ? "Moderator note: {$reason}" : ""),
                'subject'    => 'SKonnect: 7-Day Posting Ban Issued',
            ],
            3 => [
                'badge'      => '⛔ Permanent Community Ban — Level 3',
                'badgeColor' => '#f87171',
                'title'      => 'Your account has been permanently banned from the community feed',
                'bodyHtml'   => "{$contentBlock}
                                 <p style='margin-top:12px;'>Due to serious and repeated violations of our community
                                 guidelines, your account has been issued a <strong>Permanent Community Ban</strong>.
                                 You are permanently restricted from creating threads, commenting, or replying.</p>
                                 <p>If you believe this is in error, please contact the barangay office directly.</p>
                                 {$reasonBlock}",
                'bodyPlain'  => "A Permanent Community Ban has been issued on your account.\n\n"
                    . ($reportedContent ? "Reported message:\n\"{$reportedContent}\"\n\n" : "")
                    . ($threadSubject   ? "Thread: {$threadSubject}\n\n" : "")
                    . "You are permanently restricted from interacting with the community feed.\n"
                    . ($reason ? "Moderator note: {$reason}" : ""),
                'subject'    => 'SKonnect: Permanent Community Ban Issued',
            ],
        ];

        $meta = $levelMeta[$level] ?? $levelMeta[1];

        return $this->sendNotification(
            email: $email,
            name: $name,
            subject: $meta['subject'],
            badge: $meta['badge'],
            badgeColor: $meta['badgeColor'],
            title: $meta['title'],
            bodyHtml: $meta['bodyHtml'],
            bodyPlain: $meta['bodyPlain'],
            ctaLabel: '',
            ctaUrl: ''
        );
    }
/* ── SERVICE REQUEST NOTIFICATIONS ────────────────────────── */

    /**
     * Notify resident that their service request was successfully submitted.
     */
    public function sendRequestSubmitted(
        string $email,
        string $name,
        string $serviceName,
        int    $applicationId
    ): bool {
        $safe    = htmlspecialchars($serviceName);
        $appCode = str_pad((string)$applicationId, 6, '0', STR_PAD_LEFT);

        return $this->sendNotification(
            email:      $email,
            name:       $name,
            subject:    "SKonnect: Service Request Submitted — {$safe}",
            badge:      '✅ Request Submitted',
            badgeColor: '#4ade80',
            title:      "Your request has been received!",
            bodyHtml:   "<p>Hi <strong>{$name}</strong>,</p>
                         <p>We've successfully received your service request for <strong>{$safe}</strong>
                         (Ref&nbsp;#&nbsp;<strong>{$appCode}</strong>).</p>
                         <p>An SK Officer will review your application. You will be notified of any
                         updates via email. You can also track the status of your request anytime
                         through your SKonnect portal.</p>",
            bodyPlain:  "Hi {$name},\n\nYour service request for \"{$safe}\" (Ref # {$appCode}) has been received.\n\nAn SK Officer will review your application and you will be notified of any updates.",
            ctaLabel:   'Track My Request',
            ctaUrl:     'http://localhost/SKonnect/views/portal/my_requests_page.php'
        );
    }

    /**
     * Notify resident that they cancelled their service request.
     */
    public function sendRequestCancelled(
        string $email,
        string $name,
        string $serviceName,
        int    $applicationId
    ): bool {
        $safe    = htmlspecialchars($serviceName);
        $appCode = str_pad((string)$applicationId, 6, '0', STR_PAD_LEFT);

        return $this->sendNotification(
            email:      $email,
            name:       $name,
            subject:    "SKonnect: Service Request Cancelled — {$safe}",
            badge:      '🚫 Request Cancelled',
            badgeColor: '#f87171',
            title:      "Your request has been cancelled.",
            bodyHtml:   "<p>Hi <strong>{$name}</strong>,</p>
                         <p>This confirms that your service request for <strong>{$safe}</strong>
                         (Ref&nbsp;#&nbsp;<strong>{$appCode}</strong>) has been <strong>cancelled</strong>
                         as per your request.</p>
                         <p>If this was a mistake or you wish to apply again in the future, you may
                         submit a new request through your SKonnect portal.</p>",
            bodyPlain:  "Hi {$name},\n\nYour service request for \"{$safe}\" (Ref # {$appCode}) has been cancelled.\n\nIf this was a mistake, you may submit a new request through your SKonnect portal.",
            ctaLabel:   'Go to My Requests',
            ctaUrl:     'http://localhost/SKonnect/views/portal/my_requests_page.php'
        );
    }

    /**
     * Notify resident that their application requires action (officer sent a note).
     */
    public function sendActionRequired(
        string $email,
        string $name,
        string $serviceName,
        int    $applicationId,
        string $officerNote
    ): bool {
        $safe     = htmlspecialchars($serviceName);
        $appCode  = str_pad((string)$applicationId, 6, '0', STR_PAD_LEFT);
        $safeNote = htmlspecialchars($officerNote);

        return $this->sendNotification(
            email:      $email,
            name:       $name,
            subject:    "SKonnect: Action Required — {$safe}",
            badge:      '⚠️ Action Required',
            badgeColor: '#fbbf24',
            title:      "Your application needs your attention.",
            bodyHtml:   "<p>Hi <strong>{$name}</strong>,</p>
                         <p>An SK Officer has reviewed your service request for <strong>{$safe}</strong>
                         (Ref&nbsp;#&nbsp;<strong>{$appCode}</strong>) and requires additional
                         action or information from you.</p>
                         <p><strong>Officer's Message:</strong></p>
                         <blockquote style='border-left:3px solid #fbbf24;margin:12px 0;
                                            padding:10px 14px;background:rgba(255,255,255,0.06);
                                            border-radius:0 6px 6px 0;
                                            color:rgba(255,255,255,0.85);font-style:italic;
                                            font-size:13.5px;line-height:1.6;'>
                             {$safeNote}
                         </blockquote>
                         <p>Please log in to your SKonnect portal to review and respond to this request.</p>",
            bodyPlain:  "Hi {$name},\n\nYour service request for \"{$safe}\" (Ref # {$appCode}) requires your attention.\n\nOfficer's message:\n\"{$officerNote}\"\n\nPlease log in to your SKonnect portal to respond.",
            ctaLabel:   'Respond Now',
            ctaUrl:     'http://localhost/SKonnect/views/portal/my_requests_page.php'
        );
    }

    /**
     * Notify resident that their application has been approved.
     * If a fulfillment file was attached, we do NOT send it — instead we
     * prompt the resident to pick it up via the portal.
     */
    public function sendRequestApproved(
        string $email,
        string $name,
        string $serviceName,
        int    $applicationId,
        string $approvalMessage,
        bool   $hasFulfillmentFile = false
    ): bool {
        $safe     = htmlspecialchars($serviceName);
        $appCode  = str_pad((string)$applicationId, 6, '0', STR_PAD_LEFT);
        $safeMsg  = htmlspecialchars($approvalMessage);

        $fileBlock = $hasFulfillmentFile
            ? "<p style='margin-top:14px;padding:10px 14px;background:rgba(250,204,21,0.12);
                          border-left:3px solid #facc15;border-radius:0 6px 6px 0;
                          font-size:13px;color:rgba(255,255,255,0.9);'>
                   📎 <strong>A file has been prepared for you.</strong> Please visit your
                   SKonnect portal to view and download your document.
               </p>"
            : '';

        $approvalBlock = $safeMsg
            ? "<p><strong>Message from the SK Officer:</strong></p>
               <blockquote style='border-left:3px solid #4ade80;margin:12px 0;
                                  padding:10px 14px;background:rgba(255,255,255,0.06);
                                  border-radius:0 6px 6px 0;
                                  color:rgba(255,255,255,0.85);font-style:italic;
                                  font-size:13.5px;line-height:1.6;'>
                   {$safeMsg}
               </blockquote>"
            : '';

        return $this->sendNotification(
            email:      $email,
            name:       $name,
            subject:    "SKonnect: Your Request Has Been Approved — {$safe}",
            badge:      '🎉 Request Approved',
            badgeColor: '#4ade80',
            title:      "Great news — your request has been approved!",
            bodyHtml:   "<p>Hi <strong>{$name}</strong>,</p>
                         <p>Your service request for <strong>{$safe}</strong>
                         (Ref&nbsp;#&nbsp;<strong>{$appCode}</strong>) has been
                         <strong>approved</strong> by an SK Officer.</p>
                         {$approvalBlock}
                         {$fileBlock}",
            bodyPlain:  "Hi {$name},\n\nYour service request for \"{$safe}\" (Ref # {$appCode}) has been APPROVED.\n\n"
                . ($approvalMessage ? "Officer's message:\n\"{$approvalMessage}\"\n\n" : "")
                . ($hasFulfillmentFile ? "A file has been prepared for you. Please visit your SKonnect portal to view and download it.\n" : ""),
            ctaLabel:   'View My Request',
            ctaUrl:     'http://localhost/SKonnect/views/portal/my_requests_page.php'
        );
    }

    /**
     * Notify resident that their application has been rejected.
     */
    public function sendRequestRejected(
        string $email,
        string $name,
        string $serviceName,
        int    $applicationId,
        string $reason
    ): bool {
        $safe       = htmlspecialchars($serviceName);
        $appCode    = str_pad((string)$applicationId, 6, '0', STR_PAD_LEFT);
        $safeReason = htmlspecialchars($reason);

        return $this->sendNotification(
            email:      $email,
            name:       $name,
            subject:    "SKonnect: Your Request Was Not Approved — {$safe}",
            badge:      '❌ Request Rejected',
            badgeColor: '#f87171',
            title:      "Your service request was not approved.",
            bodyHtml:   "<p>Hi <strong>{$name}</strong>,</p>
                         <p>After review, your service request for <strong>{$safe}</strong>
                         (Ref&nbsp;#&nbsp;<strong>{$appCode}</strong>) has been
                         <strong>declined</strong>.</p>
                         <p><strong>Reason:</strong></p>
                         <blockquote style='border-left:3px solid #f87171;margin:12px 0;
                                            padding:10px 14px;background:rgba(255,255,255,0.06);
                                            border-radius:0 6px 6px 0;
                                            color:rgba(255,255,255,0.85);font-style:italic;
                                            font-size:13.5px;line-height:1.6;'>
                             {$safeReason}
                         </blockquote>
                         <p>If you believe this decision was made in error, or if you would like
                         to apply again with the correct information, please visit your SKonnect
                         portal.</p>",
            bodyPlain:  "Hi {$name},\n\nYour service request for \"{$safe}\" (Ref # {$appCode}) has been DECLINED.\n\nReason:\n\"{$reason}\"\n\nPlease visit your SKonnect portal if you wish to reapply.",
            ctaLabel:   'Go to My Requests',
            ctaUrl:     'http://localhost/SKonnect/views/portal/my_requests_page.php'
        );
    }

    /* ── ADMIN ACTION NOTIFICATION (User Management) ───────────── */
 
    public function sendVerificationLinkEmail(
        string $email,
        string $name,
        string $role,
        string $verifyUrl
    ): bool {
        return $this->sendNotification(
            email:      $email,
            name:       $name,
            subject:    'Verify Your SKonnect Account',
            badge:      '✉️ Email Verification Required',
            badgeColor: '#facc15',
            title:      "Welcome to SKonnect, {$name}!",
            bodyHtml:   "<p>An administrator has created a <strong>{$role}</strong> account for you on the SKonnect portal.</p>
                         <p>To activate your account and gain access, please verify your email address by clicking the button below.</p>
                         <p style='color:rgba(255,255,255,0.55);font-size:12px;margin-top:16px;'>
                             This verification link does not expire. If you did not expect this email, please ignore it or contact the barangay office.
                         </p>",
            bodyPlain:  "An administrator created a {$role} account for you on SKonnect.\n\nVerify your account here: {$verifyUrl}",
            ctaLabel:   'Verify My Account',
            ctaUrl:     $verifyUrl
        );
    }

    public function sendAdminActionNotification(
        string $email,
        string $name,
        string $subject,
        string $badge,
        string $badgeColor,
        string $title,
        string $bodyHtml,
        string $bodyPlain
    ): bool {
        return $this->sendNotification(
            email:      $email,
            name:       $name,
            subject:    $subject,
            badge:      $badge,
            badgeColor: $badgeColor,
            title:      $title,
            bodyHtml:   $bodyHtml,
            bodyPlain:  $bodyPlain,
            ctaLabel:   'Go to SKonnect',
            ctaUrl:     'http://localhost/skonnect/views/auth/login.php'
        );
    }
}