<?php
require_once '../database.php';
require_once 'user_logs.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../roomservice.php');
    exit;
}

// Get form data
$guestName = $_POST['uname'] ?? '';
$room = $_POST['room'] ?? '';
$details = $_POST['details'] ?? '';

// Determine request type (custom or predefined)
if (isset($_POST['custom_request']) && !empty($_POST['custom_request'])) {
    $request = $_POST['custom_request'];
} else {
    $request = $_POST['request'] ?? '';
}

if (empty($guestName) || empty($room) || empty($request)) {
    header('Location: ../roomservice.php?status=error&message=' . urlencode('Missing required fields'));
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Insert new service request
    $stmt = $conn->prepare("INSERT INTO customer_messages (uname, room, request, details, status, created_at) 
                           VALUES (?, ?, ?, ?, 'pending', NOW())");
    $stmt->bind_param('ssss', $guestName, $room, $request, $details);
    $stmt->execute();
    
    // Get the ID of the newly inserted record
    $taskId = $conn->insert_id;
    
    // Log the new service request
    logNewServiceRequest($conn, $taskId, $request, $room);
    
    $conn->commit();
    
    // Redirect with success message
    $message = "Service request for $request in room $room";
    header('Location: ../roomservice.php?status=success&message=' . urlencode($message));
    exit;
} catch (Exception $e) {
    $conn->rollback();
    
    // Redirect with error message
    header('Location: ../roomservice.php?status=error&message=' . urlencode('Error creating request: ' . $e->getMessage()));
    exit;
}

$conn->close();
?>
