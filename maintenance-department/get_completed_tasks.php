<?php
session_start();
require_once '../database.php';

if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'Maintenance') {
    http_response_code(403);
    exit('Unauthorized');
}

if (!isset($_GET['emp_id'])) {
    http_response_code(400);
    exit('Employee ID is required');
}

$emp_id = $_GET['emp_id'];
$query = "SELECT * FROM completed_maintenance WHERE emp_id = ? ORDER BY completed_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $emp_id);
$stmt->execute();
$result = $stmt->get_result();

$tasks = [];
while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}

header('Content-Type: application/json');
echo json_encode($tasks);
