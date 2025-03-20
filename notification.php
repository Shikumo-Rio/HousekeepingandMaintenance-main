<?php
// Set the timezone and session configurations
date_default_timezone_set('Asia/Manila');
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type for JSON response
header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Include the database connection file
include 'database.php';

// Fetch admin emp_id for sending notifications
$adminQuery = "SELECT emp_id FROM login_accounts WHERE user_type = 'admin' LIMIT 1";
$adminResult = $conn->query($adminQuery);
$admin = $adminResult->fetch_assoc();
$emp_id = $admin['emp_id']; // Use this emp_id for notifications

// Array to hold all notifications
$notifications = [];

// Query to fetch all notifications for employees and maintenance
$sql = "SELECT notifications.message, notifications.link, 
               DATE_FORMAT(notifications.created_at, '%Y-%m-%dT%H:%i:%s') as created_at 
        FROM notifications 
        JOIN login_accounts ON notifications.emp_id = login_accounts.emp_id 
        WHERE login_accounts.user_type IN ('employee', 'admin') 
        ORDER BY notifications.created_at DESC 
        LIMIT 10";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
}

// Check stock levels in local inventory and add notifications for low or zero stock
$stockCheckSQL = "SELECT id, item_name, quantity FROM inventory WHERE quantity < 10";
$stockResult = $conn->query($stockCheckSQL);

if ($stockResult) {
    // Add stock notifications to the notifications array
    while ($stockRow = $stockResult->fetch_assoc()) {
        if ($stockRow['quantity'] == 0) {
            // Out of Stock notification
            array_unshift($notifications, [
                'message' => 'Out of Stock: ' . $stockRow['item_name'],
                'link' => 'inventory.php', // Link to inventory management page
                'created_at' => date('Y-m-d\TH:i:s') // Current time for this notification in ISO format
            ]);
        } elseif ($stockRow['quantity'] < 10) {
            // Low Stock notification
            array_unshift($notifications, [
                'message' => 'Low Stock: ' . $stockRow['item_name'] . ' (' . $stockRow['quantity'] . ' left)',
                'link' => 'inventory.php', // Link to inventory management page
                'created_at' => date('Y-m-d\TH:i:s') // Current time for this notification in ISO format
            ]);
        }
    }
}



$conn->close();

// Return notifications as JSON
echo json_encode($notifications);
?>
