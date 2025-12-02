📚 Attendance Monitoring System — Setup & User Guide

A complete guide for setting up the device, database, and website.

🚀 Quick Start

Before anything else, please follow these steps in order:

Set up WiFi on the Device

Set up the Database

Access the Website

Learn How to Use the Device

🛠 Required Software

Make sure these are installed before starting:

Arduino IDE

XAMPP

📦 Device Usage Guide
🔌 Step 1 — Power the Device

Plug the device into any power outlet using a USB-C connector.

🌐 Step 2 — Open the Website

Log in using the SUPERADMIN account.

👩‍🏫 Step 3 — Add Teacher/Admin Accounts

Add test or dummy accounts to simulate attendance.

Check the email for generated login credentials.

Logout afterwards.

🖐 Step 4 — Register Fingerprint (Teacher)

On the device:

Select ENROLL → TEACHER.

Enter your Enrollment Code.

Scan your fingerprint twice.

💻 Step 5 — Access Teacher Dashboard

Log in as the teacher.

Go to Manage Sections.

Upload the file under the Class List folder.

Click Upload.

👦 Step 6 — Register Fingerprint (Student)

On the device:

Select ENROLL → STUDENT.

Enter the Student ID.

Scan fingerprint twice.

🕒 Step 7 — Add Schedule

Log in as the teacher.

Go to Manage Schedule.

Fill in details.

For testing: set Start Time to 1 minute from the current time.

📲 Step 8 — Simulate Attendance

On the device:

Select SCAN.

Follow LED instructions.

⚠️ Important Reminders

✔️ Fingerprint scanner must be plugged into your PC/laptop.

✔️ Do NOT modify any code except what the manual specifies.

✔️ Arduino IDE must be installed before uploading code.

📡 Setting Up WiFi on the Device

Locate Project Folder
Go to:
attendance_system/FINGERPRINT SCANNER

Open Arduino File
Inside the NEW FOLDER, open NEW.ino.

Change WiFi Credentials

Go to Line 18 and Line 19.

Replace the SSID and PASSWORD inside the quotes " " with your WiFi details.

Upload to Device

Hold down the BOOT button on the device.

Click Upload in Arduino IDE.

Release once the console starts showing output.

🗄️ Setting Up the Database (XAMPP)

Open XAMPP → Start Apache & MySQL

Click Admin under MySQL

In the browser:

Click +New then Import

Choose the file attendance_system.sql inside the DATABASE folder

🌍 Accessing the Website (Localhost)

Hosting is already prepared. You only need local access.

Install XAMPP

Copy the entire folder:
attendance_system → XAMPP/htdocs/

Start Apache and MySQL in XAMPP

Open your browser and go to:

http://localhost/attendance_system/login.php

🔑 Superadmin Login

Use this to create teachers and admins:

Username: superadmin
Password: superadmin
