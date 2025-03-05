<?php
// Set the timezone and session configurations
date_default_timezone_set('Asia/Manila');
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in and has admin privileges
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
if ($_SESSION['user_type'] !== 'Admin') {
    header("Location: unauthorized.php");
    exit;
}

// Include the database connection file
include 'database.php';

// Fetch admin emp_id for sending notifications
$adminQuery = "SELECT emp_id FROM login_accounts WHERE user_type = 'admin' LIMIT 1";
$adminResult = $conn->query($adminQuery);
$admin = $adminResult->fetch_assoc();
$emp_id = $admin['emp_id']; // Use this emp_id for notifications

// Query to fetch all notifications for employees and maintenance
$sql = "SELECT notifications.message, notifications.link, 
               DATE_FORMAT(notifications.created_at, '%Y-%m-%dT%H:%i:%s') as created_at 
        FROM notifications 
        JOIN login_accounts ON notifications.emp_id = login_accounts.emp_id 
        WHERE login_accounts.user_type IN ('employee', 'maintenance') 
        ORDER BY notifications.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    // Log to check the created_at value (optional for debugging)
    error_log('Created at: ' . $row['created_at']);
    $notifications[] = $row;
}

$stmt->close();

// Check stock levels and add notifications for low or zero stock
$stockCheckSQL = "SELECT id, item_name, available_stock FROM inventory WHERE available_stock < 10";
$stockResult = $conn->query($stockCheckSQL);

// Add stock notifications to the notifications array
while ($stockRow = $stockResult->fetch_assoc()) {
    if ($stockRow['available_stock'] == 0) {
        // Out of Stock notification
        array_unshift($notifications, [
            'message' => 'Out of Stock: ' . $stockRow['item_name'],
            'link' => 'inventory.php', // Link to inventory management page
            'created_at' => date('Y-m-d H:i:s') // Current time for this notification
        ]);
    } elseif ($stockRow['available_stock'] < 10) {
        // Low Stock notification
        array_unshift($notifications, [
            'message' => 'Low Stock: ' . $stockRow['item_name'] . ' (' . $stockRow['available_stock'] . ' left)',
            'link' => 'inventory.php', // Link to inventory management page
            'created_at' => date('Y-m-d H:i:s') // Current time for this notification
        ]);
    }
}

$conn->close();

// Return notifications as JSON
echo json_encode($notifications);
?>
