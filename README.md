# Service Log App (PHP + SQLite) — Docker Ready

## Features
- Login
- Categories: Mechanical / Electrical / Application
- Each category has its own service number series:
  - MECH-000001, ELEC-000001, APP-000001 ...
- Entry form includes:
  - Service no (auto), Name, Date from/to, Time from/to,
    Company name/place/contact, Issue, Solution, Expenses, Cost
- Browse per category, search, view + print anytime
- ✅ Edit/Update existing service entry
- ✅ Export to PDF (simple built-in PDF generator)
- ✅ User Management (add users)
- ✅ Change password
- ✅ Company name autocomplete (from previous entries)

## Run on VPS
```bash
unzip service-log-app-v2.zip
cd service-log-app
docker compose up -d --build
```

Open:
- http://YOUR_SERVER_IP:8091

## Install DB (one time)
Open once:
- http://YOUR_SERVER_IP:8091/install.php

Default user:
- admin / admin123

Then delete install.php:
```bash
rm install.php
```

## SQLite file
Stored on host:
- ./data/app.db

If you get: unable to open database file
```bash
mkdir -p data
sudo chown -R 33:33 data
sudo chmod -R 775 data
docker compose restart
```

sudo mkdir -p /var/www/html/uploads

# Give ownership to the web server user
sudo chown -R www-data:www-data /var/www/html/uploads
sudo chown -R www-data:www-data /var/www/html/data

# Set write permissions (775 allows owner and group to write)
sudo chmod -R 775 /var/www/html/uploads
sudo chmod -R 775 /var/www/html/data

docker exec -it YOUR_CONTAINER_NAME chown -R www-data:www-data /var/www/html/
