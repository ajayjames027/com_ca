# HACKFEST '26 — BigRock Subdomain Deployment Guide
**Event:** HACKFEST '26 Intercollegiate Hackathon  
**Organizer:** Department of Commerce Computer Applications, St. Joseph's College (Autonomous), Tiruchirappalli  
**Technical Partner:** FrontierWox Tech Private Limited  
**Target URL:** `https://hackfest.frontierwox.in` (or your chosen BigRock domain/subdomain)

---

## 📋 Architecture Overview
The backend has been converted to native **PHP 7.4 / 8.x + MySQL + Apache (.htaccess)**, requiring **zero Node.js dependencies**. It runs seamlessly on standard BigRock Linux Shared Hosting with cPanel.

- **Frontend:** Pure HTML5, CSS3, modern JavaScript (Async/Await)
- **Backend API:** Native PHP (`api/register.php`, `api/login.php`, etc.)
- **Database:** MySQL 5.7+ / 8.0+ / MariaDB (managed via cPanel phpMyAdmin)
- **Email Notifications:** Pure PHP SSL SMTP socket to `smtp.gmail.com:465` (sends from `ajayjames027@gmail.com` using Gmail App Password)
- **Payment Verification:** Manual committee review of UTR reference & receipt screenshot from the official St. Joseph's College Seminar Portal (`CC21124631`)

---

## 🚀 Step 1: Create the Subdomain in BigRock cPanel

1. Log in to your **BigRock Account** &rarr; Open **cPanel**.
2. Scroll to the **Domains** section and click **Domains** or **Subdomains**.
3. Click **Create A New Domain** (or **Add Subdomain**).
4. Enter:
   - **Domain / Subdomain:** `hackfest.frontierwox.in`
   - **Document Root:** `public_html/hackfest` (or `public_html/hackfest.frontierwox.in`)
5. Click **Submit** / **Create**.

---

## 🗄️ Step 2: Create the MySQL Database & User

1. In cPanel, find the **Databases** section and click **MySQL Database Wizard**.
2. **Step 1: Create A Database:**
   - Enter a name, e.g., `hackfest` (cPanel will prepend your username, e.g., `frontie_hackfest`).
   - Click **Next Step**.
3. **Step 2: Create Database User:**
   - Username: e.g., `hackfest_user` (will become `frontie_hackfest_user`).
   - Password: Click **Password Generator** to create a strong password (copy it!).
   - Click **Create User**.
4. **Step 3: Add User to Database:**
   - Check the **ALL PRIVILEGES** box.
   - Click **Make Changes** &rarr; **Next Step**.

*Note down your Database Name, Username, and Password.*

---

## 📥 Step 3: Import the Database Schema

1. Go back to cPanel home &rarr; open **phpMyAdmin** (under Databases).
2. On the left sidebar, click your newly created database (e.g. `frontie_hackfest`).
3. Click the **Import** tab at the top.
4. Click **Choose File** and select `schema.sql` from this repository.
5. Scroll down and click **Import** (or **Go**).
6. You will see green success messages and the tables `teams` and `admins` created.

---

## ⚙️ Step 4: Configure `config.php`

Open `config.php` in your text editor (or edit directly in cPanel File Manager) and update the database credentials created in Step 2:

```php
// config.php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'freewmbl_frontie_hackfest');
define('DB_USER', 'freewmbl_frontie_hackfest'); // If your DB username is different in cPanel, update this
define('DB_PASS', 'Freewill@25');

// Admin Credentials
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123'); // Change to your preferred secure admin password

// Gmail SMTP Notification Credentials
define('EMAIL_HOST', 'smtp.gmail.com');
define('EMAIL_PORT', 465);
define('EMAIL_USER', 'ajayjames027@gmail.com');
define('EMAIL_PASS', 'ohksocfoozjdwqok'); // Gmail App Password
```

---

## 📤 Step 5: Upload Files to Subdomain Root

1. In cPanel, open **File Manager**.
2. Navigate to your subdomain directory: `public_html/hackfest/`.
3. Compress the project folder into a `.zip` on your computer (excluding `node_modules` and `.git`).
4. Click **Upload** in File Manager, upload your `.zip`, and then right-click &rarr; **Extract**.
5. Ensure the extracted files sit directly inside `public_html/hackfest/`:
   ```text
   public_html/hackfest/
   ├── api/
   │   ├── index.php
   │   ├── register.php
   │   ├── login.php
   │   ├── team_status.php
   │   ├── admin_teams.php
   │   ├── admin_approve.php
   │   ├── admin_reject.php
   │   ├── admin_delete.php
   │   └── export_csv.php
   ├── includes/
   │   └── smtp_mailer.php
   ├── uploads/
   │   └── .htaccess
   ├── .htaccess
   ├── config.php
   ├── schema.sql
   ├── index.html
   ├── register.html
   ├── login.html
   ├── dashboard.html
   ├── admin.html
   ├── style.css
   ├── script.js
   ├── college_logo.png
   ├── fwt.jpg
   └── hackathon rules.pdf
   ```
6. **Set Permissions for `uploads/` Folder:**
   - In cPanel File Manager, right-click `uploads/` &rarr; **Change Permissions**.
   - Ensure it is set to `755` (or `775`) so PHP can write uploaded payment receipts.

---

## 🔒 Step 6: Enable Free SSL (HTTPS)

1. In cPanel, search for **SSL/TLS Status** (or **Let's Encrypt SSL**).
2. Find `hackfest.frontierwox.in`.
3. Click **Run AutoSSL** (or **Issue**).
4. Wait 1-2 minutes until a green padlock appears for your subdomain.

---

## ✅ Step 7: Live Testing & Verification Checklist

1. **Homepage:**
   - Open `https://hackfest.frontierwox.in`
   - Verify official title: *Department of Commerce Computer Applications, St. Joseph's College (Autonomous), Tiruchirappalli*.
   - Verify 5-Step Registration Process Guide and coordinator contacts.
   - Verify bottom-right "Developed by FrontierWox" floating badge.
2. **Team Registration (`register.html`):**
   - Click "Register Your Team".
   - Click the college payment portal button: opens `https://service.sjctni.edu/Seminar/25/index.php?programcode=CC21124631`.
   - Fill in Team details (2 members strictly), enter UTR ID, attach receipt image, and submit.
   - Confirm success modal displaying issued Pass ID (e.g. `HF26-1001`).
3. **Team Dashboard (`login.html` &rarr; `dashboard.html`):**
   - Log in using Team Name and Password.
   - Verify status displays as `PENDING COMMITTEE VERIFICATION`.
4. **Admin Verification Console (`login.html` &rarr; `admin.html`):**
   - Log in with `admin` / `admin123`.
   - Click **View Receipt** to inspect the uploaded payment screenshot and UTR ID.
   - Click **Approve**.
   - Verify that:
     1. Status changes to `APPROVED`.
     2. Automated confirmation email is dispatched from `ajayjames027@gmail.com` to both participant emails.
5. **Gate Pass & WhatsApp Link:**
   - In the Team Dashboard (or email), verify:
     1. Official Campus Entry Gate Pass is displayed with QR code and participants' details.
     2. "Join Official WhatsApp Group" button links to `https://chat.whatsapp.com/CGQ2aJinJQNIbKR39taZc0`.
     3. Printable Gate Pass opens cleanly with `Print / Save Pass as PDF`.
6. **CSV Export:**
   - Click **Export to CSV** in admin console to download the full registration report.

---

## 🛠️ Troubleshooting & Support

- **"Internal Server Error 500"**:
  - Check cPanel **Errors** log.
  - Verify database credentials in `config.php`.
- **"Upload failed" on registration**:
  - In cPanel &rarr; **Select PHP Version** &rarr; **Options**, ensure `upload_max_filesize` is at least `10M` and `post_max_size` is at least `12M`.
  - Ensure `uploads/` directory permissions are `755`.
- **Emails not delivering**:
  - Gmail SMTP uses port 465 SSL. If BigRock firewall blocks outbound 465, submit a quick ticket to BigRock support to "Enable outbound port 465/587 for SMTP". The script automatically falls back to native `mail()` if the socket is blocked.
