# Service Logs - Field Service Management System

**Service Logs** is a lightweight, web-based application designed for technicians and engineers to track field service reports. It categorizes services (Mechanical, Electrical, Application), generates PDF reports, and manages service history with a clean, searchable interface.

Built with **PHP** and **SQLite**, it is Docker-ready and easy to deploy on any server or local machine.

![Service Logs Logo](assets/icon-512.png)

## 🌟 Features

* **Categorized Logs:** Separate auto-incrementing series for distinct departments:
* **MECH** (Mechanical) e.g., `MECH-000001`
* **ELEC** (Electrical) e.g., `ELEC-000001`
* **APP** (Application) e.g., `APP-000001`


* **Detailed Reporting:** Record dates, times, company details, contact persons, issues found, solutions provided, and expense tracking.
* **Smart Tools:** Company name autocomplete (learns from history) and image attachments.
* **PDF Export:** Generate professional service report PDFs instantly.
* **User Management:** Admin panel to add users and reset passwords.
* **Search & Filter:** Easily find past service records by specific criteria.

---

## 📋 Prerequisites

* **Docker** and **Docker Compose** installed on your system.
* (Optional) A reverse proxy (like Nginx) if deploying to a public server.

---

## 🚀 Quick Start (Docker)

### 1. Project Setup

Ensure your project directory is structured as follows:

```text
/service-logs-app
  ├── app/
  ├── api/
  ├── assets/
  ├── data/           <-- Will hold the database
  ├── sql/
  ├── uploads/        <-- Will hold images
  ├── docker-compose.yml
  ├── Dockerfile
  ├── index.php
  ├── install.php
  └── ... (other files)

```

### 2. Build and Run

Open your terminal in the project root and run:

```bash
docker compose up -d --build

```

### 3. Permissions Setup (Critical)

The application needs to write to the `data` and `uploads` folders. Run the following commands to set the correct ownership for the web server user (`www-data`):

```bash
# Create folders if they don't exist
mkdir -p data uploads

# Set permissions inside the container
docker exec -it service_logs chown -R www-data:www-data /var/www/html/data
docker exec -it service_logs chown -R www-data:www-data /var/www/html/uploads
docker exec -it service_logs chmod -R 775 /var/www/html/data
docker exec -it service_logs chmod -R 775 /var/www/html/uploads

```

### 4. Database Installation

1. Open your web browser and go to: `http://localhost:8091/install.php`.
2. You should see a **"✅ Installed"** confirmation screen.
3. **Default Credentials:**
* **Username:** `admin`
* **Password:** `admin123`



### 5. Cleanup

For security, delete the installation file after setup:

```bash
docker exec -it service_logs rm /var/www/html/install.php

```

---

## ⚙️ Configuration

Configuration is handled via environment variables in `docker-compose.yml`.

| Variable | Default | Description |
| --- | --- | --- |
| `APP_URL` | `http://localhost:8091` | The base URL for the app. Update this if using a domain name. |
| `PHP_TZ` | `Asia/Kolkata` | Timezone for report timestamps. Change to your local zone (e.g., `America/New_York`). |
| `SQLITE_PATH` | `/var/www/html/data/app.db` | Internal path to the SQLite database file. |

To apply changes, edit the file and restart:

```bash
docker compose down && docker compose up -d

```

---

## 📖 Usage Guide

### Creating a New Service Entry

1. Click **"New Entry"** on the dashboard.
2. Select the **Category** (Mechanical, Electrical, or Application).
3. Fill in the client details. *Note: As you type the Company Name, suggestions from previous visits will appear.*
4. Upload any relevant site photos.
5. Click **Save**. The Service Number (e.g., `MECH-000045`) is generated automatically.

### Exporting Reports

1. Navigate to the **View** or **Search** page.
2. Click the **PDF** icon next to any service entry.
3. A printable PDF summary will be downloaded.

### User Management

1. Log in as `admin`.
2. Go to the **Users** tab.
3. Here you can register new technicians or reset passwords for existing accounts.

---

## 🛠 Troubleshooting

**Error: "General error: 14 unable to open database file"**

* This indicates the web server cannot write to the `data/` directory.
* **Solution:** Re-run the permission commands listed in Step 3 of the Quick Start.

**Images not uploading**

* Ensure the `uploads/` folder exists and has write permissions.
* Check that the file size is within limits (default PHP limit is usually 2MB, but can be adjusted in a custom `php.ini`).

**Autocomplete not working**

* The autocomplete relies on existing data. It will start working once you have saved a few service records with company names.

---

## 🔒 Security Recommendations

* **Change Default Password:** Immediately after logging in, go to the User settings and change the `admin` password.
* **Delete Install Script:** Ensure `install.php` is removed to prevent the database from being reset accidentally.
* **Backups:** Regularly back up the `./data/app.db` file and the `./uploads/` directory to save your records.
