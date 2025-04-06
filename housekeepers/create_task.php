<?php
require_once '../database.php';

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['user_type'] !== 'Employee') {
    header("Location: ../unauthorized.php");
    exit;
}

$response = [
    'success' => false,
    'message' => 'Invalid request'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taskDetails = htmlspecialchars($_POST['task_details']);
    $assignedTo = htmlspecialchars($_POST['assigned_to']);
    $createdBy = $_SESSION['username'];

    // Save the task to the database
    $stmt = $conn->prepare("INSERT INTO assigntasks (request, details, emp_id, uname, create_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssis", $taskDetails, $taskDetails, $assignedTo, $createdBy);
    if ($stmt->execute()) {
        $taskId = $conn->insert_id;

        // Send task assignment notification via WebSocket
        $websocketUrl = "ws://localhost:8080/chat";
        $data = json_encode([
            'type' => 'task_assignment',
            'task_id' => $taskId,
            'task_details' => $taskDetails,
            'assigned_to' => $assignedTo
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $websocketUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_exec($ch);
        curl_close($ch);

        $response['success'] = true;
        $response['message'] = 'Task assigned and notification sent';
    } else {
        $response['message'] = 'Failed to assign task';
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>