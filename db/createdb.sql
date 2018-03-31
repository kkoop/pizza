CREATE DATABASE IF NOT EXISTS pizza DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pizza;

-- Datenbankschema aus Dump
source pizza.sql

-- Benutzer anlegen
CREATE USER IF NOT EXISTS pizza IDENTIFIED BY '932MFjxdCiSjaLjE';
GRANT SELECT,INSERT,UPDATE,DELETE,EXECUTE ON pizza.* TO 'pizza';
FLUSH PRIVILEGES;
