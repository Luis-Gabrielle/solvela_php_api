<?php
// config.php - Database configuration
$host = 'localhost';
$dbname = 'u591433413_solvela';
$username = 'u591433413_solvela'; // Default XAMPP username
$password = 'u3lkVFTjlGX'; // Default XAMPP password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>