<?php
require_once '../database.php';
require_once '../PHP_AItask/allocate_tasks.php';
require_once 'user_logs.php';

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);
$taskId = $data['id'] ?? '';
$newStatus = $data['status'] ?? '';

if (empty($taskId) || empty($newStatus)) {
    echo json_encode(['success' => false, 'error' => 'Missing required data']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // First, get the current status
    $currentStatusQuery = "SELECT status FROM customer_messages WHERE id = ?";
    $stmt = $conn->prepare($currentStatusQuery);
    $stmt->bind_param("i", $taskId);
    $stmt->execute();
    $result = $stmt->get_result();
    $oldStatus = "unknown";

    if ($row = $result->fetch_assoc()) {
        $oldStatus = $row['status'];
    }
    $stmt->close();

    // Update customer_messages status
    $stmt = $conn->prepare("UPDATE customer_messages SET status = ? WHERE id = ?");
    $stmt->bind_param('ss', $newStatus, $taskId);
    $stmt->execute();

    // Log the status change
    logTaskStatusChange($conn, $taskId, $oldStatus, $newStatus);

    // Update assigntasks status and employee status
    $stmt = $conn->prepare("UPDATE assigntasks SET status = ? WHERE task_id = ?");
    $stmt->bind_param('ss', $newStatus, $taskId);
    $stmt->execute();

    // If task is completed, update employee status
    if ($newStatus === 'complete') {
        $stmt = $conn->prepare("
            UPDATE employee e
            INNER JOIN assigntasks a ON e.emp_id = a.emp_id
            SET e.status = 'active'
            WHERE a.task_id = ? AND e.status = 'busy'
        ");
        $stmt->bind_param('s', $taskId);
        $stmt->execute();
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
