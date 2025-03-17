<?php
require_once '../database.php';
require_once '../PHP_AItask/allocate_tasks.php';

// Check for pending tasks and allocate if needed
$pendingCheck = $conn->query("SELECT COUNT(*) as pending FROM customer_messages WHERE status = 'pending'")->fetch_assoc();
if ($pendingCheck['pending'] > 0) {
    allocateTasks($conn);
}

echo json_encode(['success' => true]);
