-- Evita cadastro duplicado pelo mesmo nome (ignorando maiúsculas)
USE titanium_lunch;

ALTER TABLE employees
  ADD COLUMN name_key VARCHAR(150) NULL AFTER name;

UPDATE employees SET name_key = UPPER(TRIM(name)) WHERE name_key IS NULL;

ALTER TABLE employees
  MODIFY name_key VARCHAR(150) NOT NULL,
  ADD UNIQUE KEY uk_employees_name_key (name_key);
