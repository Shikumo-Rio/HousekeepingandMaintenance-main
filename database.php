<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set PHP default timezone
date_default_timezone_set('Asia/Manila');

// Database connection settings
$db_host = 'localhost';
$db_username = 'root';
$db_password = '';
$db_name = 'bot';

// MySQLi Connection
$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set timezone for MySQLi connection
$conn->query("SET time_zone = '+08:00'");

// PDO Connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set timezone for PDO connection
    $pdo->exec("SET time_zone = '+08:00'");
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Add priority column to customer_messages and assigntasks tables if it doesn't exist

// Function to delete a room cleaning and service from the database
function deleteRoomCleaningAndService($conn, $id) {
    $sql = "DELETE FROM customer_messages WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

function deleteLostAndFound($conn, $id) {
    $sql = "DELETE FROM lost_and_found WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

function addStockNotification($conn, $item_name, $emp_id, $status) {
    // Check if a notification already exists for this item's stock message
    $message = ($status == 'low') ? "Stock for $item_name is running low." : "Stock for $item_name is out of stock!";
    
    // Check for existing notification with the same message
    $checkQuery = "SELECT * FROM notifications WHERE message = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("s", $message);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // If no existing notification, add a new one
    if ($result->num_rows === 0) {
        $insertQuery = "INSERT INTO notifications (message, link, created_at, emp_id, item_name) 
                        VALUES (?, ?, NOW(), ?, ?)";
        $link = "inventory.php"; // Link to inventory page
        
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("ssis", $message, $link, $emp_id, $item_name);
        $stmt->execute();
    }
    $stmt->close();
}