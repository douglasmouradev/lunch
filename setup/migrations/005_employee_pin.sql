-- PIN individual de 4 dígitos para marcação no quiosque
USE titanium_lunch;

ALTER TABLE employees
  ADD COLUMN pin_hash VARCHAR(255) NULL DEFAULT NULL AFTER name_key;
