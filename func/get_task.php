<?php
require_once '../database.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Task ID is required']);
    exit;
}

$taskId = intval($_GET['id']);
$query = "SELECT * FROM customer_messages WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $taskId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Task not found']);
    exit;
}

$task = $result->fetch_assoc();
echo json_encode($task);
