<?php
session_start();
require_once '../database.php';

if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'Maintenance') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? '';
    $employee = $_POST['employee'] ?? '';
    $schedule = $_POST['schedule'] ?? '';

    if (empty($request_id) || empty($employee) || empty($schedule)) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    // Validate date format
    if (!strtotime($schedule)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        exit;
    }

    // Update the maintenance request with the schedule
    $stmt = $conn->prepare("UPDATE maintenance_requests SET schedule = ? WHERE id = ? AND emp_id = ?");
    $stmt->bind_param("sis", $schedule, $request_id, $employee);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
