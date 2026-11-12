CREATE DATABASE IF NOT EXISTS bantay_dagat_marinduque
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE bantay_dagat_marinduque;

CREATE TABLE IF NOT EXISTS admins (
  admin_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  profile_image VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
  user_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  username VARCHAR(60) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  contact_number VARCHAR(30) NULL,
  account_status ENUM('Active','Inactive','Suspended') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reports (
  report_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  reference_number VARCHAR(30) NULL UNIQUE,
  reference_sent_at DATETIME NULL,
  reference_sent_by BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  reporter_name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NULL,
  contact_number VARCHAR(30) NULL,
  report_title VARCHAR(200) NOT NULL,
  category ENUM('Illegal Fishing','Marine Pollution','Improper Waste Disposal','Coastal Damage','Destroyed Coral Reefs','Stranded Marine Animals','Injured Marine Animals','Oil Spills','Other Coastal Issues') NOT NULL,
  description TEXT NOT NULL,
  barangay VARCHAR(120) NULL,
  location VARCHAR(255) NOT NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  incident_date DATE NULL,
  incident_time TIME NULL,
  evidence_image VARCHAR(255) NULL,
  status ENUM('Pending','Under Review','In Progress','Resolved','Rejected') NOT NULL DEFAULT 'Pending',
  admin_remarks TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY fk_reports_user (user_id),
  KEY idx_reports_status (status),
  KEY idx_reports_category (category),
  KEY idx_reports_created_at (created_at),
  KEY idx_reports_barangay (barangay),
  KEY idx_reports_coordinates (latitude, longitude),
  KEY fk_reports_reference_admin (reference_sent_by),
  CONSTRAINT fk_reports_reference_admin FOREIGN KEY (reference_sent_by) REFERENCES admins(admin_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_reports_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS report_history (
  history_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  report_id BIGINT UNSIGNED NOT NULL,
  admin_id BIGINT UNSIGNED NULL,
  previous_status ENUM('Pending','Under Review','In Progress','Resolved','Rejected') NULL,
  new_status ENUM('Pending','Under Review','In Progress','Resolved','Rejected') NOT NULL,
  remarks TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY fk_history_admin (admin_id),
  KEY idx_history_report_date (report_id, created_at),
  CONSTRAINT fk_history_admin FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_history_report FOREIGN KEY (report_id) REFERENCES reports(report_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_inbox_messages (
  inbox_message_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  recipient_user_id BIGINT UNSIGNED NOT NULL,
  report_id BIGINT UNSIGNED NOT NULL,
  reference_number VARCHAR(30) NOT NULL,
  subject VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  admin_id BIGINT UNSIGNED NULL,
  read_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user_inbox_recipient_unread (recipient_user_id, read_at, created_at),
  KEY idx_user_inbox_report (report_id, created_at),
  CONSTRAINT fk_user_inbox_recipient FOREIGN KEY (recipient_user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_user_inbox_report FOREIGN KEY (report_id) REFERENCES reports(report_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_user_inbox_admin FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS announcements (
  announcement_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  admin_id BIGINT UNSIGNED NULL,
  title VARCHAR(200) NOT NULL,
  category ENUM('Bantay Dagat Activities','Coastal Clean-Up Drives','Marine Conservation Programs','Environmental Announcements','Community Activities','Coastal Warnings','Emergency Advisories') NOT NULL,
  description LONGTEXT NOT NULL,
  featured_image VARCHAR(255) NULL,
  publication_status ENUM('Draft','Published','Unpublished') NOT NULL DEFAULT 'Draft',
  published_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY fk_announcements_admin (admin_id),
  KEY idx_announcement_status_date (publication_status, published_at),
  CONSTRAINT fk_announcements_admin FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS marine_species (
  species_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  common_name VARCHAR(150) NOT NULL,
  scientific_name VARCHAR(180) NULL,
  category VARCHAR(60) NULL,
  description TEXT NOT NULL,
  habitat TEXT NULL,
  diet TEXT NULL,
  occurrence_note TEXT NULL,
  conservation_status VARCHAR(100) NULL,
  interesting_facts TEXT NULL,
  species_image VARCHAR(255) NULL,
  image_source_url TEXT NULL,
  reference_url TEXT NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_species_scientific_name (scientific_name),
  KEY idx_species_common_name (common_name),
  KEY idx_species_conservation (conservation_status),
  KEY idx_species_category (category)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cleanup_activities (
  activity_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  activity_title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  location VARCHAR(255) NULL,
  activity_date DATETIME NULL,
  activity_image VARCHAR(255) NULL,
  status VARCHAR(50) NULL DEFAULT 'Upcoming',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS messages (
  message_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL,
  subject VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('Unread','Read','Archived') NOT NULL DEFAULT 'Unread',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_messages_status_date (status, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admin_activity_logs (
  log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  admin_id BIGINT UNSIGNED NULL,
  action VARCHAR(100) NOT NULL,
  description TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY fk_activity_admin (admin_id),
  KEY idx_admin_logs_date (created_at),
  CONSTRAINT fk_activity_admin FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Local-only starter account: admin@bantaydagat.local / BantayDagat!2026
-- Change this password in Admin Profile immediately after first login.
INSERT IGNORE INTO admins (full_name, email, password)
VALUES ('Bantay Dagat Administrator', 'admin@bantaydagat.local',
        '$2y$10$y2W3382Oa4N69RSz95SX8.iRRca1NwyEutGS5v1nl0Kc/dsIgqXOq');

INSERT INTO marine_species
  (common_name, scientific_name, category, description, habitat, diet, conservation_status, interesting_facts)
SELECT 'Green Sea Turtle', 'Chelonia mydas', 'Marine Reptile', 'A large marine turtle that depends on healthy seagrass beds and clean nesting beaches.', 'Tropical coastal waters, seagrass meadows, and nesting beaches.', 'Seagrass and algae as adults.', 'Endangered', 'Females return to beaches in the region where they hatched to nest.'
WHERE NOT EXISTS (SELECT 1 FROM marine_species WHERE common_name = 'Green Sea Turtle');

INSERT INTO marine_species
  (common_name, scientific_name, category, description, habitat, diet, conservation_status, interesting_facts)
SELECT 'Whale Shark', 'Rhincodon typus', 'Fish', 'The world''s largest fish is a gentle filter feeder that travels through Philippine waters.', 'Warm offshore and coastal waters.', 'Plankton, fish eggs, and small schooling fish.', 'Endangered', 'Each whale shark has a unique pattern of spots, like a fingerprint.'
WHERE NOT EXISTS (SELECT 1 FROM marine_species WHERE common_name = 'Whale Shark');

INSERT INTO marine_species
  (common_name, scientific_name, category, description, habitat, diet, conservation_status, interesting_facts)
SELECT 'Hawksbill Turtle', 'Eretmochelys imbricata', 'Marine Reptile', 'A reef-associated turtle whose health reflects the condition of coral ecosystems.', 'Coral reefs, lagoons, and rocky coastal habitats.', 'Sponges and other small reef animals.', 'Critically Endangered', 'Its narrow beak helps it reach food in reef crevices.'
WHERE NOT EXISTS (SELECT 1 FROM marine_species WHERE common_name = 'Hawksbill Turtle');

INSERT INTO announcements (title, category, description, publication_status, published_at)
SELECT 'Welcome to Bantay-Dagat Marinduque', 'Environmental Announcements',
       'This community platform helps residents share coastal concerns, learn about marine life, and support a cleaner, safer sea. Check back for local announcements and activities.',
       'Published', CURRENT_TIMESTAMP
WHERE NOT EXISTS (
  SELECT 1 FROM announcements
  WHERE title = 'Welcome to Bantay-Dagat Marinduque' AND admin_id IS NULL
);

CREATE OR REPLACE VIEW vw_dashboard_statistics AS
SELECT COUNT(*) AS total_reports,
       COALESCE(SUM(status = 'Pending'), 0) AS pending_reports,
       COALESCE(SUM(status = 'Under Review'), 0) AS under_review_reports,
       COALESCE(SUM(status = 'In Progress'), 0) AS in_progress_reports,
       COALESCE(SUM(status = 'Resolved'), 0) AS resolved_reports,
       COALESCE(SUM(status = 'Rejected'), 0) AS rejected_reports
FROM reports;

CREATE OR REPLACE VIEW vw_latest_announcements AS
SELECT announcement_id, title, category, description, featured_image, published_at
FROM announcements
WHERE publication_status = 'Published';

CREATE OR REPLACE VIEW vw_public_map_reports AS
SELECT report_id, reference_number, report_title, category, barangay, location,
       latitude, longitude, status, created_at
FROM reports
WHERE latitude IS NOT NULL AND longitude IS NOT NULL;
