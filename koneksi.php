<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "donut_shop";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     // Auto-create users table if it doesn't exist
     $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
         `id` INT AUTO_INCREMENT PRIMARY KEY,
         `username` VARCHAR(150) NOT NULL UNIQUE,
         `password` VARCHAR(255) NOT NULL,
         `role` VARCHAR(50) DEFAULT 'customer',
         `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>