-- Titanium Lunch — só tabelas (banco já criado no aaPanel, ex.: sql_lunch)
-- phpMyAdmin: selecione o banco → Importar → este arquivo
-- SSH: mysql -h 127.0.0.1 -u sql_lunch -p sql_lunch < setup/install-tables-only.sql

CREATE TABLE IF NOT EXISTS departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS employees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  name_key VARCHAR(150) NOT NULL,
  pin_hash VARCHAR(255) NULL DEFAULT NULL,
  department_id INT NOT NULL,
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_employees_name_key (name_key),
  FOREIGN KEY (department_id) REFERENCES departments(id)
);

CREATE TABLE IF NOT EXISTS lunch_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  lunch_date DATE NOT NULL,
  had_lunch TINYINT(1) NOT NULL DEFAULT 0,
  marked_source VARCHAR(20) NULL DEFAULT NULL,
  marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_employee_date (employee_id, lunch_date),
  FOREIGN KEY (employee_id) REFERENCES employees(id)
);

CREATE TABLE IF NOT EXISTS admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  must_change_password TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS import_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NULL,
  source_type VARCHAR(20) NOT NULL,
  summary VARCHAR(500) NOT NULL,
  details_json JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
);

INSERT INTO departments (name) VALUES
  ('TI'),
  ('Comercial'),
  ('Financeiro'),
  ('RH'),
  ('Operações')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO admin_users (username, password_hash)
VALUES ('admin', '$2y$12$y2fqSFnJ75lR76eTpEJBneFXg4.3LjW/CHnOYC.XgdYFP0e961ct6')
ON DUPLICATE KEY UPDATE username = VALUES(username);
