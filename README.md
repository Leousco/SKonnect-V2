Last Update: 9/9/26  

System Users:  

Residents - Standard users, can view announcements, interact with community posts  
apply to offered services, view their request/s status, and receive in-system and  
email notifications about their services status, community posts, and events  
developments.  

SK Officer - Focuses on posting, editing, and managing announcements. Also manages  
services as well as service requests (approve, reject, respond). Handle events,  
view analytics, reports, and users.  

Moderator - Focuses on community control. They can remove or reply to threads.  
They handle reports such us spam, harassments, send warning to users, and  
as well as update the status of threads  

System Admin - They have full authority, control, and has access to everything.  
They can manage, create, delete, or configure users. They can change system configs,  
access system logs. They can do everything the system offers.  
  
  
Login credentials:

Email | Password

admin@skonnect.com     | passwords  
moderator@skonnect.com | passwords  
officer@skonnect.com   | passwords  
  
  
List of Implemented Major Features:  
  
Public view  
- View announcements  
- View available services (application requires login)  
- View community feed (interaction requires login)  
- Register an account  
- Login registered account (authentication)  
- Login admin accounts (authorization)  
  
Portal view  
- View upcoming events (calendar based)  
- View and bookmark an announcement  
- View, create, comment, reply, support, report, and bookmark a thread in community feed  
- View and apply for available services  
- View, edit, and resubmit, recent user applications  
- View, mark as read, and dismiss notifications  
- Setup basic profile for personal insights about the user's account  
  
Officer panel  
- Create and configure announcements  
- Create, configure, and activate/deactivate a service  
- Approve, reject, and require for revision a service application  
- Mark upcoming events on calendar  
- View system analytics (Officer module only)  
- View system users for basic insights (not included admin users)  
  
Moderator panel  
- View community feed  
- Set thread status  
- Comment/reply on threads  
- Hide threads, comments, and replies  
- Ban/sanction specific users  
- Handle reports (sanction, remove or dismiss)  
- View moderators activity logs  
  
Admin panel  
- Create and configure announcements  
- Manage services  
- Manage service applications  
- Manage threads  
- Manage community reports  
- Manage all system users (add, ban, remove, change role)  
- View system-wide analytics  
- View admin activity logs  
  
  
Refactoring for Database Migration - Incomplete ⏳ 
  
Completed:  
- Authentication/Authorization  
- Announcements Module  
- Community Feed Module  
- Services Module  
- Profile Page (resident)  
- Notifications Module (resident)  
- Dashboard (resident, officer, moderator, admin)  
  
Current Task:  
- Admin panel ⏳  
  - Threads  
  - Reports
  - User management
  - Analytics & logs
  
  
Known Issues:  
  
General  
- Design inconsistencies for buttons, dropdowns, etc. ✔️  
- Topbars on each user views are not sticky  
- After logging in, clicking the 'back' button of a browser returns the user to  
  the login page. (fixed maybe) ✔️  
- Empty fields like threads, reports, etc. shows either two message saying  
  "no record yet" or misaligned (not centered) text.  
- Inconsistent toast design across all user views.  
- Make the notifications on admin users (officer, moderator, admin) a modal.  
- Make a 404 page.   
  
Public side  
 - Login page  
   - No loading visualization when clicking the "Login" button ✔️  
   - Forgot Password non functional  
 - Registration  
   - No password restrictions  
 - Services  
   - Bug with modal appearing and the navbar
  
Portal side  
 - Dashboard  
   - Event in calendar shows "Tomorrow" even though the event is still 2 days  
     from now  
   - Include a services section for easy viewing
   - Include latest community discussions (trending threads)
 - Notifications  
   - Color and icon of "action require" service requests. ✔️  
   - Viewing a notification doesn't auto "mark as read" it.  
 - Profile  
   - User info improvements  
 - Services  
   - Clicking outside the modal closes the modal resulting in loss of progress ✔️  
   - Make the "Submit Request" button unclickable if all fields are not complete yet  
   - Maybe add a confirmation modal or something before submitting an application  
 - Notif Badge  
   - Counter appears without any new notifications when opening "view" pages  
     (thread_view.php, announcement_view.php)  
  
Officer side  
 - Events Page  
   - Clicking outside the add event modal closes the modal resulting in  
     loss of progress ✔️  
   - Past events doesn't auto delete (to be evaluated)  
   - New events appear at the bottom of the list  
 - Notification badge non functional  
 - Services  
   - No confirmation modal when deactivating or activating a service  
  
Moderator side  
 - Dashboard  
   - Styles for containers  
 - Community Feed  
   - Commenting or replying auto updates the status of the thread to 'responded'  
    but it is not shown immediately because the page needs to refresh first.  
 - Notifications non functional  
  
Admin side  
 - Mostly non functional, styles are inconsistent with other related modules  
   on different users  
 - Dashboard
   - Update the sections
   - Add sections for other modules (threads, reports, recent announcements)
  
 
