# SKonnect

A community portal that connects residents with their SK officers: announcements, community discussions, events, and youth services in one place.

> **Last Update:** 9/29/26

## Table of Contents

- [System Users](#system-users)
- [Login Credentials](#login-credentials)
- [Features](#features)
  - [Feature to be Implemented](#feature-to-be-implemented)
  - [Implemented Major Features](#implemented-major-features)
- [Refactoring for Database Migration](#refactoring-for-database-migration)
- [Known Issues](#known-issues)
- [Features Backlog](#features-backlog)

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

- [ ] AI Chatbot

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

### General

- [x] Design inconsistencies for buttons, dropdowns, etc.
- [ ] Topbars on each user views are not sticky
- [x] After logging in, clicking the 'back' button of a browser returns the user to the login page. *(fixed maybe)*
- [ ] Empty fields like threads, reports, etc. show either two "no record yet" messages or misaligned (not centered) text.
- [ ] Inconsistent toast design across all user views.
- [ ] Make the notifications on admin users (officer, moderator, admin) a modal.
- [ ] Make a 404 page.

### PUBLIC SIDE

- Login page

- [x] No loading visualization when clicking the "Login" button
- [ ] Forgot Password non functional

- Registration
- [ ] No password restrictions

- Services
- [ ] Bug with modal appearing and the navbar

### PORTAL SIDE

- Dashboard
- [x] Event in calendar shows "Tomorrow" even though the event is still 2 days from now
- [x] Include a services section for easy viewing
- [x] Include latest community discussions (trending threads)

- Notifications
- [x] Color and icon of "action require" service requests
- [x] Viewing a notification doesn't auto "mark as read" it

- Profile
- [ ] User info improvements

- Services
- [x] Clicking outside the modal closes the modal resulting in loss of progress
- [x] Make the "Submit Request" button unclickable if all fields are not complete yet
- [x] On the request modal, make the info strip non-sticky to allow more space for viewing the form
- [x] Bug, multiple services entries appearing on one application

<!-- - Maybe add a confirmation modal or something before submitting an application -->

- Notif Badge
- [x] Counter appears without any new notifications when opening "view" pages (`thread_view.php`, `announcement_view.php`)

### OFFICER SIDE

- Events Page
- [x] Clicking outside the add event modal closes the modal resulting in loss of progress
- [ ] Past events don't auto delete *(to be evaluated)*
- [ ] New events appear at the bottom of the list

- Notification badge
- [ ] Notification badge non functional

- Services
- [ ] No confirmation modal when deactivating or activating a service

### MODERATOR SIDE

- Dashboard
- [ ] Styles for containers
- [ ] Fix the layouts of each section

- Community Feed
- [ ] Commenting or replying auto updates the status of the thread to 'responded' but it is not shown immediately because the page needs to refresh first.

- Notifications
- [ ] Notifications non functional

### ADMIN SIDE

- [x] Mostly non functional, styles are inconsistent with other related modules on different users

- Dashboard
- [ ] Update the sections
- [ ] Add sections for other modules (threads, reports, recent announcements)


### Features Backlog

- [ ] Reactions system to the announcement module
- [ ] Services page: make the request submission confirmation a modal instead of a toast notification
