<?php
include '../database.php';
require_once 'user_logs.php'; // Add user logs functionality

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate JSON input
if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

// Check if required keys exist
$requiredKeys = ['task_id','emp_id','emp_name', 'uname', 'room', 'request', 'details'];
foreach ($requiredKeys as $key) {
    if (!isset($data[$key])) {
        echo json_encode(['success' => false, 'error' => "Missing required field: $key"]);
        exit;
    }
}

// Assign variables
$task_id = $data['task_id'];
$emp_id = $data['emp_id'];
$emp_name = $data['emp_name'];
$uname = $data['uname'];
$room = $data['room'];
$request = $data['request'];
$details = $data['details'];

// Database operation
try {
    $stmt = $conn->prepare("INSERT INTO assigntasks (emp_id, emp_name, task_id, uname, room, request, details, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'working')");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("isissss", $emp_id, $emp_name, $task_id, $uname, $room, $request, $details);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    // Update customer_messages status
    $updateStmt = $conn->prepare("UPDATE customer_messages SET status = 'working' WHERE id = ?");
    if (!$updateStmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $updateStmt->bind_param("i", $task_id);
    if (!$updateStmt->execute()) {
        throw new Exception("Execute failed: " . $updateStmt->error);
    }
    
    // Log the task assignment
    logTaskAssignment($conn, $task_id, $emp_id, $emp_name);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
