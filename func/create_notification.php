<?php
// Include database connection
require_once '../connection.php';

// Function to sanitize input
function sanitize($conn, $data) {
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// Set headers for JSON response
header('Content-Type: application/json');

// Check if request is POST and contains JSON data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON data from request body
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    // Check if JSON was successfully decoded
    if ($data === null) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
        exit;
    }
    
    // Validate required fields
    $required_fields = ['emp_id', 'message', 'link', 'item_name', 'notif_type', 'task_id'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
            exit;
        }
    }
    
    // Sanitize input data
    $emp_id = sanitize($conn, $data['emp_id']);
    $message = sanitize($conn, $data['message']);
    $link = sanitize($conn, $data['link']);
    $item_name = sanitize($conn, $data['item_name']);
    $notif_type = sanitize($conn, $data['notif_type']);
    $task_id = sanitize($conn, $data['task_id']);
    $is_read = isset($data['is_read']) ? (int)$data['is_read'] : 0;
    
    // Current timestamp for created_at
    $created_at = date('Y-m-d H:i:s');
    
    // Insert notification into database
    $query = "INSERT INTO notifications (emp_id, message, link, created_at, item_name, notif_type, is_read, task_id) 
              VALUES ('$emp_id', '$message', '$link', '$created_at', '$item_name', '$notif_type', '$is_read', '$task_id')";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Notification created successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method. POST required.']);
}
?>
