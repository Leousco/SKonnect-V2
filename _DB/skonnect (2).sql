-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 08, 2026 at 03:51 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `skonnect`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'NULL = system action',
  `action` varchar(50) NOT NULL COMMENT 'approved|declined|published|flagged|deleted|created|updated|login|banned|unbanned|activated|deactivated',
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 13, 'thread_flagged', '{\"target_type\":\"thread\",\"target_id\":38,\"target_name\":\"This a thread\",\"target_user\":\"Icolet Etelevyssuraon\",\"notes\":\"Flagged for: spam. Added to mod queue.\"}', '::1', '2026-04-23 18:04:37'),
(2, 13, 'thread_pinned', '{\"target_type\":\"thread\",\"target_id\":37,\"target_name\":\"Can we start a community vegetable garden in the vacant lot at Phase 2?\",\"target_user\":\"Bicop Lmio\",\"notes\":\"Thread pinned to top of feed.\"}', '::1', '2026-04-23 18:06:20'),
(3, 13, 'thread_status_updated', '{\"target_type\":\"thread\",\"target_id\":35,\"target_name\":\"Process for getting a permit to start a home-based sari-sari store\",\"target_user\":\"Bicop Lmio\",\"notes\":\"Status changed to \\\"resolved\\\".\"}', '::1', '2026-04-23 18:39:40'),
(4, 13, 'mod_comment_posted', '{\"target_type\":\"thread\",\"target_id\":34,\"target_name\":\"How can I apply for a Resident ID card for my new helper?\",\"target_user\":\"Bicop Lmio\",\"notes\":\"Moderator posted a comment on this thread.\"}', '::1', '2026-04-23 18:40:01'),
(5, 13, 'thread_status_updated', '{\"target_type\":\"thread\",\"target_id\":34,\"target_name\":\"How can I apply for a Resident ID card for my new helper?\",\"target_user\":\"Bicop Lmio\",\"notes\":\"Status changed to \\\"responded\\\".\"}', '::1', '2026-04-23 18:40:07'),
(6, 13, 'warning_issued', '{\"target_type\":\"user\",\"target_id\":21,\"target_name\":\"Icolet Etelevyssuraon\",\"target_user\":\"\",\"notes\":\"Warning issued. Related thread: \\\"Can we start a community vegetable garden in the vacant lot at Phase 2?\\\".\"}', '::1', '2026-04-24 08:22:04'),
(7, 13, 'mod_comment_posted', '{\"target_type\":\"thread\",\"target_id\":39,\"target_name\":\"Why Event Big\",\"target_user\":\"Icolet Etelevyssuraon\",\"notes\":\"Moderator posted a comment on this thread.\"}', '::1', '2026-04-24 08:34:56'),
(8, 13, 'mute_issued', '{\"target_type\":\"user\",\"target_id\":23,\"target_name\":\"Icorousius Dleavo Debrovaniael\",\"target_user\":\"\",\"notes\":\"7-Day Ban issued. Reason: Comment removed test. Related thread: \\\"Thread Notification\\\". Reported content also removed.\"}', '::1', '2026-04-24 09:07:27'),
(9, 13, 'mod_comment_posted', '{\"target_type\":\"thread\",\"target_id\":44,\"target_name\":\"Saan po ang venue ng Summer Liga?\",\"target_user\":\"Ico Etelliv\",\"notes\":\"Moderator posted a comment on this thread.\"}', '::1', '2026-04-24 11:09:07'),
(10, 13, 'thread_status_updated', '{\"target_type\":\"thread\",\"target_id\":44,\"target_name\":\"Saan po ang venue ng Summer Liga?\",\"target_user\":\"Ico Etelliv\",\"notes\":\"Status changed to \\\"responded\\\".\"}', '::1', '2026-04-24 11:09:12'),
(11, 13, 'thread_pinned', '{\"target_type\":\"thread\",\"target_id\":44,\"target_name\":\"Saan po ang venue ng Summer Liga?\",\"target_user\":\"Ico Etelliv\",\"notes\":\"Thread pinned to top of feed.\"}', '::1', '2026-04-24 11:09:16'),
(12, 13, 'mod_comment_posted', '{\"target_type\":\"thread\",\"target_id\":50,\"target_name\":\"Missing SK ID - How to replace?\",\"target_user\":\"Bico Etelyev\",\"notes\":\"Moderator posted a comment on this thread.\"}', '::1', '2026-04-26 10:52:29'),
(13, 13, 'thread_status_updated', '{\"target_type\":\"thread\",\"target_id\":50,\"target_name\":\"Missing SK ID - How to replace?\",\"target_user\":\"Bico Etelyev\",\"notes\":\"Status changed to \\\"responded\\\".\"}', '::1', '2026-04-26 10:53:36'),
(14, 13, 'report_dismissed', '{\"target_type\":\"thread\",\"target_id\":50,\"target_name\":\"Missing SK ID - How to replace?\",\"target_user\":\"Bico Etelyev\",\"notes\":\"Report dismissed. Category: misinformation. No action taken.\"}', '::1', '2026-04-26 10:54:28'),
(15, 13, 'report_resolved', '{\"target_type\":\"thread\",\"target_id\":46,\"target_name\":\"Request to borrow SK Sound System\",\"target_user\":\"Bico Etelyev\",\"notes\":\"Report resolved. Category: harassment. Thread hidden and author notified.\"}', '::1', '2026-04-26 10:54:34'),
(16, 13, 'thread_status_updated', '{\"target_type\":\"thread\",\"target_id\":42,\"target_name\":\"Broken Lights at Phase 3 Basketball Court\",\"target_user\":\"Ico Etelliv\",\"notes\":\"Status changed to \\\"resolved\\\".\"}', '::1', '2026-04-26 20:25:59'),
(17, 13, 'thread_status_updated', '{\"target_type\":\"thread\",\"target_id\":47,\"target_name\":\"Mental Health Seminar for Senior High\",\"target_user\":\"Bico Etelyev\",\"notes\":\"Status auto-updated to \\\"responded\\\" on moderator comment.\"}', '::1', '2026-04-26 20:51:52'),
(18, 13, 'mod_comment_posted', '{\"target_type\":\"thread\",\"target_id\":47,\"target_name\":\"Mental Health Seminar for Senior High\",\"target_user\":\"Bico Etelyev\",\"notes\":\"Moderator posted a comment on this thread.\"}', '::1', '2026-04-26 20:51:56'),
(19, 13, 'thread_status_updated', '{\"target_type\":\"thread\",\"target_id\":48,\"target_name\":\"Application for SPES (Summer Job)\",\"target_user\":\"Bico Etelyev\",\"notes\":\"Status auto-updated to \\\"responded\\\" on moderator comment.\"}', '::1', '2026-04-26 20:53:15'),
(20, 13, 'mod_comment_posted', '{\"target_type\":\"thread\",\"target_id\":48,\"target_name\":\"Application for SPES (Summer Job)\",\"target_user\":\"Bico Etelyev\",\"notes\":\"Moderator posted a comment on this thread.\"}', '::1', '2026-04-26 20:53:19'),
(21, 13, 'thread_status_updated', '{\"target_type\":\"thread\",\"target_id\":43,\"target_name\":\"Proposal: Monthly E-Sports Tournament\",\"target_user\":\"Ico Etelliv\",\"notes\":\"Status auto-updated to \\\"responded\\\" on moderator comment.\"}', '::1', '2026-04-27 18:29:44'),
(22, 13, 'mod_comment_posted', '{\"target_type\":\"thread\",\"target_id\":43,\"target_name\":\"Proposal: Monthly E-Sports Tournament\",\"target_user\":\"Ico Etelliv\",\"notes\":\"Moderator posted a comment on this thread.\"}', '::1', '2026-04-27 18:29:49'),
(23, 13, 'thread_status_updated', '{\"target_type\":\"thread\",\"target_id\":48,\"target_name\":\"Application for SPES (Summer Job)\",\"target_user\":\"Bico Etelyev\",\"notes\":\"Status changed to \\\"resolved\\\".\"}', '::1', '2026-04-27 18:30:35'),
(24, 13, 'thread_restored', '{\"target_type\":\"thread\",\"target_id\":46,\"target_name\":\"Request to borrow SK Sound System\",\"target_user\":\"Bico Etelyev\",\"notes\":\"Thread restored to feed.\"}', '::1', '2026-04-30 20:24:21'),
(25, 12, 'declined', 'Banned user <strong>Bico Etelyev</strong>. Reason: goofy', '::1', '2026-05-03 10:37:44'),
(26, 12, 'updated', 'Lifted ban for user <strong>Bico Etelyev</strong>', '::1', '2026-05-03 11:03:17'),
(27, 12, 'deleted', 'Permanently deleted account: <strong>Bico Etelyev</strong> (leovillete878@gmail.com)', '::1', '2026-05-03 11:04:28'),
(28, 12, 'created', 'Created new user account for <strong>Dun Cruz</strong> with role <strong>Resident</strong>', '::1', '2026-05-03 18:11:51'),
(29, 12, 'deleted', 'Permanently deleted account: <strong>Dun Cruz</strong> (lvillete778@gmail.com)', '::1', '2026-05-03 18:53:19'),
(30, 12, 'created', 'Created new user account for <strong>Jun Druz</strong> with role <strong>Resident</strong>', '::1', '2026-05-03 18:54:21');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `category` enum('event','program','notice','meeting','urgent') NOT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `featured_at` timestamp NULL DEFAULT NULL,
  `banner_img` varchar(500) DEFAULT NULL,
  `author_id` int(11) NOT NULL,
  `published_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expired_at` date DEFAULT NULL,
  `status` enum('active','draft','archived') NOT NULL DEFAULT 'active',
  `archived_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `category`, `featured`, `featured_at`, `banner_img`, `author_id`, `published_at`, `updated_at`, `expired_at`, `status`, `archived_at`) VALUES
(49, 'Annual Summer Rooftop Mixer', '<p data-path-to-node=\"9,1,0\">It’s time to trade the spreadsheets for sunglasses! Join us next Thursday for our annual summer mixer on the 10th-floor terrace. We’ll have live music, a local taco bar, and some \"friendly\" competitive lawn games to celebrate our hard work this half-year.</p><p data-path-to-node=\"9,1,1\">Please <b data-path-to-node=\"9,1,1\" data-index-in-node=\"7\">RSVP via the internal portal</b> by Tuesday so we can finalize catering numbers. Don\'t forget to mention any dietary restrictions in the comments section of the form!</p>', 'event', 0, NULL, '/SKonnect/assets/uploads/banners/69eacfb894f984.26567218.jpg', 14, '2026-04-23 16:00:00', '2026-04-24 02:04:40', NULL, 'active', NULL),
(50, 'Launch of the \"Healthy Minds\" Initiative', '<p data-path-to-node=\"11,1,0\">We are thrilled to announce a new mental health support program for all staff members. Starting next month, the \"Healthy Minds\" initiative will provide free access to premium meditation apps, four confidential counseling sessions per year, and weekly yoga classes held in the breakout room.</p><p data-path-to-node=\"11,1,1\">This program is part of our ongoing commitment to employee well-being. We believe that a supported team is a successful team, and we encourage everyone to take advantage of these new resources.</p>', 'program', 0, NULL, '/SKonnect/assets/uploads/banners/69eacfdc33f407.70485330.jpg', 14, '2026-04-23 16:00:00', '2026-04-24 02:05:16', NULL, 'active', NULL),
(51, 'Q3 Strategic Town Hall', '<p data-path-to-node=\"13,1,0\">CEO Sarah Jenkins will lead our third-quarter Town Hall to discuss our 2026 performance goals and upcoming product launches. This is a mandatory hybrid event; local staff are encouraged to attend in the main auditorium, while remote teams can join via the standard video conference link.</p><p data-path-to-node=\"13,1,1\">There will be a <b data-path-to-node=\"13,1,1\" data-index-in-node=\"16\">30-minute Q&amp;A session</b> at the end. If you have specific questions you\'d like addressed, please submit them to the \"Slido\" link provided in your calendar invite by Wednesday afternoon.</p>', 'meeting', 0, NULL, '/SKonnect/assets/uploads/banners/69ead0106c5986.49014372.jpg', 14, '2026-04-23 16:00:00', '2026-04-24 02:06:08', NULL, 'active', NULL),
(52, 'Updated Hybrid Work Guidelines', '<p data-path-to-node=\"15,1,0\">To better support our collaborative culture, we are updating our hybrid work guidelines effective the first of next month. These changes are designed to provide more clarity on \"Core Collaboration Days\" while maintaining the flexibility our teams value.</p><p data-path-to-node=\"15,1,1\">Please review the <b data-path-to-node=\"15,1,1\" data-index-in-node=\"18\">updated Employee Handbook</b> in the HR section of the portal. Department heads will be reaching out to their respective teams to discuss how these changes apply to specific project timelines.</p>', 'notice', 0, NULL, '/SKonnect/assets/uploads/banners/69ead02e3ab106.91300921.jpg', 14, '2026-04-23 16:00:00', '2026-04-24 02:06:38', NULL, 'active', NULL),
(53, 'City Harvest: Community Food Drive', '<p data-path-to-node=\"17,1,0\">Our annual charity drive kicks off this Monday! We are partnering with City Harvest to collect non-perishable food items for local families in need. Collection bins will be placed at the main entrance and near the cafeteria elevators.</p><p data-path-to-node=\"17,1,1\">To add a little spice to the giving, the department that collects the most weight in donations by the end of the month will win a <b data-path-to-node=\"17,1,1\" data-index-in-node=\"130\">catered lunch of their choice</b>. Let’s see if Marketing can defend their title from last year!</p>', 'event', 0, NULL, '/SKonnect/assets/uploads/banners/69ead050e51a03.83006426.jpg', 14, '2026-04-23 16:00:00', '2026-04-24 02:07:12', NULL, 'active', NULL),
(54, 'Professional Development Grant Window', '<p data-path-to-node=\"19,1,0\">The application window for Q3 Professional Development Grants is officially open. If you are looking to take a certification course, attend an industry conference, or enroll in a technical workshop, you may be eligible for full or partial funding.</p><p data-path-to-node=\"19,1,1\">Applications must include a brief summary of how the course aligns with your current role and a breakdown of costs. Please submit your proposals through the <b data-path-to-node=\"19,1,1\" data-index-in-node=\"157\">Learning Management System (LMS)</b> for approval by your manager.</p>', 'program', 0, NULL, '/SKonnect/assets/uploads/banners/69ead06d8e4903.90470637.jpg', 14, '2026-04-23 16:00:00', '2026-04-24 02:07:41', NULL, 'active', NULL),
(55, 'Security Alert: New Phishing Attempt', '<p data-path-to-node=\"21,1,0\">Our IT Security team has identified a sophisticated phishing email currently circulating within the organization. The email purports to be from \"Payroll Services\" with the subject line \"Immediate Action Required: Tax Document Correction\" and asks users to click a link to verify their SSN.</p><p data-path-to-node=\"21,1,1\"><b data-path-to-node=\"21,1,1\" data-index-in-node=\"0\">Do not click any links</b> or download any attachments from this sender. If you have already interacted with the email, please disconnect your device from the network immediately and contact the Help Desk at extension 555.</p>', 'urgent', 1, '2026-04-23 20:08:16', '/SKonnect/assets/uploads/banners/69ead0907bed09.77337968.jpg', 14, '2026-04-23 16:00:00', '2026-04-24 02:08:16', NULL, 'active', NULL),
(56, 'Annual Community Health & Wellness Fair', '<p data-path-to-node=\"13,1,0\">It’s time for our annual outreach event! This Saturday, we will be transforming the North Parking Lot into a community health hub. We are offering free glucose testing, blood pressure screenings, and pediatric nutritional consultations to the general public.</p><p data-path-to-node=\"13,1,1\">We still need a few more volunteers from the Nursing and Admin departments to help manage the booths. It’s a great way to give back to the neighborhood and show the community the \"human\" side of our white coats.</p>', 'program', 1, '2026-04-23 20:09:23', '/SKonnect/assets/uploads/banners/69ead0d3113000.24250033.jpg', 14, '2026-04-23 16:00:00', '2026-04-24 02:09:23', NULL, 'active', NULL),
(57, 'Medical Mission 2026', 'We are thrilled to announce the return of our annual <b data-path-to-node=\"1\" data-index-in-node=\"53\">Medical Mission</b>, dedicated to providing essential healthcare services to those who need them most. Our mission is simple: to bridge the gap in healthcare access and bring healing, hope, and wellness directly to our community.<div><br></div><div><b>Event Overview</b></div><div>This year, we are bringing together a dedicated team of doctors, nurses, and volunteers to offer a comprehensive range of free medical services. Whether it’s a routine check-up or specialized consultation, our goal is to ensure no one is left behind.<font face=\"Google Sans Text, sans-serif\"><span style=\"font-size: 15.21px;\"><b><br></b></span></font></div><div><br></div><div><b>Services Provided</b></div><div><ul><li><p data-path-to-node=\"6,0,0\"><b data-path-to-node=\"6,0,0\" data-index-in-node=\"0\">General Consultations:</b> Physical exams and adult/pediatric medicine.</p></li><li><b data-path-to-node=\"6,1,0\" data-index-in-node=\"0\">Dental Care:</b> Cleanings, extractions, and oral health education.<p data-path-to-node=\"6,1,0\"></p></li><li><b data-path-to-node=\"6,2,0\" data-index-in-node=\"0\">Vision Screening:</b> Eye exams and distribution of prescription glasses.<br></li><li><b data-path-to-node=\"6,3,0\" data-index-in-node=\"0\">Diagnostic Testing:</b> Blood pressure monitoring, glucose testing, and BMI assessments.<br></li><li><b data-path-to-node=\"6,4,0\" data-index-in-node=\"0\">Pharmacy Services:</b> Provision of essential prescribed medications (subject to availability).</li></ul><br></div><div><h3 data-path-to-node=\"8\" style=\"font-family: &quot;Google Sans&quot;, sans-serif !important; line-height: 1.15 !important;\"><b data-path-to-node=\"8\" data-index-in-node=\"0\" style=\"font-family: &quot;Google Sans Text&quot;, sans-serif !important; line-height: 1.15 !important;\">Important Details</b></h3><table data-path-to-node=\"9\" style=\"margin-bottom: 32px; font-family: &quot;Google Sans Text&quot;, sans-serif !important; line-height: 1.15 !important;\"><thead style=\"font-family: &quot;Google Sans Text&quot;, sans-serif !important; line-height: 1.15 !important;\"><tr style=\"font-family: &quot;Google Sans Text&quot;, sans-serif !important; line-height: 1.15 !important;\"><td style=\"border: 1px solid; font-family: &quot;Google Sans Text&quot;, sans-serif !important; line-height: 1.15 !important;\"><strong style=\"font-family: &quot;Google Sans Text&quot;, sans-serif !important; line-height: 1.15 !important;\">Date</strong></td><td style=\"border: 1px solid; font-family: &quot;Google Sans Text&quot;, sans-serif !important; line-height: 1.15 !important;\"><strong style=\"font-family: &quot;Google Sans Text&quot;, sans-serif !important; line-height: 1.15 !important;\">Location</strong></td><td style=\"border: 1px solid; font-family: &quot;Google Sans Text&quot;, sans-serif !important; line-height: 1.15 !important;\"><strong style=\"font-family: &quot;Google Sans Text&quot;, sans-serif !important; line-height: 1.15 !important;\">Time</strong></td></tr></thead><tbody style=\"font-family: &quot;Google Sans Text&quot;, sans-serif !important; line-height: 1.15 !important;\"><tr style=\"font-family: &quot;Google Sans Text&quot;, sans-serif !important; line-height: 1.15 !important;\"><td style=\"border: 1px solid; font-family: &quot;Google Sans Text&quot;, sans-serif !important; line-height: 1.15 !important;\"><span data-path-to-node=\"9,1,0,0\" style=\"font-family: &quot;Google Sans Text&quot;, sans-serif !important; line-height: 1.15 !important;\"><b data-path-to-node=\"9,1,0,0\" data-index-in-node=\"0\" style=\"font-family: &quot;Google Sans Text&quot;, sans-serif !important; line-height: 1.15 !important;\">Saturday, June 20, 2026</b></span></td><td style=\"border: 1px solid; font-family: &quot;Google Sans Text&quot;, sans-serif !important; line-height: 1.15 !important;\"><span data-path-to-node=\"9,1,1,0\" style=\"font-family: &quot;Google Sans Text&quot;, sans-serif !important; line-height: 1.15 !important;\">Central Community Center</span></td><td style=\"border: 1px solid; font-family: &quot;Google Sans Text&quot;, sans-serif !important; line-height: 1.15 !important;\"><span data-path-to-node=\"9,1,2,0\" style=\"font-family: &quot;Google Sans Text&quot;, sans-serif !important; line-height: 1.15 !important;\">8:00 AM – 4:00 PM</span></td></tr></tbody></table></div>', 'program', 1, '2026-05-07 19:22:31', '/SKonnect/assets/uploads/banners/69fd3a8b198983.80416275.jpg', 14, '2026-05-07 16:00:00', '2026-05-08 01:22:31', NULL, 'active', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `announcement_bookmarks`
--

CREATE TABLE `announcement_bookmarks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement_bookmarks`
--

INSERT INTO `announcement_bookmarks` (`id`, `user_id`, `announcement_id`, `created_at`) VALUES
(43, 25, 50, '2026-04-26 02:34:00');

-- --------------------------------------------------------

--
-- Table structure for table `announcement_files`
--

CREATE TABLE `announcement_files` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `file_path` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement_files`
--

INSERT INTO `announcement_files` (`id`, `announcement_id`, `file_path`) VALUES
(56, 49, '/SKonnect/assets/uploads/attachments/69eacfb8ae4485.10139497.pdf'),
(57, 50, '/SKonnect/assets/uploads/attachments/69eacfdc406783.90228683.pdf'),
(58, 51, '/SKonnect/assets/uploads/attachments/69ead01076d908.44957574.pdf'),
(59, 52, '/SKonnect/assets/uploads/attachments/69ead02e447500.38404573.pdf'),
(60, 53, '/SKonnect/assets/uploads/attachments/69ead050eeea81.84882226.pdf'),
(61, 53, '/SKonnect/assets/uploads/attachments/69ead051063385.08218055.docx'),
(62, 55, '/SKonnect/assets/uploads/attachments/69ead09085b106.99954977.pdf'),
(63, 56, '/SKonnect/assets/uploads/attachments/69ead0d320d000.63421376.pdf'),
(64, 57, '/SKonnect/assets/uploads/attachments/69fd3a8ba00f05.52918086.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `application_documents`
--

CREATE TABLE `application_documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `application_id` int(10) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(10) UNSIGNED DEFAULT NULL COMMENT 'Bytes',
  `mime_type` varchar(100) DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `application_documents`
--

INSERT INTO `application_documents` (`id`, `application_id`, `file_name`, `file_path`, `file_size`, `mime_type`, `uploaded_at`) VALUES
(40, 23, 'Sample PDF Attachment.pdf', '/uploads/applications/app_23_69eadb21256707.63427671_Sample_PDF_Attachment.pdf', 80561, 'application/pdf', '2026-04-24 10:53:21'),
(41, 24, 'Sample PDF Attachment.pdf', '/uploads/applications/app_24_69eadb473fc502.47314481_Sample_PDF_Attachment.pdf', 80561, 'application/pdf', '2026-04-24 10:53:59'),
(42, 25, 'Sample PDF Attachment.pdf', '/uploads/applications/app_25_69eadb653f1604.38461347_Sample_PDF_Attachment.pdf', 80561, 'application/pdf', '2026-04-24 10:54:29'),
(43, 26, 'Sample PDF Attachment.pdf', '/uploads/applications/app_26_69eadbd07e7708.40023674_Sample_PDF_Attachment.pdf', 80561, 'application/pdf', '2026-04-24 10:56:16'),
(44, 27, 'Sample PDF Attachment.pdf', '/uploads/applications/app_27_69eadbf7966407.03489099_Sample_PDF_Attachment.pdf', 80561, 'application/pdf', '2026-04-24 10:56:55'),
(45, 27, 'Sample WORD Attachment.docx', '/uploads/applications/app_27_69ed7c4f073a04.83064359_Sample_WORD_Attachment.docx', 11028, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2026-04-26 10:45:35'),
(46, 28, 'Sample PDF Attachment.pdf', '/uploads/applications/app_28_69fd4087ebbe07.44077122_Sample_PDF_Attachment.pdf', 80561, 'application/pdf', '2026-05-08 09:46:47'),
(47, 29, 'Sample PDF Attachment.pdf', '/uploads/applications/app_29_69fd40a7d68080.74341576_Sample_PDF_Attachment.pdf', 80561, 'application/pdf', '2026-05-08 09:47:19'),
(48, 30, 'Sample PDF Attachment.pdf', '/uploads/applications/app_30_69fd40d075d289.05433388_Sample_PDF_Attachment.pdf', 80561, 'application/pdf', '2026-05-08 09:48:00');

-- --------------------------------------------------------

--
-- Table structure for table `application_notes`
--

CREATE TABLE `application_notes` (
  `id` int(10) UNSIGNED NOT NULL,
  `application_id` int(10) UNSIGNED NOT NULL,
  `officer_id` int(10) UNSIGNED NOT NULL COMMENT 'FK → users.id',
  `note` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `application_notes`
--

INSERT INTO `application_notes` (`id`, `application_id`, `officer_id`, `note`, `created_at`) VALUES
(25, 27, 14, 'Kulang reqs', '2026-04-26 10:43:04'),
(26, 27, 14, 'Request Approved! Your request has been verified. You may claim the medicines at the Barangay Health Center Pharmacy. Look for Nurse Gina and present the approval code: SK-MED-2026.', '2026-04-26 10:47:44'),
(27, 30, 14, 'Missing requirements', '2026-05-08 09:48:42');

-- --------------------------------------------------------

--
-- Table structure for table `comment_replies`
--

CREATE TABLE `comment_replies` (
  `id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_removed` tinyint(1) NOT NULL DEFAULT 0,
  `removed_by_mod` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = hidden by a moderator sanction (shows tombstone); 0 = not mod-removed',
  `removed_by_user` tinyint(1) NOT NULL DEFAULT 0,
  `is_mod_comment` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comment_replies`
--

INSERT INTO `comment_replies` (`id`, `comment_id`, `author_id`, `message`, `is_removed`, `removed_by_mod`, `removed_by_user`, `is_mod_comment`, `created_at`) VALUES
(47, 85, 25, 'Reply #1', 0, 0, 0, 0, '2026-04-24 11:06:18'),
(48, 85, 25, 'Replied', 0, 0, 0, 0, '2026-04-24 11:06:23'),
(49, 88, 25, 'This a reply', 0, 0, 0, 0, '2026-04-24 11:06:47'),
(50, 88, 25, 'Replies to comment', 0, 0, 0, 0, '2026-04-24 11:06:55');

-- --------------------------------------------------------

--
-- Table structure for table `comment_reports`
--

CREATE TABLE `comment_reports` (
  `id` int(11) NOT NULL,
  `target_type` enum('comment','reply') NOT NULL,
  `target_id` int(11) NOT NULL,
  `reporter_id` int(11) NOT NULL,
  `category` enum('inappropriate','spam','misinformation','harassment') NOT NULL,
  `note` text DEFAULT NULL,
  `status` enum('pending','reviewed','dismissed') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comment_reports`
--

INSERT INTO `comment_reports` (`id`, `target_type`, `target_id`, `reporter_id`, `category`, `note`, `status`, `created_at`) VALUES
(24, 'reply', 47, 24, 'spam', 'Spammed', 'pending', '2026-04-24 11:08:14'),
(25, 'comment', 88, 24, 'inappropriate', 'Not nice', 'pending', '2026-04-24 11:08:25'),
(26, 'comment', 89, 24, 'misinformation', NULL, 'pending', '2026-04-24 11:08:41'),
(27, 'comment', 84, 27, 'spam', 'Not informational', 'pending', '2026-05-08 09:49:55'),
(28, 'comment', 87, 27, 'misinformation', NULL, 'pending', '2026-05-08 09:50:10');

-- --------------------------------------------------------

--
-- Table structure for table `comment_supports`
--

CREATE TABLE `comment_supports` (
  `id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comment_supports`
--

INSERT INTO `comment_supports` (`id`, `comment_id`, `user_id`, `created_at`) VALUES
(56, 91, 25, '2026-04-26 10:34:33'),
(57, 86, 25, '2026-04-26 10:34:34');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `event_time_end` time DEFAULT NULL,
  `location` varchar(120) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL COMMENT 'FK → users.id (sk_officer)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `event_date`, `event_time`, `event_time_end`, `location`, `description`, `created_by`, `created_at`, `updated_at`) VALUES
(6, 'SKonnect Esports League: Battle of the Barangays', '2026-04-25', '08:00:00', '13:00:00', 'Barangay Covered Court (Main Stage)', 'Step out of the casual lobbies and into the competitive arena! This month-long tournament is designed to foster camaraderie and sportsmanship through digital competition, focusing on popular titles like Mobile Legends: Bang Bang and Valorant. Beyond the gameplay, the event includes a seminar on &amp;quot;Digital Wellness and Responsible Gaming&amp;quot; to educate our young athletes on balancing screen time with physical health. The grand finals will be live-streamed on our official social media pages, featuring professional shoutcasters and a significant prize pool for the top-performing teams.', 14, '2026-04-24 02:59:25', '2026-04-24 03:04:14'),
(7, '&amp;quot;Green Youth&amp;quot; Community Urban Gardening Workshop', '2026-04-28', '07:00:00', NULL, 'Community Greenhouse and Seedling Nursery', 'Transform your small balcony or backyard into a sustainable food source! The SK Council is partnering with local agricultural experts to teach the youth the fundamentals of hydroponics and vertical gardening. Participants will learn how to grow their own vegetables and herbs using recycled materials, promoting environmental consciousness and food security within our community. At the end of the workshop, each attendee will receive a &amp;quot;Green Starter Kit,&amp;quot; which includes organic seeds, specialized soil, and biodegradable pots to jumpstart their home garden journey.', 14, '2026-04-24 02:59:59', '2026-04-24 03:04:22'),
(8, 'Youth Empowerment Summit 2026: Leading the Future', '2026-04-07', '13:00:00', '17:00:00', 'arangay Multi-Purpose Convention Center (2nd Floor, Main Hall).', 'Our flagship annual summit returns with a focus on leadership, innovation, and civic engagement. This full-day event features keynote speakers from successful startups, local government leaders, and youth advocates who will share their journeys and insights on how to make a tangible impact in society.', 14, '2026-04-24 03:00:36', '2026-04-24 03:00:36'),
(9, '&quot;Palarong Kabataan&quot; Summer Sports Festival', '2026-05-06', '10:00:00', NULL, 'Zone 4 Sports Complex and the Phase 1 Open Field.', 'Relive the thrill of traditional Filipino games alongside modern athletic competitions! The Summer Sports Festival is a week-long celebration of physical fitness and local culture.', 14, '2026-04-24 03:01:17', '2026-04-24 03:01:17'),
(10, 'Mental Health Awareness Night: &quot;You Are Not Alone&quot;', '2026-05-15', '08:00:00', '13:00:00', 'Barangay Park and Amphitheater (Under the Stars).', 'Join us for an evening of reflection, support, and music dedicated to breaking the stigma surrounding mental health.', 14, '2026-04-24 03:01:51', '2026-04-24 03:01:51'),
(11, 'Tech-Voc Career Fair &amp; Job Expo', '2026-05-22', '16:30:00', '20:00:00', 'Ground Floor Lobby, Barangay Hall Building.', 'Bridge the gap between education and employment at our comprehensive Career Fair. Specifically tailored for graduating students and out-of-school youth, this event brings together over 20 partner companies from the BPO, retail, and hospitality sectors for on-the-spot interviews and hiring.', 14, '2026-04-24 03:02:45', '2026-04-24 03:02:45');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `is_dismissed` tinyint(1) NOT NULL DEFAULT 0,
  `is_official` tinyint(1) NOT NULL DEFAULT 0,
  `link` varchar(300) DEFAULT NULL,
  `ref_type` varchar(30) DEFAULT NULL,
  `ref_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `is_read`, `is_dismissed`, `is_official`, `link`, `ref_type`, `ref_id`, `created_at`) VALUES
(7, 13, 'thread', 'New Reply to Your Comment', 'Icolet Etelevyssuraon replied to your comment on \"Thread Notification\": \"relly\"', 0, 0, 0, 'thread_view.php?id=40', 'thread', 40, '2026-04-22 20:40:03'),
(20, 24, 'new_service', 'New Service Available: SK Tertiary Scholarship Grant', 'A new Scholarship service \"SK Tertiary Scholarship Grant\" is now available on the portal. Visit the Services page to view eligibility requirements and apply.', 1, 0, 0, 'services_page.php?id=32', 'service', 32, '2026-04-24 10:36:07'),
(21, 25, 'new_service', 'New Service Available: SK Tertiary Scholarship Grant', 'A new Scholarship service \"SK Tertiary Scholarship Grant\" is now available on the portal. Visit the Services page to view eligibility requirements and apply.', 1, 0, 0, 'services_page.php?id=32', 'service', 32, '2026-04-24 10:36:07'),
(22, 24, 'new_service', 'New Service Available: Youth Mental Health Hotline', 'A new Medical service \"Youth Mental Health Hotline\" is now available on the portal. Visit the Services page to view eligibility requirements and apply.', 1, 0, 0, 'services_page.php?id=33', 'service', 33, '2026-04-24 10:37:08'),
(23, 25, 'new_service', 'New Service Available: Youth Mental Health Hotline', 'A new Medical service \"Youth Mental Health Hotline\" is now available on the portal. Visit the Services page to view eligibility requirements and apply.', 1, 0, 0, 'services_page.php?id=33', 'service', 33, '2026-04-24 10:37:08'),
(24, 24, 'new_service', 'New Service Available: Free Prescription Medicine Request', 'A new Medical service \"Free Prescription Medicine Request\" is now available on the portal. Visit the Services page to view eligibility requirements and apply.', 1, 0, 0, 'services_page.php?id=34', 'service', 34, '2026-04-24 10:38:46'),
(25, 25, 'new_service', 'New Service Available: Free Prescription Medicine Request', 'A new Medical service \"Free Prescription Medicine Request\" is now available on the portal. Visit the Services page to view eligibility requirements and apply.', 1, 0, 0, 'services_page.php?id=34', 'service', 34, '2026-04-24 10:38:46'),
(26, 24, 'new_service', 'New Service Available: Tech-Skills Livelihood Training', 'A new Livelihood service \"Tech-Skills Livelihood Training\" is now available on the portal. Visit the Services page to view eligibility requirements and apply.', 1, 0, 0, 'services_page.php?id=35', 'service', 35, '2026-04-24 10:39:42'),
(27, 25, 'new_service', 'New Service Available: Tech-Skills Livelihood Training', 'A new Livelihood service \"Tech-Skills Livelihood Training\" is now available on the portal. Visit the Services page to view eligibility requirements and apply.', 1, 0, 0, 'services_page.php?id=35', 'service', 35, '2026-04-24 10:39:42'),
(28, 24, 'new_service', 'New Service Available: Burial & Funeral Cash Assistance', 'A new Assistance service \"Burial & Funeral Cash Assistance\" is now available on the portal. Visit the Services page to view eligibility requirements and apply.', 1, 0, 0, 'services_page.php?id=36', 'service', 36, '2026-04-24 10:40:41'),
(29, 25, 'new_service', 'New Service Available: Burial & Funeral Cash Assistance', 'A new Assistance service \"Burial & Funeral Cash Assistance\" is now available on the portal. Visit the Services page to view eligibility requirements and apply.', 1, 0, 0, 'services_page.php?id=36', 'service', 36, '2026-04-24 10:40:41'),
(30, 24, 'new_service', 'New Service Available: SK Youth Legal Desk', 'A new Legal service \"SK Youth Legal Desk\" is now available on the portal. Visit the Services page to view eligibility requirements and apply.', 1, 0, 0, 'services_page.php?id=37', 'service', 37, '2026-04-24 10:41:38'),
(31, 25, 'new_service', 'New Service Available: SK Youth Legal Desk', 'A new Legal service \"SK Youth Legal Desk\" is now available on the portal. Visit the Services page to view eligibility requirements and apply.', 1, 0, 0, 'services_page.php?id=37', 'service', 37, '2026-04-24 10:41:38'),
(32, 24, 'new_service', 'New Service Available: Sports Equipment Lending (Liga Prep)', 'A new Other service \"Sports Equipment Lending (Liga Prep)\" is now available on the portal. Visit the Services page to view eligibility requirements and apply.', 1, 0, 0, 'services_page.php?id=38', 'service', 38, '2026-04-24 10:42:40'),
(33, 25, 'new_service', 'New Service Available: Sports Equipment Lending (Liga Prep)', 'A new Other service \"Sports Equipment Lending (Liga Prep)\" is now available on the portal. Visit the Services page to view eligibility requirements and apply.', 1, 0, 0, 'services_page.php?id=38', 'service', 38, '2026-04-24 10:42:40'),
(34, 24, 'new_service', 'New Service Available: Student Working Permit Certification', 'A new Education service \"Student Working Permit Certification\" is now available on the portal. Visit the Services page to view eligibility requirements and apply.', 1, 0, 0, 'services_page.php?id=39', 'service', 39, '2026-04-24 10:43:34'),
(35, 25, 'new_service', 'New Service Available: Student Working Permit Certification', 'A new Education service \"Student Working Permit Certification\" is now available on the portal. Visit the Services page to view eligibility requirements and apply.', 1, 0, 0, 'services_page.php?id=39', 'service', 39, '2026-04-24 10:43:34'),
(36, 24, 'new_service', 'New Service Available: Barangay Clearance for First-Time Job Seekers', 'A new Legal service \"Barangay Clearance for First-Time Job Seekers\" is now available on the portal. Visit the Services page to view eligibility requirements and apply.', 1, 0, 0, 'services_page.php?id=40', 'service', 40, '2026-04-24 10:44:26'),
(37, 25, 'new_service', 'New Service Available: Barangay Clearance for First-Time Job Seekers', 'A new Legal service \"Barangay Clearance for First-Time Job Seekers\" is now available on the portal. Visit the Services page to view eligibility requirements and apply.', 1, 0, 0, 'services_page.php?id=40', 'service', 40, '2026-04-24 10:44:26'),
(38, 24, 'new_service', 'New Service Available: Calamity Emergency Relief Pack', 'A new Assistance service \"Calamity Emergency Relief Pack\" is now available on the portal. Visit the Services page to view eligibility requirements and apply.', 1, 0, 0, 'services_page.php?id=41', 'service', 41, '2026-04-24 10:45:18'),
(39, 25, 'new_service', 'New Service Available: Calamity Emergency Relief Pack', 'A new Assistance service \"Calamity Emergency Relief Pack\" is now available on the portal. Visit the Services page to view eligibility requirements and apply.', 1, 1, 0, 'services_page.php?id=41', 'service', 41, '2026-04-24 10:45:18'),
(40, 24, 'thread', 'New Comment on Your Thread', 'Bico Etelyev commented on your thread \"Saan po ang venue ng Summer Liga?\": \"Comment\"', 0, 0, 0, 'thread_view.php?id=44', 'thread', 44, '2026-04-24 11:06:04'),
(41, 24, 'thread', 'New Comment on Your Thread', 'Bico Etelyev commented on your thread \"Saan po ang venue ng Summer Liga?\": \"Comment #2\"', 0, 0, 0, 'thread_view.php?id=44', 'thread', 44, '2026-04-24 11:06:08'),
(42, 24, 'thread', 'New Comment on Your Thread', 'Bico Etelyev commented on your thread \"Saan po ang venue ng Summer Liga?\": \"Comment #3\"', 0, 0, 0, 'thread_view.php?id=44', 'thread', 44, '2026-04-24 11:06:13'),
(43, 24, 'thread', 'New Comment on Your Thread', 'Bico Etelyev commented on your thread \"Proposal: Monthly E-Sports Tournament\": \"Commented\"', 0, 0, 0, 'thread_view.php?id=43', 'thread', 43, '2026-04-24 11:06:38'),
(44, 24, 'thread', 'New Comment on Your Thread', 'Bico Etelyev commented on your thread \"Proposal: Monthly E-Sports Tournament\": \"Posted Comment\"', 0, 0, 0, 'thread_view.php?id=43', 'thread', 43, '2026-04-24 11:06:43'),
(45, 24, 'thread', 'New Comment on Your Thread', 'Bico Etelyev commented on your thread \"Transparency on SK Fund Allocation\": \"Commenting\"', 0, 0, 0, 'thread_view.php?id=45', 'thread', 45, '2026-04-24 11:07:06'),
(46, 25, 'thread', 'New Comment on Your Thread', 'Ico Etelliv commented on your thread \"Request to borrow SK Sound System\": \"Comments\"', 1, 1, 0, 'thread_view.php?id=46', 'thread', 46, '2026-04-24 11:08:00'),
(47, 24, 'thread', 'Official Response on Your Thread', 'A moderator (Maya Reyes) has officially responded to your thread \"Saan po ang venue ng Summer Liga?\": \"Official Response\"', 1, 0, 1, 'thread_view.php?id=44', 'thread', 44, '2026-04-24 11:09:03'),
(48, 25, 'service', 'Action Required: Free Prescription Medicine Request', 'An officer has added a note to your \"Free Prescription Medicine Request\" request (REQ-#27) and is asking for additional information or documents. Please review and respond.', 1, 0, 0, 'my_requests_page.php?id=27', 'service_application', 27, '2026-04-26 10:43:05'),
(49, 25, 'service', 'Request Approved: Free Prescription Medicine Request', 'Your request for \"Free Prescription Medicine Request\" (REQ-#27) has been approved. Please check your request page for next steps and any additional instructions.', 1, 0, 0, 'my_requests_page.php?id=27', 'service_application', 27, '2026-04-26 10:47:44'),
(50, 25, 'thread', 'Official Response on Your Thread', 'A moderator (Maya Reyes) has officially responded to your thread \"Missing SK ID - How to replace?\": \"Idk\"', 1, 0, 1, 'thread_view.php?id=50', 'thread', 50, '2026-04-26 10:52:25'),
(51, 25, 'thread', 'Official Response on Your Thread', 'A moderator (Maya Reyes) has officially responded to your thread \"Mental Health Seminar for Senior High\": \"Testy Comment Status Auto Update\"', 1, 1, 1, 'thread_view.php?id=47', 'thread', 47, '2026-04-26 20:51:52'),
(52, 25, 'thread', 'Official Response on Your Thread', 'A moderator (Maya Reyes) has officially responded to your thread \"Application for SPES (Summer Job)\": \"Commentses\"', 1, 1, 1, 'thread_view.php?id=48', 'thread', 48, '2026-04-26 20:53:15'),
(53, 24, 'thread', 'Official Response on Your Thread', 'A moderator (Maya Reyes) has officially responded to your thread \"Proposal: Monthly E-Sports Tournament\": \"Comment\"', 0, 0, 1, 'thread_view.php?id=43', 'thread', 43, '2026-04-27 18:29:44'),
(54, 24, 'announcement', 'New Announcement: Medical Mission 2026', 'A new announcement has been posted: \"Medical Mission 2026\". We are thrilled to announce the return of our annual Medical Mission, dedicated to providing essential healthcare servi…', 0, 0, 0, 'announcements_page.php?id=57', 'announcement', 57, '2026-05-08 09:21:16'),
(55, 27, 'announcement', 'New Announcement: Medical Mission 2026', 'A new announcement has been posted: \"Medical Mission 2026\". We are thrilled to announce the return of our annual Medical Mission, dedicated to providing essential healthcare servi…', 1, 0, 0, 'announcements_page.php?id=57', 'announcement', 57, '2026-05-08 09:21:16'),
(56, 27, 'service', 'Action Required: Sports Equipment Lending (Liga Prep)', 'An officer has added a note to your \"Sports Equipment Lending (Liga Prep)\" request (REQ-#30) and is asking for additional information or documents. Please review and respond.', 0, 0, 0, 'my_requests_page.php?id=30', 'service_application', 30, '2026-05-08 09:48:42'),
(57, 24, 'thread', 'New Comment on Your Thread', 'Jun Druz commented on your thread \"Transparency on SK Fund Allocation\": \"Test comment\"', 0, 0, 0, 'thread_view.php?id=45', 'thread', 45, '2026-05-08 09:49:39'),
(58, 24, 'thread', 'New Comment on Your Thread', 'Jun Druz commented on your thread \"Broken Lights at Phase 3 Basketball Court\": \"It is not broken\"', 0, 0, 0, 'thread_view.php?id=42', 'thread', 42, '2026-05-08 09:50:27');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` enum('medical','education','scholarship','livelihood','assistance','legal','other') NOT NULL DEFAULT 'other',
  `service_type` enum('document','appointment','info') NOT NULL DEFAULT 'document' COMMENT 'document = online application, appointment = request-based, info = information/direct contact',
  `description` text NOT NULL,
  `approval_message` text NOT NULL COMMENT 'Instructions shown to residents when their application is approved',
  `eligibility` varchar(255) DEFAULT NULL,
  `processing_time` varchar(100) DEFAULT NULL,
  `requirements` text DEFAULT NULL COMMENT 'Raw text; lines starting with - are rendered as bullet points',
  `contact_info` text DEFAULT NULL COMMENT 'Used for info/walk-in type services only',
  `attachment_name` varchar(255) DEFAULT NULL COMMENT 'Display name of the downloadable form',
  `attachment_path` varchar(500) DEFAULT NULL COMMENT 'Server path or URL to the downloadable file',
  `max_capacity` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'NULL = unlimited; when current_count >= max_capacity the service auto-closes',
  `current_count` smallint(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Approved/accepted applicant count; auto-incremented by backend',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK to users table — officer who created the service',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `category`, `service_type`, `description`, `approval_message`, `eligibility`, `processing_time`, `requirements`, `contact_info`, `attachment_name`, `attachment_path`, `max_capacity`, `current_count`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(32, 'SK Tertiary Scholarship Grant', 'scholarship', 'document', 'A semi-annual financial assistance program for underprivileged but deserving college students residing in the barangay.', 'Congratulations! Your scholarship application has been approved. Please visit the SK Office this coming Friday (9 AM - 5 PM) to sign the Memorandum of Agreement and claim your stipend. Bring your original School ID.', 'Enrolled College Students (Residents)', '5-7 working days', '- Certificate of Enrollment (Current Semester)\r\n- Latest General Weighted Average (GWA) / Report Card\r\n- Certificate of Indigency', '', 'Sample PDF Attachment.pdf', '/uploads/forms/form_69ead717c0c609.25435246_Sample_PDF_Attachment.pdf', 100, 0, 'active', 14, '2026-04-24 10:36:07', '2026-04-24 10:36:07'),
(33, 'Youth Mental Health Hotline', 'medical', 'info', 'A 24/7 confidential counseling and crisis intervention service for youth dealing with stress, anxiety, or personal issues provides round-the-clock access to trained counselors or peer support volunteers via phone, chat, or in-person drop-ins. This ensures that young people in distress—whether due to academic pressure, family conflicts, relationship problems, or mental health struggles—can receive immediate, non-judgmental help at any hour without fear of stigma or breach of privacy. The service may include active listening, coping strategies, de-escalation techniques, and referrals to psychiatrists or social workers when needed. By being available 24/7, it acts as a safety net for those who may feel isolated or helpless outside regular office hours, potentially preventing crises from escalating into self-harm or other emergencies.', '', 'Open to all youth residents', 'Immediate', '', 'Hotline: 0917-123-HELP (4357) \r\nFB Messenger: @SKonnectMentalHealth \r\nOffice: 2nd Floor, Barangay Health Center.', 'Sample PDF Attachment.pdf', '/uploads/forms/form_69ead753e1bb88.81408440_Sample_PDF_Attachment.pdf', NULL, 0, 'active', 14, '2026-04-24 10:37:07', '2026-04-24 10:51:44'),
(34, 'Free Prescription Medicine Request', 'medical', 'appointment', 'Provision of basic maintenance medicines or emergency prescription refills for indigent youth and their immediate families ensures that life-saving treatments for conditions like asthma, hypertension, or infections are not interrupted due to financial hardship. This assistance allows eligible families to obtain essential drugs—such as antibiotics, antipyretics, or maintenance tablets for chronic illnesses—during critical times without waiting for regular health center schedules or facing out-of-pocket expenses. By covering emergency refills and basic medications, the program prevents minor health issues from worsening and reduces the burden on public hospitals, giving young people and their families timely access to care when they need it most.', 'Your request has been verified. You may claim the medicines at the Barangay Health Center Pharmacy. Look for Nurse Gina and present the approval code: SK-MED-2026.', 'Registered Residents', '1-2 days', '- Valid Medical Prescription (dated within the last 3 months)\r\n- Barangay ID or Proof of Residency', '', 'Sample PDF Attachment.pdf,Sample EXCEL Attachment.xlsx', '/uploads/forms/form_69ead7b6320005.26436597_Sample_PDF_Attachment.pdf,/uploads/forms/form_69ead7b644cc86.80606199_Sample_EXCEL_Attachment.xlsx', NULL, 2, 'active', 14, '2026-04-24 10:38:46', '2026-04-24 10:56:55'),
(35, 'Tech-Skills Livelihood Training', 'livelihood', 'document', 'A weekend workshop series focusing on high-demand digital skills like Graphic Design, Virtual Assistance, and Basic Web Development is offered to equip young people with practical, income-generating competencies. These hands-on sessions are typically held on Saturdays or Sundays, making them accessible to students and employed youth alike. By covering tools like Canva or Photoshop for design, communication and productivity platforms for VA work, and HTML/CSS basics for web development, the workshops help participants build freelancing-ready skills. This initiative opens doors to online job opportunities, remote work, and entrepreneurial projects without requiring expensive formal courses.', 'You are officially enrolled in the workshop! Please check your registered email for the Zoom link and the digital starter kit. The first session starts this Saturday at 1:00 PM.', 'Out-of-school youth or unemployed graduates (Ages 15-30)', '3 working days', '- Valid ID with Birthdate\r\n- Accomplished Skills Assessment Form (Online)', '', 'Sample PDF Attachment.pdf', '/uploads/forms/form_69ead7ee202d88.06260981_Sample_PDF_Attachment.pdf', 30, 2, 'active', 14, '2026-04-24 10:39:42', '2026-05-08 09:47:19'),
(36, 'Burial & Funeral Cash Assistance', 'assistance', 'appointment', 'Immediate financial aid is extended to the families of deceased youth members to help cover funeral and burial expenses. This assistance ensures that bereaved families need not worry about upfront costs during a difficult time, allowing them to give their loved one a dignified farewell without added financial strain.', 'We extend our deepest condolences. Your request for assistance has been approved. The cash aid can be collected by the designated beneficiary at the Barangay Treasurer\'s Office.', 'Immediate family of the deceased resident', '1-3 days', '- Certified True Copy of Death Certificate\r\n- Valid ID of the Claimant\r\n- Proof of Relationship (Birth Certificate/Marriage Contract)', '', 'Sample PDF Attachment.pdf', '/uploads/forms/form_69ead8291b3282.12593356_Sample_PDF_Attachment.pdf', NULL, 2, 'active', 14, '2026-04-24 10:40:41', '2026-05-08 09:46:47'),
(37, 'SK Youth Legal Desk', 'legal', 'info', 'Free legal consultation for youth-related cases is offered to working students and young individuals, covering labor disputes, notary services, and rights awareness. This assistance helps them understand their legal protections without the financial burden of hiring a private lawyer.', '', 'Registered Voters / Residents (Ages 15-30)', '1 day (for appointments)', '- Valid ID', 'Atty. Reyes is available every Tuesday and Thursday,\r\n2 PM to 5 PM at the SK Hall. \r\nFor appointments, call (02) 8-555-0123.', NULL, NULL, NULL, 0, 'active', 14, '2026-04-24 10:41:38', '2026-04-24 10:50:30'),
(38, 'Sports Equipment Lending (Liga Prep)', 'other', 'appointment', 'Borrowing of basketballs, volleyballs, nets, and training cones for youth-led community sports activities is typically facilitated through the Sangguniang Kabataan or barangay youth desk. These equipment items are made available to support organized sports events, summer clinics, and recreational meets that promote teamwork, physical fitness, and positive youth engagement. Interested groups usually need to submit a simple letter request addressed to the SK chairperson, indicating the activity details, schedule, and number of participants. Once approved, the equipment may be borrowed free of charge, provided it is returned in good condition. This initiative encourages young leaders to organize healthy, productive activities without the added cost of renting gear.', 'Request approved. You may pick up the equipment at the SK Hall. Please ensure all items are returned in good condition within 48 hours to avoid penalties.', 'Youth Group Leaders / SK Members', '1 day', '- Borrower\'s Valid ID\r\n- Signed Equipment Accountability Form', '', 'Sample PDF Attachment.pdf', '/uploads/forms/form_69ead8a0d3ea05.59713063_Sample_PDF_Attachment.pdf', NULL, 1, 'active', 14, '2026-04-24 10:42:40', '2026-05-08 09:48:00'),
(39, 'Student Working Permit Certification', 'education', 'document', 'Under the guidelines of the Sangguniang Kabataan, an SK Endorsement may be issued to students aged 15 to 17 who are seeking part-time summer employment or applying for the Special Program for the Employment of Students (SPES). This endorsement serves as an official certification from the youth council affirming the student’s residency, good moral character, and eligibility for youth-targeted work opportunities. It is particularly valuable for minors who need to comply with local government and Department of Labor and Employment (DOLE) requirements, as the endorsement helps facilitate their enrollment in SPES, where they can work for short periods during school breaks while receiving both compensation and educational incentives. By securing this SK Endorsement, young applicants are better positioned to access lawful, age-appropriate work that balances their need for income, skills development, and continued schooling—without being prematurely pushed into full-time employment.', 'Your SK Endorsement Certification is ready. A digital copy has been sent to your SKonnect Inbox. You may also pick up the hard copy with the official dry seal at the Secretary\'s desk.', 'Students aged 15-17', '2 days', '- Parent\'s Consent Form\r\n- School ID or Recent Report Card', '', 'Sample PDF Attachment.pdf', '/uploads/forms/form_69ead8d6506b88.36647495_Sample_PDF_Attachment.pdf', NULL, 0, 'active', 14, '2026-04-24 10:43:34', '2026-04-24 10:49:24'),
(40, 'Barangay Clearance for First-Time Job Seekers', 'legal', 'appointment', 'Under Republic Act No. 11261, also known as the First Time Jobseekers Assistance Act, first-time job seekers in the Philippines are entitled to receive a free barangay clearance and Sangguniang Kabataan (SK) certification, provided they meet the qualifications outlined in the law. This means that any Filipino citizen who is applying for a job for the first time, has no prior work experience recorded with the Social Security System (SSS), and is not yet covered by any government-mandated employment benefits can secure these essential pre-employment documents without paying the usual issuance fees. The barangay clearance serves as a basic certification of good moral character and residency within the community, while the SK certification—issued specifically for youth aged 15 to 30—affirms eligibility for youth-focused programs and services. By removing the financial burden that these requirements often impose on new entrants to the workforce, the law aims to ease the transition from school or training to employment, thereby promoting inclusive economic participation and reducing barriers to lawful, documented work. This privilege applies nationwide, and any barangay or SK official who refuses to issue these documents free of charge, or imposes any condition not specified in the Act, may face administrative penalties in accordance with the law.', 'Your First-Time Job Seeker certification is ready for pickup. Please bring one 1x1 photo for the document. This service is free of charge.', 'First-time job seekers (Residents)', '1 day', '- Oath of Undertaking (available at the office)\r\n- Valid ID or Birth Certificate', '', 'Sample PDF Attachment.pdf', '/uploads/forms/form_69ead909ebf008.20423994_Sample_PDF_Attachment.pdf', NULL, 1, 'active', 14, '2026-04-24 10:44:25', '2026-04-24 10:53:21'),
(41, 'Calamity Emergency Relief Pack', 'assistance', 'document', 'This service is a cornerstone of the SK Council’s disaster response framework, specifically designed to provide a rapid and compassionate lifeline to youth-led households or families with youth members who have been displaced or severely affected by natural disasters. Whether the community is reeling from the torrential rains and flooding of a typhoon or the devastating loss of property due to a fire incident, this program ensures that the most basic human needs are met with dignity and efficiency.', 'Your relief request is confirmed. Please proceed to the Barangay Covered Court, Window 3. Present your SKonnect QR code for scanning and faster release of your pack.', 'Affected residents in declared calamity zones', 'Immediate (within 24 hours of calamity)', '- Proof of Residency (or inclusion in the calamity masterlist)', '', 'Sample PDF Attachment.pdf', '/uploads/forms/form_69ead93e8e6204.63808019_Sample_PDF_Attachment.pdf', 500, 0, 'active', 14, '2026-04-24 10:45:18', '2026-04-24 10:47:24');

-- --------------------------------------------------------

--
-- Table structure for table `service_applications`
--

CREATE TABLE `service_applications` (
  `id` int(10) UNSIGNED NOT NULL,
  `service_id` int(10) UNSIGNED NOT NULL,
  `resident_id` int(10) UNSIGNED NOT NULL COMMENT 'FK to users/residents table',
  `full_name` varchar(150) NOT NULL DEFAULT '',
  `contact` varchar(30) NOT NULL DEFAULT '',
  `email` varchar(150) NOT NULL DEFAULT '',
  `address` varchar(255) NOT NULL DEFAULT '',
  `status` enum('pending','action_required','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `purpose` text DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `fulfillment_file` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_applications`
--

INSERT INTO `service_applications` (`id`, `service_id`, `resident_id`, `full_name`, `contact`, `email`, `address`, `status`, `purpose`, `submitted_at`, `updated_at`, `fulfillment_file`) VALUES
(23, 40, 24, 'Ico Etelliv', '09199531108', 'villete.leonardo.buya@gmail.com', 'Caloocan City', 'pending', 'Descriptions', '2026-04-24 10:53:21', '2026-04-24 10:53:21', NULL),
(24, 36, 24, 'Rico Villete', '09199531108', 'villete.leonardo.buya@gmail.com', 'Caloocan City', 'pending', 'Purpose of descriptions', '2026-04-24 10:53:59', '2026-04-24 10:53:59', NULL),
(25, 34, 24, 'Rico Villete', '09199531108', 'villete.leonardo.buya@gmail.com', 'Caloocan City', 'pending', 'Description of purpose', '2026-04-24 10:54:29', '2026-04-24 10:54:29', NULL),
(26, 35, 25, 'Juan Del Monte', '09199531108', 'leovillete878@gmail.com', 'Caloocan City', 'pending', 'Brief descriptions', '2026-04-24 10:56:16', '2026-04-24 10:56:16', NULL),
(27, 34, 25, 'Juan Dela Monte', '09199531108', 'leovillete878@gmail.com', 'Caloocan City', 'approved', 'Explained description', '2026-04-24 10:56:55', '2026-04-26 10:47:44', '/uploads/fulfillment/fulfillment_69ed7cd05d2a01.25038305_Sample_PDF_Attachment.pdf'),
(28, 36, 27, 'Rico Villete', '09199531108', 'lvillete778@gmail.com', 'Caloocan City', 'pending', 'For my recently deceased father', '2026-05-08 09:46:47', '2026-05-08 09:46:47', NULL),
(29, 35, 27, 'Rico Villete', '09199531108', 'lvillete778@gmail.com', 'Caloocan City', 'pending', 'I need training', '2026-05-08 09:47:19', '2026-05-08 09:47:19', NULL),
(30, 38, 27, 'Rico Villete', '09199531108', 'lvillete778@gmail.com', 'Caloocan City', 'action_required', 'Needed for school purposes', '2026-05-08 09:48:00', '2026-05-08 09:48:42', NULL);

--
-- Triggers `service_applications`
--
DELIMITER $$
CREATE TRIGGER `trg_release_slot_on_rejection` AFTER UPDATE ON `service_applications` FOR EACH ROW BEGIN
    IF (NEW.status IN ('rejected', 'cancelled')) AND (OLD.status NOT IN ('rejected', 'cancelled')) THEN
        UPDATE `services`
        SET `current_count` = GREATEST(0, `current_count` - 1)
        WHERE `id` = NEW.service_id;

        UPDATE `services`
        SET `status` = 'active'
        WHERE `id` = NEW.service_id
          AND `max_capacity` IS NOT NULL
          AND `current_count` < `max_capacity`
          AND `status` = 'inactive';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_reserve_slot_on_insert` AFTER INSERT ON `service_applications` FOR EACH ROW BEGIN
    UPDATE `services`
    SET `current_count` = `current_count` + 1
    WHERE `id` = NEW.service_id;

    UPDATE `services`
    SET `status` = 'inactive'
    WHERE `id` = NEW.service_id
      AND `max_capacity` IS NOT NULL
      AND `current_count` >= `max_capacity`;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`key`, `value`, `updated_at`) VALUES
('brgy_about', '', '2026-04-23 08:20:25'),
('brgy_address', '', '2026-04-23 08:20:25'),
('brgy_contact', '', '2026-04-23 08:20:25'),
('brgy_email', '', '2026-04-23 08:20:25'),
('brgy_municipality', '', '2026-04-23 08:20:25'),
('brgy_name', '', '2026-04-23 08:20:25'),
('brgy_province', '', '2026-04-23 08:20:25'),
('favicon_path', '', '2026-04-23 08:20:25'),
('logo_path', '', '2026-04-23 08:20:25'),
('sys_email', '', '2026-04-23 08:20:25'),
('sys_name', 'SKonnect', '2026-04-23 08:20:25'),
('sys_tagline', '', '2026-04-23 08:20:25'),
('sys_version', '1.0.0', '2026-04-23 08:20:25');

-- --------------------------------------------------------

--
-- Table structure for table `threads`
--

CREATE TABLE `threads` (
  `id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `category` enum('inquiry','complaint','suggestion','event_question','other') NOT NULL,
  `subject` varchar(120) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','responded','resolved') NOT NULL DEFAULT 'pending',
  `is_removed` tinyint(1) NOT NULL DEFAULT 0,
  `removed_by_user` tinyint(1) NOT NULL DEFAULT 0,
  `is_flagged` tinyint(1) NOT NULL DEFAULT 0,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `threads`
--

INSERT INTO `threads` (`id`, `author_id`, `category`, `subject`, `message`, `status`, `is_removed`, `removed_by_user`, `is_flagged`, `is_pinned`, `created_at`, `updated_at`) VALUES
(41, 24, 'inquiry', 'SK Scholarship Requirements 2026', 'Magandang araw po sa SK Council! Gusto ko lang po sana itanong kung ano ang mga requirements para mag-apply sa scholarship program ngayong taon? Maraming salamat po sa pagsagot!', 'pending', 0, 0, 0, 0, '2026-04-24 10:17:32', '2026-04-24 10:17:32'),
(42, 24, 'complaint', 'Broken Lights at Phase 3 Basketball Court', 'Good evening. I would like to report that the floodlights at the Phase 3 court have been flickering for a week, and now two of them are completely dead. It\'s dangerous for the kids playing late in the afternoon. Hope you can fix this soon.', 'resolved', 0, 0, 0, 0, '2026-04-24 10:17:54', '2026-04-26 20:25:54'),
(43, 24, 'suggestion', 'Proposal: Monthly E-Sports Tournament', 'Instead of just basketball and volleyball, why don\'t we host a Mobile Legends or Valorant tournament? Most of the youth in our barangay are into gaming, and this could be a great way to promote sportsmanship and teamwork digitally.', 'responded', 0, 0, 0, 0, '2026-04-24 10:18:21', '2026-04-27 18:29:44'),
(44, 24, 'event_question', 'Saan po ang venue ng Summer Liga?', 'Hi SK! Hindi ko po kasi makita sa post kung saan gaganapin yung opening ceremony ng Summer Liga. Sa main plaza po ba ito o sa covered court ng Zone 4? Thank you po!', 'responded', 0, 0, 0, 1, '2026-04-24 10:18:40', '2026-04-24 11:09:16'),
(45, 24, 'complaint', 'Transparency on SK Fund Allocation', 'I\'ve been checking the transparency board, but I haven\'t seen any updates regarding the budget spent for the Linggo ng Kabataan last year. Can we get a detailed breakdown of where the funds went? Transparency is key for our trust.', 'pending', 0, 0, 0, 0, '2026-04-24 10:19:00', '2026-04-24 10:19:00'),
(46, 25, 'other', 'Request to borrow SK Sound System', 'Requesting lang po sana kami kung pwedeng mahiram yung sound system ng SK para sa aming clean-up drive activity sa Sunday. Kami na po ang bahala sa transpo at kuryente. Maraming salamat po, SK Chair!', 'pending', 0, 0, 0, 0, '2026-04-24 10:22:14', '2026-04-30 20:24:21'),
(47, 25, 'suggestion', 'Mental Health Seminar for Senior High', 'With the increasing pressure of college entrance exams, maybe the SK can host a mental health and stress management seminar for Grade 12 students. It would be very timely and helpful for the graduating batch.', 'responded', 0, 0, 0, 0, '2026-04-24 10:23:20', '2026-04-26 20:51:52'),
(48, 25, 'inquiry', 'Application for SPES (Summer Job)', 'Kailan po kaya magsisimula ang pag-tanggap ng application para sa SPES (Special Program for Employment of Students)? Sana po ma-prioritize yung mga students na working students talaga. Abangan ko po ang reply niyo.', 'resolved', 0, 0, 0, 0, '2026-04-24 10:23:37', '2026-04-27 18:30:30'),
(49, 25, 'event_question', 'Youth Summit: Is there a registration fee?', 'I’m interested in joining the upcoming Youth Summit this weekend. Is the event free of charge, or do we need to pay for the materials and food? Also, is there a deadline for the online registration?', 'pending', 0, 0, 0, 0, '2026-04-24 10:23:55', '2026-04-24 10:23:55'),
(50, 25, 'inquiry', 'Missing SK ID - How to replace?', 'Hi! I lost my SK membership ID during the recent flooding. Ano po ang process for replacement? May bayad po ba or kailangan lang ng Affidavit of Loss? Please let me know the steps. Thanks!', 'responded', 0, 0, 0, 0, '2026-04-24 10:24:11', '2026-04-26 10:53:32');

-- --------------------------------------------------------

--
-- Table structure for table `thread_bookmarks`
--

CREATE TABLE `thread_bookmarks` (
  `id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thread_bookmarks`
--

INSERT INTO `thread_bookmarks` (`id`, `thread_id`, `user_id`, `created_at`) VALUES
(221, 46, 24, '2026-04-24 11:07:42'),
(222, 44, 25, '2026-04-24 18:20:20'),
(223, 47, 25, '2026-04-27 18:24:54'),
(225, 44, 27, '2026-05-08 09:51:11');

-- --------------------------------------------------------

--
-- Table structure for table `thread_comments`
--

CREATE TABLE `thread_comments` (
  `id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_removed` tinyint(1) NOT NULL DEFAULT 0,
  `removed_by_mod` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = hidden by a moderator sanction (shows tombstone); 0 = not mod-removed',
  `removed_by_user` tinyint(1) NOT NULL DEFAULT 0,
  `is_mod_comment` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thread_comments`
--

INSERT INTO `thread_comments` (`id`, `thread_id`, `author_id`, `message`, `is_removed`, `removed_by_mod`, `removed_by_user`, `is_mod_comment`, `created_at`) VALUES
(83, 50, 25, 'I don\'t know', 0, 0, 0, 0, '2026-04-24 10:24:33'),
(84, 44, 25, 'Comment', 0, 0, 0, 0, '2026-04-24 11:06:04'),
(85, 44, 25, 'Comment #2', 0, 0, 0, 0, '2026-04-24 11:06:08'),
(86, 44, 25, 'Comment #3', 0, 0, 0, 0, '2026-04-24 11:06:12'),
(87, 43, 25, 'Commented', 0, 0, 0, 0, '2026-04-24 11:06:38'),
(88, 43, 25, 'Posted Comment', 0, 0, 0, 0, '2026-04-24 11:06:42'),
(89, 45, 25, 'Commenting', 0, 0, 0, 0, '2026-04-24 11:07:06'),
(90, 46, 24, 'Comments', 0, 0, 0, 0, '2026-04-24 11:07:59'),
(91, 44, 13, 'Official Response', 0, 0, 0, 1, '2026-04-24 11:09:03'),
(92, 50, 13, 'Idk', 0, 0, 0, 1, '2026-04-26 10:52:25'),
(93, 47, 13, 'Testy Comment Status Auto Update', 0, 0, 0, 1, '2026-04-26 20:51:52'),
(94, 48, 13, 'Commentses', 0, 0, 0, 1, '2026-04-26 20:53:15'),
(95, 43, 13, 'Comment', 0, 0, 0, 1, '2026-04-27 18:29:44'),
(96, 45, 27, 'Test comment', 0, 0, 0, 0, '2026-05-08 09:49:39'),
(97, 42, 27, 'It is not broken', 0, 0, 0, 0, '2026-05-08 09:50:27');

-- --------------------------------------------------------

--
-- Table structure for table `thread_images`
--

CREATE TABLE `thread_images` (
  `id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thread_images`
--

INSERT INTO `thread_images` (`id`, `thread_id`, `file_name`, `file_path`, `uploaded_at`) VALUES
(66, 46, 'parrot.jpg', 'uploads/threads/46_69ead3d74e778.jpg', '2026-04-24 10:22:15'),
(67, 46, 'highres-Canon-EOS-M50-Black-2_1519289103.jpg', 'uploads/threads/46_69ead3d819708.jpg', '2026-04-24 10:22:16'),
(68, 46, 'images (1).jpg', 'uploads/threads/46_69ead3d87cb50.jpg', '2026-04-24 10:22:16'),
(69, 46, 'lebon.jpg', 'uploads/threads/46_69ead3d8b6148.jpg', '2026-04-24 10:22:16'),
(70, 47, 'images (2).jpg', 'uploads/threads/47_69ead41898968.jpg', '2026-04-24 10:23:20'),
(71, 47, 'images.jpg', 'uploads/threads/47_69ead418a4cb8.jpg', '2026-04-24 10:23:20'),
(72, 47, 'shbrek.jpg', 'uploads/threads/47_69ead418b2c28.jpg', '2026-04-24 10:23:20');

-- --------------------------------------------------------

--
-- Table structure for table `thread_reports`
--

CREATE TABLE `thread_reports` (
  `id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL,
  `reporter_id` int(11) NOT NULL,
  `category` enum('inappropriate','spam','misinformation','harassment') NOT NULL,
  `note` text DEFAULT NULL,
  `status` enum('pending','reviewed','dismissed') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thread_reports`
--

INSERT INTO `thread_reports` (`id`, `thread_id`, `reporter_id`, `category`, `note`, `status`, `created_at`) VALUES
(18, 50, 24, 'misinformation', NULL, 'dismissed', '2026-04-24 11:07:32'),
(19, 49, 24, 'inappropriate', NULL, 'pending', '2026-04-24 11:07:38'),
(20, 46, 24, 'harassment', 'Harassing', 'reviewed', '2026-04-24 11:07:55'),
(21, 42, 27, 'misinformation', NULL, 'pending', '2026-05-08 09:50:32'),
(22, 43, 27, 'inappropriate', NULL, 'pending', '2026-05-08 09:50:47');

-- --------------------------------------------------------

--
-- Table structure for table `thread_supports`
--

CREATE TABLE `thread_supports` (
  `id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thread_supports`
--

INSERT INTO `thread_supports` (`id`, `thread_id`, `user_id`, `created_at`) VALUES
(156, 50, 24, '2026-04-24 11:07:22'),
(157, 49, 24, '2026-04-24 11:07:24'),
(158, 48, 24, '2026-04-24 11:07:27'),
(159, 47, 24, '2026-04-24 11:07:28'),
(162, 46, 25, '2026-04-24 18:20:00'),
(163, 43, 25, '2026-04-24 18:20:01'),
(164, 44, 25, '2026-04-24 18:20:25'),
(165, 49, 25, '2026-04-26 19:37:50'),
(166, 50, 25, '2026-04-27 18:24:50'),
(167, 47, 25, '2026-04-27 18:24:52'),
(168, 48, 27, '2026-05-08 09:51:12'),
(169, 47, 27, '2026-05-08 09:51:13'),
(170, 49, 27, '2026-05-08 09:51:15'),
(171, 43, 27, '2026-05-08 09:51:17'),
(172, 44, 27, '2026-05-08 09:51:18');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `birth_date` date NOT NULL,
  `age` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `role` enum('resident','moderator','sk_officer','admin') NOT NULL DEFAULT 'resident',
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expires` datetime DEFAULT NULL,
  `verify_token` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `middle_name`, `gender`, `birth_date`, `age`, `email`, `password`, `is_verified`, `role`, `otp_code`, `otp_expires`, `verify_token`, `created_at`, `verified_at`) VALUES
(12, 'Rey', 'Santos', 'Cruz', 'male', '2000-03-15', 25, 'admin@skonnect.com', '$2y$10$KzsjmePIGxKHotu8yqddMeo.0ymj9w8yV2pQzWG8Lq.uERZMXrBTS', 1, 'admin', NULL, NULL, NULL, '2026-03-04 09:09:42', '2026-03-04 09:09:42'),
(13, 'Maya', 'Reyes', 'Lim', 'female', '1998-07-22', 27, 'moderator@skonnect.com', '$2y$10$TJ.CZA5ds2Zy1/WM0AInzOJ1h2gkdgILcaXT.s.MRt/k6Aq2E3g1K', 1, 'moderator', NULL, NULL, NULL, '2026-03-04 09:09:42', '2026-03-04 09:09:42'),
(14, 'Carlo', 'Mendoza', 'Bautista', 'male', '1995-11-05', 30, 'officer@skonnect.com', '$2y$10$fMLkG3QvcG0mJI359fgqr.2aC4aE.e4NFB2Bk4meQySGsuICdhPCO', 1, 'sk_officer', NULL, NULL, NULL, '2026-03-04 09:09:42', '2026-03-04 09:09:42'),
(24, 'Ico', 'Etelliv', '', 'male', '2005-06-01', 20, 'villete.leonardo.buya@gmail.com', '$2y$10$9oWGm2C/h6LZMr/5cmOmr.dW/fcp9qvxKBr.nDVdO3a/khVpotgKy', 1, 'resident', NULL, NULL, NULL, '2026-04-24 02:10:26', '2026-04-24 02:10:54'),
(25, 'Bico', 'Etelyev', '', 'male', '2003-12-15', 22, 'leovillete878@gmail.com_deleted_1777777472', '$2y$10$0zwvdcQ3GMCew1QR78MdZ.Mm4vBle2KvG/TgOGj4XT5V7YEUgAc6.', 1, 'resident', NULL, NULL, NULL, '2026-04-24 02:21:05', '2026-04-24 02:21:27'),
(26, 'Dun', 'Cruz', 'Optional', 'male', '2000-07-24', 25, 'lvillete778@gmail.com_deleted_1777805603', '$2y$10$.MH63iXpjBX2ZOBiD6p/MOUldaBol60ZAIp7CKaiBKW1C2rNp9igu', 1, 'resident', NULL, NULL, NULL, '2026-05-03 10:11:51', '2026-05-03 10:12:18'),
(27, 'Jun', 'Druz', 'key', 'male', '2003-09-08', 22, 'lvillete778@gmail.com', '$2y$10$BjmtScQKCd.CFUNIdaR2eeRbVmyxFJtJMz2gff6.cS1S/tJrek2pK', 1, 'resident', NULL, NULL, NULL, '2026-05-03 10:54:21', '2026-05-03 10:54:41');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `trg_user_profile_init` AFTER INSERT ON `users` FOR EACH ROW INSERT IGNORE INTO `user_profiles` (`user_id`) VALUES (NEW.`id`)
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_user_status_init` AFTER INSERT ON `users` FOR EACH ROW INSERT INTO `user_status` (`user_id`) VALUES (NEW.`id`)
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `user_id` int(11) NOT NULL,
  `mobile_number` varchar(20) DEFAULT NULL COMMENT 'CRITICAL — required for full profile',
  `purok` varchar(100) DEFAULT NULL COMMENT 'CRITICAL — required for full profile',
  `street_address` varchar(255) DEFAULT NULL,
  `civil_status` enum('single','married','widowed','separated','annulled') DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `educational_attainment` enum('elementary','high_school','senior_high','vocational','college_level','college_graduate','post_graduate') DEFAULT NULL,
  `school_institution` varchar(255) DEFAULT NULL,
  `course_strand` varchar(255) DEFAULT NULL,
  `employment_status` enum('student','employed','unemployed','self_employed') DEFAULT NULL,
  `is_registered_voter` tinyint(1) NOT NULL DEFAULT 0,
  `avatar_path` varchar(500) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`user_id`, `mobile_number`, `purok`, `street_address`, `civil_status`, `nationality`, `religion`, `educational_attainment`, `school_institution`, `course_strand`, `employment_status`, `is_registered_voter`, `avatar_path`, `updated_at`) VALUES
(12, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-21 12:49:47'),
(13, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-21 12:49:47'),
(14, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-21 12:49:47'),
(24, '09999999999', 'Purok 4', '124 Sampaluya St.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-05-03 03:15:40'),
(25, '09999999999', 'Purok 3', '123 Sampaguita st.', 'single', 'Filipino', 'Roman Catholic', 'college_level', 'Quezon City University', 'BS Information Technology', 'student', 1, NULL, '2026-05-03 01:46:52'),
(26, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-05-03 10:11:51'),
(27, '09999999999', 'Purok 3', '125 Sampaguita St.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-05-08 01:43:43');

-- --------------------------------------------------------

--
-- Table structure for table `user_sanctions`
--

CREATE TABLE `user_sanctions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `issued_by` int(11) NOT NULL,
  `level` tinyint(1) NOT NULL DEFAULT 1,
  `reason` text NOT NULL,
  `report_id` int(11) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_status`
--

CREATE TABLE `user_status` (
  `user_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_banned` tinyint(1) NOT NULL DEFAULT 0,
  `banned_reason` varchar(255) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `feed_ban_level` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=none, 2=7-day ban, 3=permanent ban',
  `feed_ban_expires` datetime DEFAULT NULL COMMENT 'NULL = permanent; populated for level-2 7-day bans',
  `login_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Failed attempts in current window (resets to 0 after lockout is applied or on success)',
  `lockout_until` datetime DEFAULT NULL COMMENT 'NULL = not locked; future timestamp = account locked until this time',
  `lockout_level` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Escalation tier; increments each lockout. Duration = min(30, 5 + level*5) minutes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_status`
--

INSERT INTO `user_status` (`user_id`, `is_active`, `is_banned`, `banned_reason`, `is_deleted`, `deleted_at`, `feed_ban_level`, `feed_ban_expires`, `login_attempts`, `lockout_until`, `lockout_level`) VALUES
(12, 1, 0, NULL, 0, NULL, 0, NULL, 0, NULL, 0),
(13, 1, 0, NULL, 0, NULL, 0, NULL, 0, NULL, 0),
(14, 1, 0, NULL, 0, NULL, 0, NULL, 0, NULL, 0),
(24, 1, 0, NULL, 0, NULL, 0, NULL, 0, NULL, 0),
(25, 0, 0, NULL, 1, '2026-05-03 03:04:32', 0, NULL, 0, NULL, 0),
(26, 0, 0, NULL, 1, '2026-05-03 10:53:23', 0, NULL, 0, NULL, 0),
(27, 1, 0, NULL, 0, NULL, 0, NULL, 0, NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_al_user` (`user_id`),
  ADD KEY `idx_al_action` (`action`),
  ADD KEY `idx_al_created` (`created_at`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_announcement_authorA` (`author_id`);

--
-- Indexes for table `announcement_bookmarks`
--
ALTER TABLE `announcement_bookmarks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_announcement` (`user_id`,`announcement_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_announcement_id` (`announcement_id`);

--
-- Indexes for table `announcement_files`
--
ALTER TABLE `announcement_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `announcement_id` (`announcement_id`);

--
-- Indexes for table `application_documents`
--
ALTER TABLE `application_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_doc_application` (`application_id`);

--
-- Indexes for table `application_notes`
--
ALTER TABLE `application_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_an_application` (`application_id`);

--
-- Indexes for table `comment_replies`
--
ALTER TABLE `comment_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reply_comment` (`comment_id`),
  ADD KEY `fk_reply_author` (`author_id`);

--
-- Indexes for table `comment_reports`
--
ALTER TABLE `comment_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_comment_report` (`target_type`,`target_id`,`reporter_id`),
  ADD KEY `fk_cr_reporter` (`reporter_id`);

--
-- Indexes for table `comment_supports`
--
ALTER TABLE `comment_supports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_comment_support` (`comment_id`,`user_id`),
  ADD KEY `fk_csupport_user` (`user_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_date` (`event_date`),
  ADD KEY `fk_event_author` (`created_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_user` (`user_id`),
  ADD KEY `idx_notif_user_dismissed` (`user_id`,`is_dismissed`),
  ADD KEY `idx_notif_user_read` (`user_id`,`is_read`),
  ADD KEY `idx_notif_created` (`created_at`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`service_type`);

--
-- Indexes for table `service_applications`
--
ALTER TABLE `service_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_service` (`service_id`),
  ADD KEY `idx_resident` (`resident_id`),
  ADD KEY `idx_app_status` (`status`),
  ADD KEY `idx_sa_resident` (`resident_id`),
  ADD KEY `idx_sa_service` (`service_id`),
  ADD KEY `idx_sa_status` (`status`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `threads`
--
ALTER TABLE `threads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_thread_author` (`author_id`),
  ADD KEY `idx_is_pinned` (`is_pinned`);

--
-- Indexes for table `thread_bookmarks`
--
ALTER TABLE `thread_bookmarks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_bookmark` (`thread_id`,`user_id`),
  ADD KEY `fk_bookmark_user` (`user_id`);

--
-- Indexes for table `thread_comments`
--
ALTER TABLE `thread_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_comment_thread` (`thread_id`),
  ADD KEY `fk_comment_author` (`author_id`);

--
-- Indexes for table `thread_images`
--
ALTER TABLE `thread_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_img_thread` (`thread_id`);

--
-- Indexes for table `thread_reports`
--
ALTER TABLE `thread_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_thread_report` (`thread_id`,`reporter_id`),
  ADD KEY `fk_tr_thread` (`thread_id`),
  ADD KEY `fk_tr_reporter` (`reporter_id`);

--
-- Indexes for table `thread_supports`
--
ALTER TABLE `thread_supports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_thread_support` (`thread_id`,`user_id`),
  ADD KEY `fk_support_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_otp` (`otp_code`),
  ADD KEY `idx_role` (`role`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_sanctions`
--
ALTER TABLE `user_sanctions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_us_user` (`user_id`),
  ADD KEY `idx_us_issued` (`issued_by`),
  ADD KEY `idx_us_report` (`report_id`);

--
-- Indexes for table `user_status`
--
ALTER TABLE `user_status`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `idx_us_active` (`is_active`),
  ADD KEY `idx_us_banned` (`is_banned`),
  ADD KEY `idx_us_deleted` (`is_deleted`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `announcement_bookmarks`
--
ALTER TABLE `announcement_bookmarks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `announcement_files`
--
ALTER TABLE `announcement_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `application_documents`
--
ALTER TABLE `application_documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `application_notes`
--
ALTER TABLE `application_notes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `comment_replies`
--
ALTER TABLE `comment_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `comment_reports`
--
ALTER TABLE `comment_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `comment_supports`
--
ALTER TABLE `comment_supports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `service_applications`
--
ALTER TABLE `service_applications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `threads`
--
ALTER TABLE `threads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `thread_bookmarks`
--
ALTER TABLE `thread_bookmarks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=226;

--
-- AUTO_INCREMENT for table `thread_comments`
--
ALTER TABLE `thread_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `thread_images`
--
ALTER TABLE `thread_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `thread_reports`
--
ALTER TABLE `thread_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `thread_supports`
--
ALTER TABLE `thread_supports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=173;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `user_sanctions`
--
ALTER TABLE `user_sanctions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_al_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_announcement_authorA` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `announcement_bookmarks`
--
ALTER TABLE `announcement_bookmarks`
  ADD CONSTRAINT `fk_bm_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `announcement_files`
--
ALTER TABLE `announcement_files`
  ADD CONSTRAINT `fk_attachment_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `application_documents`
--
ALTER TABLE `application_documents`
  ADD CONSTRAINT `fk_appdoc_application` FOREIGN KEY (`application_id`) REFERENCES `service_applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `application_notes`
--
ALTER TABLE `application_notes`
  ADD CONSTRAINT `fk_an_application` FOREIGN KEY (`application_id`) REFERENCES `service_applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comment_replies`
--
ALTER TABLE `comment_replies`
  ADD CONSTRAINT `fk_reply_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reply_comment` FOREIGN KEY (`comment_id`) REFERENCES `thread_comments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comment_reports`
--
ALTER TABLE `comment_reports`
  ADD CONSTRAINT `fk_cr_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comment_supports`
--
ALTER TABLE `comment_supports`
  ADD CONSTRAINT `fk_csupport_comment` FOREIGN KEY (`comment_id`) REFERENCES `thread_comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_csupport_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `fk_event_author` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_applications`
--
ALTER TABLE `service_applications`
  ADD CONSTRAINT `fk_app_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `threads`
--
ALTER TABLE `threads`
  ADD CONSTRAINT `fk_thread_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `thread_bookmarks`
--
ALTER TABLE `thread_bookmarks`
  ADD CONSTRAINT `fk_bookmark_thread` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bookmark_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `thread_comments`
--
ALTER TABLE `thread_comments`
  ADD CONSTRAINT `fk_comment_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comment_thread` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `thread_images`
--
ALTER TABLE `thread_images`
  ADD CONSTRAINT `fk_img_thread` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `thread_reports`
--
ALTER TABLE `thread_reports`
  ADD CONSTRAINT `fk_tr_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tr_thread` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `thread_supports`
--
ALTER TABLE `thread_supports`
  ADD CONSTRAINT `fk_support_thread` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_support_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `fk_up_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_sanctions`
--
ALTER TABLE `user_sanctions`
  ADD CONSTRAINT `fk_us_issuer` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_us_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_status`
--
ALTER TABLE `user_status`
  ADD CONSTRAINT `fk_user_status_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
