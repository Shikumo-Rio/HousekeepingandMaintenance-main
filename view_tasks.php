<?php
require_once 'database.php';

if (!isset($_GET['emp_id'])) {
    header('Location: housekeepers.php');
    exit;
}

$emp_id = $conn->real_escape_string($_GET['emp_id']);
$query = "SELECT * FROM assigntasks WHERE emp_id = '$emp_id' ORDER BY create_at DESC";
$result = $conn->query($query);

$tasks = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($tasks);
