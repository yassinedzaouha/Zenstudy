<?php
// db.php - Enhanced database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "zenstudy";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }
    
    // Set charset to UTF-8 for Arabic support
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Connection error: " . $e->getMessage());
}
?>
