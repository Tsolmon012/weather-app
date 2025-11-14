CREATE DATABASE IF NOT EXISTS weather_app
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE weather_app;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  last_name      VARCHAR(100) NOT NULL,
  first_name     VARCHAR(100) NOT NULL,
  email          VARCHAR(150) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
