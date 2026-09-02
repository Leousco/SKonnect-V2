Last Update: 8/29/26  

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
  

Features Checklist:  
  
MILESTONE #1  
- Basic Authentication   
- Basic Authorization   
- Frontend Pages   
- Announcements Module   
  
MILESTONE #2  
- Community Feed Module   
- Profile Page (resident)   
- Services Module   
- Dashboard (resident, officer, moderator)   
- In-System Notifications (resident)   
  
MILESTONE #3  
- Admin side improvements   
- User Management Module (system admin side)   
- Public view improvements   
  

Refactoring for Database Migration - Incomplete ⏳ 
  
Completed:  
- Authentication  
- Announcements Module  
- Community Feed Module  
- Services Module  
- Profile Page (resident)  
- Notifications Module (resident)  
- Dashboard (resident, officer, moderator, admin)  
  

Current Task:  
- Admin pages ⏳  
  

Known Issues:  
  
General  
- Design inconsistencies for buttons, dropdowns, etc. ✔️  
- Topbars on each user views are not sticky  
- After logging in, clicking the 'back' button of a browser returns the user to  
  the login page.  
- Empty fields like threads, reports, etc. shows either two message saying  
  "no record yet" or misaligned (not centered) text.  
- Inconsistent toast design across all user views.  
- Make the notifications on admin users (officer, moderator, admin) a modal.  
  
Public side  
 - Login page  
   - No loading visualization when clicking the "Login" button  
   - Forgot Password non functional  
 - Registration  
   - No password restrictions  
 - Services  
   - Bug with modal appearing and the navbar
  
Portal side  
 - Dashboard  
   - Event in calendar shows "Tomorrow" even though the event is still 2 days  
     from now  
 - Notifications  
   - Color and icon of "action require" service requests.  
   - Viewing a notification doesn't auto "mark as read" it.  
 - Profile  
   - User info improvements  
 - Services  
   - Clicking outside the modal closes the modal resulting in loss of progress  
   - Make the "Submit Request" button unclickable if all fields are not complete yet  
 - Notif Badge  
   - Counter appears without any new notifications when opening "view" pages  
     (thread_view.php, announcement_view.php)  
  
Officer side  
 - Events Page  
   - Clicking outside the add event modal closes the modal resulting in  
     loss of progress  
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
  
 
