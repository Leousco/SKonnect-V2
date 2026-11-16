<?php

/**
 * Curated, static knowledge about how SKonnect works.
 *
 * The database tells the assistant WHAT exists (announcements, events,
 * services, threads). This class explains HOW the portal works: navigation,
 * account steps, application steps, status meanings, and escalation paths.
 *
 * Keep this file free of personal data, credentials, and promises the portal
 * does not fulfill. Update it whenever a resident-facing workflow changes.
 * AIController combines this text with live records from AIContextProvider.
 */
class AIKnowledgeBase
{
    public function build(string $message = ''): string
    {
        $sections = $this->order($this->sections(), $message);

        $lines = [
            'Curated guidance about SKonnect. Live portal records are supplied separately and take precedence for specific announcements, events, services, and threads.',
            '',
        ];

        foreach ($sections as $section) {
            $lines[] = '## ' . $section['title'];
            foreach ($section['points'] as $point) {
                $lines[] = '- ' . $point;
            }
            $lines[] = '';
        }

        return trim(implode("\n", $lines));
    }

    private function sections(): array
    {
        return [
            [
                'title'    => 'Portal overview and navigation',
                'keywords' => ['portal', 'page', 'navigation', 'where', 'find', 'menu', 'sidebar', 'dashboard', 'home', 'skonnect', 'feature'],
                'points'   => [
                    'SKonnect is the online community portal of the Sangguniang Kabataan (SK) for announcements, community discussions, services, and events.',
                    'After signing in as a resident, the left sidebar contains Dashboard, Announcements, Community Feed, Services, My Requests, Notifications, and Profile.',
                    'The public website, before signing in, has Home, Announcements, Community, Services, Login, and Register pages.',
                    'The floating Ask SK button opens this assistant from portal pages.',
                    'Use the exact page names above when explaining where a resident should go.',
                ],
            ],
            [
                'title'    => 'Creating and verifying an account',
                'keywords' => ['register', 'registration', 'signup', 'sign', 'up', 'account', 'verify', 'verification', 'otp', 'code', 'email', 'magrehistro', 'rehistro', 'registering'],
                'points'   => [
                    'Open the Login page and choose Register, or go directly to the Register page.',
                    'Required details are first name, last name, gender, birth date, email address, password, password confirmation, and agreement to the privacy notice. Middle name is optional.',
                    'Select Verify Account to submit the form. The portal sends a 6-digit verification code to the email address. The code expires after 10 minutes.',
                    'Enter the code on the Verify Email page. If the code does not arrive, use Resend Code and check the spam or junk folder.',
                    'If the email is already registered but still unverified, registering again sends a new verification code.',
                    'A resident cannot sign in until the account is verified.',
                ],
            ],
            [
                'title'    => 'Signing in and account restrictions',
                'keywords' => ['login', 'sign', 'password', 'forgot', 'locked', 'lock', 'ban', 'banned', 'restricted', 'sanction', 'unverified', 'cannot', 'makapasok'],
                'points'   => [
                    'Sign in on the Login page using the registered email address and password. Passwords are case-sensitive.',
                    'An unverified account is sent to Verify Email, and the portal can send a new code from there.',
                    'After 3 failed password attempts, the account is temporarily locked. The first lockout lasts 5 minutes, and later lockouts can last up to 30 minutes.',
                    'A banned account shows the restriction reason when signing in. Community bans can disable commenting temporarily or permanently.',
                    'Password reset is not available in the portal yet. A resident who cannot sign in should contact an SK officer or the portal administrator.',
                    'This assistant cannot unlock, unban, verify, or change an account.',
                ],
            ],
            [
                'title'    => 'Profile and personal details',
                'keywords' => ['profile', 'personal', 'information', 'edit', 'address', 'contact', 'number', 'member', 'membership', 'civil', 'religion'],
                'points'   => [
                    'Open Profile from the portal sidebar to see personal details, contact details, membership information, member ID, and membership date.',
                    'Use Edit Profile or the Edit button on a card to update personal, contact, and membership information, then choose Save Changes.',
                    'A profile setup prompt appears when required profile details are still incomplete.',
                    'Keep the email address and mobile number current so service request updates can reach the resident.',
                ],
            ],
            [
                'title'    => 'Finding a service',
                'keywords' => ['service', 'services', 'serbisyo', 'program', 'benefit', 'scholarship', 'medical', 'education', 'livelihood', 'assistance', 'legal', 'category', 'filter', 'search', 'requirements', 'eligibility', 'processing', 'slots', 'capacity', 'full', 'closed'],
                'points'   => [
                    'Open Services from the portal sidebar to see the services offered by the SK.',
                    'Use the search box and the category, type, and status filters to narrow the list. Categories are Medical, Education, Scholarship, Livelihood, Assistance, Legal, and Other.',
                    'Each service card shows Open, Limited Slots, or Closed. Closed means the service is inactive or all slots are filled, so it cannot be requested.',
                    'View Details shows the description, eligibility, processing time, requirements, remaining slots, and contact information.',
                    'Some services are information-only. They show View Details but no Apply or Request button. Contact the SK office using the listed details for those services.',
                    'A service marked Applied means the resident already has a pending, action-required, or approved application for it.',
                ],
            ],
            [
                'title'    => 'Applying for a service',
                'keywords' => ['apply', 'application', 'request', 'submit', 'form', 'upload', 'document', 'documents', 'requirements', 'paano', 'mag-apply', 'magapply', 'magrequest'],
                'points'   => [
                    'Open Services, choose an active service that is not Closed, then select Apply for an online application or Request for a request-based service.',
                    'Complete the request form: full name, contact number, email address, and home address. The email address is prefilled from the account and read-only. The purpose field is optional.',
                    'Upload the required documents when the service lists requirements. Accepted files are images, PDF, DOC, and DOCX, with a maximum of 5 MB per file. Documents are optional when the service has no listed requirements.',
                    'Tick the confirmation checkbox to confirm that the information is accurate and that the eligibility requirements are met.',
                    'Submit Request becomes available only after all required fields, required documents, and the confirmation are complete. Select it to send the application.',
                    'The service type explains the next step. Online Application means the form plus document upload. Request-based means the SK will coordinate the schedule or next steps. Information and Contact means there is no in-app application.',
                    'After submission, the application appears in My Requests with a Pending status.',
                    'The portal does not accept a second application for the same service while an existing application is pending, action-required, or approved.',
                ],
            ],
            [
                'title'    => 'Tracking a service request',
                'keywords' => ['status', 'track', 'tracking', 'requests', 'pending', 'approved', 'rejected', 'action', 'required', 'resubmit', 'cancel', 'timeline', 'update', 'application'],
                'points'   => [
                    'Open My Requests from the portal sidebar and use the status filter to find an application.',
                    'Pending means the request was submitted and is waiting for SK review.',
                    'Action Required means the SK officer needs missing information or documents. Open the request, choose Edit Submission, update the details or documents, then choose Resubmit Application and confirm.',
                    'Approved means the request was accepted. Open it to read the officer response and any fulfillment file or instructions.',
                    'Rejected means the request was declined. The request details show the officer response or reason.',
                    'Cancelled means the resident withdrew the request. Pending and Action Required requests can be cancelled from the request details.',
                    'The request timeline shows when it was submitted, reviewed, and completed. Status changes also appear in Notifications and may be sent by email.',
                ],
            ],
            [
                'title'    => 'Announcements',
                'keywords' => ['announcement', 'announcements', 'news', 'notice', 'update', 'banner', 'expired', 'program', 'meeting', 'urgent', 'balita'],
                'points'   => [
                    'Open Announcements from the portal sidebar to read SK news and notices.',
                    'Use the search box, the category filter (Events, Programs, Meetings, Notices, Urgent), and the sort order to find an announcement.',
                    'Select Read or open a card to view the full announcement, including its banner image.',
                    'Bookmark an announcement from the announcement card. Open My Bookmarks from the Announcements page to see saved announcements.',
                    'Expired announcements are not shown in the active list. Announcement reactions and comments are not available yet.',
                    'Announcements can also be viewed on the public website without signing in.',
                ],
            ],
            [
                'title'    => 'Community feed and discussions',
                'keywords' => ['thread', 'community', 'feed', 'post', 'comment', 'reply', 'support', 'heart', 'report', 'moderator', 'hide', 'delete', 'inquiry', 'complaint', 'suggestion', 'resolved', 'responded', 'discussion'],
                'points'   => [
                    'Open Community Feed from the portal sidebar to browse resident discussions.',
                    'Use search and the category, status, and sort filters. Categories are Inquiry, Complaint, Suggestion, Event Question, and Other. Statuses are Pending, Responded, and Resolved.',
                    'Select Post a Thread, then provide a required category, subject, and message. JPEG or PNG images are optional, with a maximum of 5 MB per image.',
                    'Open a thread to comment. Use Reply on a comment to reply to it, and the heart button to support a thread or comment.',
                    'Bookmark a thread from the feed or the thread page. Use My Bookmarks on the Community Feed page to see saved threads.',
                    'Use Report on a thread, comment, or reply to send it to the moderators. Residents can delete their own thread, comment, or reply.',
                    'Pending means the thread is waiting for a moderator response. Responded means a moderator or officer replied. Resolved means the concern is settled.',
                    'Moderators can change status, hide content, and issue temporary or permanent commenting bans. A sanctioned account sees a notice in the community feed.',
                    'Do not post personal or sensitive information publicly. Use Report or contact SK staff for concerns that need privacy.',
                ],
            ],
            [
                'title'    => 'Bookmarks',
                'keywords' => ['bookmark', 'bookmarks', 'saved', 'save', 'favorite', 'favorites'],
                'points'   => [
                    'Bookmark announcements and community threads using the bookmark icon.',
                    'See saved announcements through My Bookmarks on the Announcements page, and saved threads through My Bookmarks on the Community Feed page.',
                    'Select the bookmark icon again to remove an item.',
                ],
            ],
            [
                'title'    => 'Notifications',
                'keywords' => ['notification', 'notifications', 'bell', 'unread', 'read', 'dismiss', 'alert', 'email', 'notify'],
                'points'   => [
                    'The bell in the portal topbar shows the unread count and opens recent notifications.',
                    'Open Notifications from the sidebar for the full list, with Total, Unread, This Week, and Dismissed counts.',
                    'Use Mark All Read to clear the unread count, the check action to mark one notification as read, and the dismiss action to remove it from the active list.',
                    'Service request updates, account messages, and community updates may also be sent to the registered email address.',
                    'Verification codes are sent by email and are not shown in portal notifications.',
                ],
            ],
            [
                'title'    => 'Dashboard and events',
                'keywords' => ['dashboard', 'event', 'events', 'calendar', 'schedule', 'date', 'time', 'location', 'upcoming', 'activity'],
                'points'   => [
                    'Open Dashboard from the portal sidebar for a summary of active requests, community posts, upcoming events, notifications, latest announcements, latest discussions, available services, and recent activity.',
                    'The Dashboard calendar and Upcoming Events list show the event title, date, time, location, and description.',
                    'Events are informational. The portal does not provide event registration or ticketing.',
                    'SK officers and administrators manage announcements, services, and events. Moderators manage community content. Residents cannot create announcements or events.',
                ],
            ],
            [
                'title'    => 'Privacy and getting help',
                'keywords' => ['privacy', 'private', 'data', 'help', 'contact', 'officer', 'admin', 'administrator', 'support', 'report', 'personal', 'urgent', 'concern'],
                'points'   => [
                    'The assistant reads only public portal content: barangay settings, active and unexpired announcements, upcoming events, active services, and visible community threads.',
                    'It cannot see passwords, verification codes, profile details, notifications, reports, sanctions, or applications submitted by other residents.',
                    'Never share a password or verification code in chat.',
                    'For personal request status, use My Requests. For account, ban, verification, or data concerns, contact an SK officer or the portal administrator.',
                    'For inappropriate content, use Report. For urgent barangay concerns, contact the SK office using the contact details shown in the portal.',
                ],
            ],
            [
                'title'    => 'What the assistant can do',
                'keywords' => ['assistant', 'chatbot', 'ai', 'chat', 'can', 'cannot', 'help', 'question', 'answer'],
                'points'   => [
                    'The assistant explains portal features and answers questions using this guide plus live portal records.',
                    'It can summarize announcements, list upcoming events, describe services and requirements, and explain community threads.',
                    'It cannot submit applications, approve or cancel requests, change profile details, delete content, issue sanctions, or send emails.',
                    'If the portal does not contain the answer, it should say so plainly and point to the most relevant page or to the SK office. It must not guess.',
                    'For the best answer, ask about one topic at a time.',
                ],
            ],
        ];
    }

    /**
     * Keeps every section in context but moves the most relevant ones closer to
     * the top, where models tend to pay more attention. The first section is the
     * portal overview and is always kept first.
     */
    private function order(array $sections, string $message): array
    {
        if (count($sections) < 2) {
            return $sections;
        }

        $overview = array_shift($sections);
        $message  = mb_strtolower(trim($message), 'UTF-8');

        if ($message === '') {
            return array_merge([$overview], $sections);
        }

        $tokens = array_values(array_unique(array_filter(
            preg_split('/[^a-z0-9]+/', $message) ?: [],
            static function (string $token): bool {
                return mb_strlen($token, 'UTF-8') >= 3;
            }
        )));

        if (!$tokens) {
            return array_merge([$overview], $sections);
        }

        $scored = [];
        foreach ($sections as $index => $section) {
            $score = 0;
            foreach ($section['keywords'] as $keyword) {
                $keyword = mb_strtolower($keyword, 'UTF-8');
                if ($keyword === '') {
                    continue;
                }

                foreach ($tokens as $token) {
                    if ($token === $keyword) {
                        $score += 3;
                        continue;
                    }

                    if (mb_strlen($keyword, 'UTF-8') >= 4 && mb_strpos($token, $keyword) !== false) {
                        $score += 1;
                    }
                }
            }

            $scored[] = ['index' => $index, 'score' => $score, 'section' => $section];
        }

        usort($scored, static function (array $a, array $b): int {
            if ($a['score'] === $b['score']) {
                return $a['index'] <=> $b['index'];
            }

            return $b['score'] <=> $a['score'];
        });

        $ordered = [$overview];
        foreach ($scored as $entry) {
            $ordered[] = $entry['section'];
        }

        return $ordered;
    }
}
