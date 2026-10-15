# HACKFEST '26 — Deployment Guide
## Hosting as a Subdomain of `frontierwox.in` (e.g. `hackfest.frontierwox.in`)

This application is built with **Node.js, Express, and SQL (SQLite / MySQL ready)**.

---

## 1. Database Architecture & Recommendation

You asked: *"what abt db.. use the convenient, need to host in as sub domain of frontierwox.in. , i think sql will be better."*

### Recommendation: SQLite (Built-in Standard SQL)
- **Why it is the most convenient:**
  - SQLite **is a complete SQL database engine** (`hackfest.db`). It executes standard ANSI SQL (`SELECT`, `INSERT`, `UPDATE`, `JOIN`).
  - **Zero configuration:** No need to install MySQL server, create database users, grant permissions, or manage connection strings.
  - **Single-file backups:** To back up all team data and registrations, simply copy the `hackfest.db` file.
  - **High Performance:** Capable of handling thousands of requests per second with sub-millisecond local disk queries.
  - **Zero Maintenance:** It is already embedded, self-migrating, and active in `server.js`.

### Alternative: MySQL / MariaDB (via `schema.sql`)
- If your hosting server at `frontierwox.in` runs cPanel with phpMyAdmin or a dedicated MySQL database:
  - We have created [`schema.sql`](schema.sql) in this directory.
  - Simply open **phpMyAdmin**, create a database (e.g. `frontier_hackfest`), and import [`schema.sql`](schema.sql).

---

## 2. Setting Up the Subdomain DNS

In your domain registrar / DNS provider for `frontierwox.in` (e.g. Cloudflare, GoDaddy, Hostinger, Namecheap):
1. Add a **New DNS Record**:
   - **Type:** `A`
   - **Name / Host:** `hackfest` (which points to `hackfest.frontierwox.in`)
   - **Value / Target IP:** `YOUR_SERVER_PUBLIC_IP`
   - **TTL:** Auto or 300 seconds

---

## 3. Deployment Method A: Ubuntu / Debian VPS (Recommended)

### Step 1: Install Node.js & Git on Server
```bash
sudo apt update && sudo apt upgrade -y
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs nginx
npm install -g pm2
```

### Step 2: Upload Files to Server
Upload the project folder to `/var/www/hackfest`:
```bash
sudo mkdir -p /var/www/hackfest
# Copy project files here using SCP, SFTP, or Git
cd /var/www/hackfest
npm install --production
```

### Step 3: Run the Application with PM2 (Background Daemon)
```bash
pm2 start server.js --name "hackfest-2026"
pm2 save
pm2 startup
```

### Step 4: Configure Nginx as Reverse Proxy
Create `/etc/nginx/sites-available/hackfest.frontierwox.in`:
```nginx
server {
    server_name hackfest.frontierwox.in;

    client_max_body_size 20M;

    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Enable the site and reload Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/hackfest.frontierwox.in /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Step 5: Install Free SSL Certificate (HTTPS)
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d hackfest.frontierwox.in
```
Certbot will automatically configure HTTPS and auto-renew the SSL certificate!

---

## 4. Deployment Method B: cPanel (Node.js Selector)

If `frontierwox.in` is hosted on a cPanel server:
1. Log in to **cPanel**.
2. Go to **Domains** -> **Subdomains** -> Create `hackfest.frontierwox.in`.
3. Go to **Software** -> **Setup Node.js App**:
   - **Node.js Version:** 18.x or 20.x
   - **Application Mode:** Production
   - **Application Root:** `hackfest` (or path where files are uploaded)
   - **Application URL:** `hackfest.frontierwox.in`
   - **Application Startup File:** `server.js`
4. Upload all files into the application directory.
5. In the Node.js App interface, click **Run NPM Install**.
6. Click **Restart**.
7. In cPanel **SSL/TLS Status**, run **AutoSSL** for `hackfest.frontierwox.in`.

---

## 5. Environment Variables & Admin Security

You can configure custom port or credentials by setting environment variables in a `.env` file or server environment:

| Variable | Default Value | Description |
|---|---|---|
| `PORT` | `3000` | Server listening port |
| `ADMIN_USER` | `admin` | Admin panel login username |
| `ADMIN_PASS` | `admin123` | Admin panel login password |

---

## 6. Contacts & Organizing Details Configured in Site

- **Host Institution:** Department of Computer Science & Department of Commerce Computer Applications (B.Com CA), St. Joseph's College (Autonomous), Tiruchirappalli
- **Industry Partner:** FrontierWox Tech Private Limited (`frontierwox.in`)
- **Staff Co-ordinator:** Dr. B. Fatimamary (📞 96294 82064)
- **Student Co-ordinators:**
  - Mohamed Ismail K S (📞 63818 98996)
  - K Abinesh (📞 96269 83425)
