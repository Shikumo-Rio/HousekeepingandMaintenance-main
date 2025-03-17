<?php
require_once '../database.php';
require_once '../PHP_AItask/allocate_tasks.php';

// Check and allocate pending tasks
$pendingCheck = $conn->query("SELECT COUNT(*) as pending FROM customer_messages WHERE status = 'pending'")->fetch_assoc();
if ($pendingCheck['pending'] > 0) {
    allocateTasks($conn);
}

// Fetch tasks for each status
$statuses = [
    'pending' => "SELECT * FROM customer_messages WHERE status = 'pending' LIMIT 5",
    'working' => "SELECT * FROM customer_messages WHERE status = 'working' LIMIT 5",
    'complete' => "SELECT * FROM customer_messages WHERE status = 'complete' ORDER BY created_at DESC LIMIT 5",
    'invalid' => "SELECT * FROM customer_messages WHERE status = 'invalid' ORDER BY created_at DESC LIMIT 5"
];

foreach ($statuses as $status => $query) {
    echo "<div class='col-md-3 status-column' data-status='$status'>";
    echo "<h4 class='status-title p-2 text-light d-inline-flex'>" . ucfirst($status) . "</h4>";
    echo "<div class='status-grid'>";
    
    $result = $conn->query($query);
    if ($result->num_rows > 0) {
        while ($task = $result->fetch_assoc()) {
            echo "<div class='status-card card mb-0' data-task-id='{$task['id']}' onclick='showDetails({$task['id']})'>";
            echo "<p>ID: {$task['id']}</p>";
            echo "<p>Name: " . htmlspecialchars($task['uname']) . "</p>";
            echo "<p>Type: " . htmlspecialchars($task['request']) . "</p>";
            echo "<p>Date: {$task['created_at']}</p>";
            echo "</div>";
        }
    } else {
        echo "<p>No {$status} tasks.</p>";
    }
    echo "</div></div>";
}
?>
