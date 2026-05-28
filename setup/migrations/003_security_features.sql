-- Titanium Lunch — segurança, auditoria e importações
USE titanium_lunch;

ALTER TABLE admin_users
  ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE lunch_records
  ADD COLUMN marked_source VARCHAR(20) NULL DEFAULT NULL AFTER had_lunch;

CREATE TABLE IF NOT EXISTS import_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NULL,
  source_type VARCHAR(20) NOT NULL,
  summary VARCHAR(500) NOT NULL,
  details_json JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
);

UPDATE admin_users SET must_change_password = 0 WHERE username != 'admin';
