<?php
include('../database.php'); // Include your database connection file

if (isset($_GET['id'])) {
    $taskId = intval($_GET['id']);
    $result = $conn->query("SELECT * FROM customer_messages WHERE id = $taskId");

    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(['error' => 'Task not found']);
    }
}
?>
