# HACKFEST '26 — Deployment Guide
**Subdomain Target:** `hackfest.frontierwox.in`  
**Organizer:** Department of Commerce Computer Applications, St. Joseph's College (Autonomous), Tiruchirappalli  
**Technical Partner:** FrontierWox Tech Private Limited  

---

## 🎯 Deployment Options

| Deployment Target | Recommended Architecture | Guide |
| :--- | :--- | :--- |
| **BigRock Linux Shared Hosting (cPanel)** | **Native PHP 7.4 / 8.x + MySQL + Apache** | 👉 **[Read Full BigRock Guide (BIGROCK_DEPLOYMENT.md)](BIGROCK_DEPLOYMENT.md)** |
| **Ubuntu / Debian VPS** | Node.js (PM2) + SQLite / MySQL + Nginx | See Section below |

---

## 🌟 Option 1: BigRock cPanel Deployment (Recommended for Shared Hosting)

Because standard BigRock shared hosting does not natively support long-running Node.js background daemons, the entire backend is available as native **PHP & MySQL**:

1. **Create Subdomain:** In cPanel &rarr; Subdomains &rarr; create `hackfest.frontierwox.in` (Docroot: `public_html/hackfest`).
2. **Create MySQL DB:** In cPanel &rarr; MySQL Database Wizard &rarr; create DB & user, grant ALL PRIVILEGES.
3. **Import SQL:** In phpMyAdmin &rarr; select DB &rarr; Import [`schema.sql`](schema.sql).
4. **Configure:** Update DB name, user, and password in [`config.php`](config.php).
5. **Upload Files:** Upload code to `public_html/hackfest` and set `uploads/` folder permission to `755`.
6. **Enable SSL:** In cPanel &rarr; SSL/TLS Status &rarr; Run AutoSSL.

For detailed screenshots and walkthrough, consult **[`BIGROCK_DEPLOYMENT.md`](BIGROCK_DEPLOYMENT.md)**.

---

## 🖥️ Option 2: VPS Deployment (Node.js & PM2)

If you are hosting on an independent Ubuntu/Debian Cloud VPS (AWS, DigitalOcean, Hetzner, Linode):

### 1. Install Node.js & Git
```bash
sudo apt update && sudo apt upgrade -y
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs nginx git
npm install -g pm2
```

### 2. Upload / Clone Files
```bash
sudo mkdir -p /var/www/hackfest
git clone https://github.com/ajayjames027/com_ca.git /var/www/hackfest
cd /var/www/hackfest
npm install --production
```

### 3. Start Backend with PM2
```bash
pm2 start server.js --name "hackfest-2026"
pm2 save
pm2 startup
```

### 4. Configure Nginx Reverse Proxy
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

Enable site & reload:
```bash
sudo ln -s /etc/nginx/sites-available/hackfest.frontierwox.in /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 5. Free SSL (Let's Encrypt)
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d hackfest.frontierwox.in
```
