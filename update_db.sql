-- SQL script to update the database for the new RWH system payload
-- Run this script on your MySQL server for the 'rwh_db' database.

USE rwh_db;

-- 1. Rename/Alter the 'volt_turbidity' column to 'ntu' and set its type to FLOAT
ALTER TABLE data_utama CHANGE COLUMN volt_turbidity ntu FLOAT NOT NULL DEFAULT 0.0;

-- 2. Add 'id_node' column if it doesn't exist to match the python payload
ALTER TABLE data_utama ADD COLUMN id_node VARCHAR(50) DEFAULT 'NODE_RWH_TELKOM' AFTER id;

-- 3. Add 'status_uv' column if it doesn't exist
ALTER TABLE data_utama ADD COLUMN status_uv VARCHAR(20) DEFAULT 'ALL-OFF' AFTER status_hazard;
