<?php
header('Content-Type: application/json');
include('../database.php');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['task_id']) || !isset($data['status'])) {
        throw new Exception('Missing required fields');
    }

    $taskId = $data['task_id'];
    $status = $data['status'];

    $stmt = $conn->prepare("UPDATE customer_messages SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $taskId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to update task status');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
