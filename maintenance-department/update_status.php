<?php
session_start();
require_once '../database.php';

if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'Maintenance') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? '';
    $status = $_POST['status'] ?? '';
    $employee = $_POST['employee'] ?? '';

    if (!empty($request_id)) {
        $query = "UPDATE maintenance_requests SET status = ?, workon = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $status, $employee, $request_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
