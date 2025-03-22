<?php
// Include database connection
include_once('../database.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $uname = mysqli_real_escape_string($conn, $_POST['uname']);
    $room = mysqli_real_escape_string($conn, $_POST['room']);
    $request = mysqli_real_escape_string($conn, $_POST['request']); // This will be either predefined or custom
    $details = mysqli_real_escape_string($conn, $_POST['details']);
    
    // Set default status to 'pending'
    $status = 'pending';
    
    // Create timestamp
    $created_at = date('Y-m-d H:i:s');
    
    // Insert into customer_messages table
    $sql = "INSERT INTO customer_messages (uname, room, request, details, status, created_at) 
            VALUES ('$uname', '$room', '$request', '$details', '$status', '$created_at')";
    
    if (mysqli_query($conn, $sql)) {
        // Create a success message with request details
        $successMessage = "Room $room: $request";
        
        // Success - redirect back to roomservice.php with success status and message
        header('Location: ../roomservice.php?status=success&message=' . urlencode($successMessage));
        exit;
    } else {
        // Error
        header('Location: ../roomservice.php?status=error&message=Failed+to+add+request:+' . mysqli_error($conn));
        exit;
    }
} else {
    // Not a POST request
    header('Location: ../roomservice.php');
    exit;
}
?>
