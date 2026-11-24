# SKonnect

A community portal that connects residents with their SK officers: announcements, community discussions, youth services, and events in one place.

> **Last Update:** 11/18/26

## Table of Contents

- [Setup](#setup)
- [System Users](#system-users)
- [Login Credentials](#login-credentials)
- [Features](#features)
  - [Feature to be Implemented](#feature-to-be-implemented)
  - [Implemented Major Features](#implemented-major-features)
- [Refactoring for Database Migration](#refactoring-for-database-migration)
- [Known Issues](#known-issues)
- [Deployment](#deployment-issues-render)
- [Features Backlog](#features-backlog)

## Setup
- Clone the project from the Repo
- Paste the .env file on the project root
- Install composer
- Configure the Apache's php.ini then restart Apache
- Test

## System Users

| Role     | Description |
| --- | --- |
| **Residents** | Standard users. Can view announcements, interact with community posts, apply to offered services, view their request status, and receive in-system and email notifications about their services status, community posts, and event developments. |
| **Officer** | Focuses on posting, editing, and managing announcements. Also manages services as well as service requests (approve, reject, respond). Handles events, and views analytics, reports, and users. |
| **Moderator** | Focuses on community control. Can remove or reply to threads. Handles reports such as spam and harassment, sends warnings to users, and updates the status of threads. |
| **Admin** | Has full authority, control, and access to everything. Can manage, create, delete, or configure users, change system configs, and access system logs. Can do everything the system offers. |

---

## Login Credentials

| Email | Password |
| --- | --- |
| `admin@skonnect.com` | `passwords` |
| `moderator@skonnect.com` | `passwords` |
| `officer@skonnect.com` | `passwords` |


## Features

### Feature to be Implemented

- [x] AI Chatbot

### Implemented Major Features

<details open>
<summary><b>Public view</b></summary>

- View announcements
- View available services (application requires login)
- View community feed (interaction requires login)
- Register an account
- Login registered account (authentication)
- Login admin accounts (authorization)

</details>

<details open>
<summary><b>Portal view</b></summary>

- View upcoming events (calendar based)
- View and bookmark an announcement
- View, create, comment, reply, support, report, and bookmark a thread in community feed
- View and apply for available services
- View, edit, and resubmit recent user applications
- View, mark as read, and dismiss notifications
- Setup basic profile for personal insights about the user's account

</details>

<details open>
<summary><b>Officer panel</b></summary>

- Create and configure announcements
- Create, configure, and activate/deactivate a service
- Approve, reject, and require revision of a service application
- Mark upcoming events on calendar
- View system analytics (Officer module only)
- View system users for basic insights (not including admin users)

</details>

<details open>
<summary><b>Moderator panel</b></summary>

- View community feed
- Set thread status
- Comment/reply on threads
- Hide threads, comments, and replies
- Ban/sanction specific users
- Handle reports (sanction, remove or dismiss)
- View moderators activity logs

</details>

<details open>
<summary><b>Admin panel</b></summary>

- Create and configure announcements
- Manage services
- Manage service applications
- Manage threads
- Manage community reports
- Manage all system users (add, ban, remove, change role)
- View system-wide analytics
- View admin activity logs

</details>


## Refactoring for Database Migration

**Status: Complete ✔️**

Completed:

- [x] Authentication/Authorization
- [x] Announcements Module
- [x] Community Feed Module
- [x] Services Module
- [x] Profile Page (resident)
- [x] Notifications Module (resident)
- [x] Dashboard (resident, officer, moderator, admin)
- [x] Admin module


## Known Issues

### [ GENERAL ]

- [x] Design inconsistencies for buttons, dropdowns, etc.
- [x] After logging in, clicking the 'back' button of a browser returns the user to the login page. *(fixed maybe)*
- [x] Empty fields like threads, reports, etc. show either two "no record yet" messages or misaligned (not centered) text.
- [x] Inconsistent toast design across all user views.
- [x] Make the notifications on admin users (officer, moderator, admin) a modal.
- [ ] Notifications 'unread' status look on each entry
- [ ] Update public panel footer
- [ ] Make a 404 page (optional)

### [ PUBLIC SIDE ]

#### Login page
- [x] No loading visualization when clicking the "Login" button
- [ ] Forgot Password non functional

#### Registration
- [ ] No password restrictions
- [x] No loading visualization on the 'register' button

#### OTP Verification
- [x] No loading visualization on the 'verify' button

#### Services
- [x] Bug with modal appearing and the navbar

### [ PORTAL SIDE ]

#### Dashboard
- [x] Event in calendar shows "Tomorrow" even though the event is still 2 days from now
- [x] Include a services section for easy viewing
- [x] Include latest community discussions (trending threads)
- [x] Hide the scrollbars on each section
- [x] AI better context and knowledge for every responses
- [x] Better look on the Chatbot interface

#### Community Feed
- [x] The icons are emojis, change it to SVGs
- [x] Change the support icon to a simple heart instead of whatever that is
- [x] In the bookmarks section fix the border colors
- [x] Maybe fix the pill colors, some pills have the same colors despite meaning differently
- [x] The pictures are not showing when posted by users
- [x] Post a thread modal automatically closes when clicking outside the modal

#### Notifications
- [x] Color and icon of "action require" service requests
- [x] Viewing a notification doesn't auto "mark as read" it

#### Profile
- [x] User info improvements

#### Services
- [x] Clicking outside the modal closes the modal resulting in loss of progress
- [x] Make the "Submit Request" button unclickable if all fields are not complete yet
- [x] On the request modal, make the info strip non-sticky to allow more space for viewing the form
- [x] Bug, multiple services entries appearing on one application

#### Notif Badge
- [x] Counter appears without any new notifications when opening "view" pages (`thread_view.php`, `announcement_view.php`)

### [ OFFICER SIDE ]

#### Events Page
- [x] Clicking outside the add event modal closes the modal resulting in loss of progress
- [x] New events appear at the bottom of the list

#### Notification badge
- [x] Notification badge non functional

#### Services
- [x] No confirmation modal when deactivating or activating a service

#### Analytics
- [x] Some visuals are bugged

### [ MODERATOR SIDE ]

#### Dashboard
- [x] Styles for containers
- [x] Fix the layouts of each section

#### Community Feed
- [x] Commenting or replying auto updates the status of the thread to 'responded' but it is not shown immediately because the page needs to refresh first.
- [x] Some icons are emojis
- [x] Pills should be one line, change the "Hidden" pill to just an icon to preserve space

#### Notifications
- [x] Notifications non functional

### [ ADMIN SIDE ]

- [x] Mostly non functional, styles are inconsistent with other related modules on different users
- [x] Notifications feature (same with officer and moderator)

#### Dashboard
- [x] Update the sections
- [x] Add sections for other modules (threads, reports, recent announcements)

#### Services
- [x] No confirmation modal when activating/deactivating a service

#### Activity Logs
- [x] Activities from the officer are not logged

### Deployment Issues (Render)

- [ ] Complete and fix deployment before presentation
- [x] officer_services.php - cannot deactivate/activate a service
- [x] mod_feed.php - meta data for each card are two-lines (truncate the author name)
- [x] admin_threads.php - thread images (similar issue from mod panel) 
- [x] mod_feed.php and admin_threads.php - the title-description design rule
- [x] Banned status of a user is error in portal community page
- [x] The dashboard on moderator view, the review button for reports lead to nothing
- [x] mod_queue.php icons are emojis for threads
- [x] mod_feed.php keep the meta data for each card stick below

#### Officer
- [x] Announcement preview in announcement page doesn't show the image banner
- [x] Requests in requests page cannot be reviewed (Same with admin)
- [x] Analytics does not show any of the data

#### Admin
- [x] Bug when activating or deactivating a service

### Features Backlog

- [ ] Reactions system to the announcements
- [ ] Portal Services page: make the request submission confirmation a modal instead of a toast notification
