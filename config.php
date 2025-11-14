<?php
// config.php - MySQL холболт
$DB_HOST = 'localhost';
$DB_NAME = 'weather_app';
$DB_USER = 'root';   // XAMPP default
$DB_PASS = '';       // XAMPP default (хоосон)

// PDO options
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        $options
    );
} catch (PDOException $e) {
    exit("DB connection error: " . $e->getMessage());
}
