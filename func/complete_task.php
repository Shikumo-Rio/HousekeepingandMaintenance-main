<?php
require_once '../database.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['task_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Task ID is required']);
    exit;
}

$taskId = intval($data['task_id']);

try {
    // Start transaction
    $conn->begin_transaction();

    // First get the task details including emp_id
    $query = "SELECT emp_id, emp_name FROM assigntasks WHERE task_id = ? AND status = 'Working'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $taskId);
    $stmt->execute();
    $result = $stmt->get_result();
    $task = $result->fetch_assoc();

    if (!$task) {
        throw new Exception('Task not found or already completed');
    }

    // Update task status in assigntasks
    $stmt = $conn->prepare("UPDATE assigntasks SET status = 'Completed' WHERE task_id = ?");
    $stmt->bind_param('i', $taskId);
    $stmt->execute();

    // Update status in customer_messages
    $stmt = $conn->prepare("UPDATE customer_messages SET status = 'complete' WHERE id = ?");
    $stmt->bind_param('i', $taskId);
    $stmt->execute();

    // Set employee status back to active
    $stmt = $conn->prepare("UPDATE employee SET status = 'active' WHERE emp_id = ? AND status = 'busy'");
    $stmt->bind_param('i', $task['emp_id']);
    $stmt->execute();

    // Log the completion
    $stmt = $conn->prepare("INSERT INTO task_logs (task_id, emp_id, action, change_details) VALUES (?, ?, 'completed', 'Task marked as complete')");
    $stmt->bind_param('ii', $taskId, $task['emp_id']);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    // Return success response matching Flask API
    echo json_encode([
        'message' => 'Task completed successfully',
        'task_id' => $taskId,
        'emp_name' => $task['emp_name'],
        'emp_id' => $task['emp_id']
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    $conn->close();
}
