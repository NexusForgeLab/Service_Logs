PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT NOT NULL UNIQUE,
  pass_hash TEXT NOT NULL,
  display_name TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS category_counters (
  category TEXT PRIMARY KEY,
  last_number INTEGER NOT NULL DEFAULT 0
);

INSERT OR IGNORE INTO category_counters(category, last_number) VALUES
('MECH', 0), ('ELEC', 0), ('APP', 0);

CREATE TABLE IF NOT EXISTS services (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  category TEXT NOT NULL,
  service_no TEXT NOT NULL UNIQUE,
  provider_id INTEGER NOT NULL,
  provider_name TEXT NOT NULL,
  name TEXT NOT NULL,
  date_from TEXT NOT NULL,
  date_to TEXT NOT NULL,
  time_from TEXT NOT NULL,
  time_to TEXT NOT NULL,
  company_name TEXT NOT NULL,
  company_place TEXT NOT NULL,
  company_contact TEXT NOT NULL,
  /* NEW FIELDS */
  contact_person TEXT NOT NULL DEFAULT '',
  issue_nature TEXT NOT NULL DEFAULT 'Observation',
  issue_fixed TEXT NOT NULL DEFAULT 'No',
  
  issue_found TEXT NOT NULL,
  solution TEXT NOT NULL,
  expenses REAL NOT NULL DEFAULT 0,
  cost REAL NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE RESTRICT
);

/* NEW TABLE FOR IMAGES */
CREATE TABLE IF NOT EXISTS service_images (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  service_id INTEGER NOT NULL,
  file_path TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_services_cat_time ON services(category, created_at);
CREATE INDEX IF NOT EXISTS idx_services_company ON services(company_name);
CREATE INDEX IF NOT EXISTS idx_services_provider ON services(provider_name);
CREATE INDEX IF NOT EXISTS idx_services_dates ON services(date_from, date_to);