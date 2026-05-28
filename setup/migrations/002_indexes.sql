-- Índices para relatórios e totais do dia (idempotente)
USE titanium_lunch;

CREATE INDEX idx_lunch_records_date ON lunch_records (lunch_date);
CREATE INDEX idx_lunch_records_employee_date ON lunch_records (employee_id, lunch_date);
CREATE INDEX idx_employees_active ON employees (active);
CREATE INDEX idx_employees_department ON employees (department_id);
