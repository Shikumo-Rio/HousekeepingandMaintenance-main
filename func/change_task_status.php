<?php
include '../database.php';

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate JSON input
if (!isset($data['id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$task_id = $data['id'];
$new_status = $data['status'];

try {
    // Update the task status in the customer_messages table
    $stmt = $conn->prepare("UPDATE customer_messages SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $task_id);
    $stmt->execute();

    // SQL for updating assigntasks with conditional use of NOW()
    if ($new_status === 'complete') {
        $updateAssignTaskSql = "UPDATE assigntasks SET status = ?, completed_at = NOW() WHERE task_id = ?";
        $stmt2 = $conn->prepare($updateAssignTaskSql);
        $stmt2->bind_param("si", $new_status, $task_id);
    } else {
        $updateAssignTaskSql = "UPDATE assigntasks SET status = ?, completed_at = NULL WHERE task_id = ?";
        $stmt2 = $conn->prepare($updateAssignTaskSql);
        $stmt2->bind_param("si", $new_status, $task_id);
    }

    $stmt2->execute();

    // Check if both updates were successful
    if ($stmt->affected_rows > 0 || $stmt2->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No rows were updated']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
