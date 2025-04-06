<?php
require_once '../database.php';

// Debug info
error_log("Notification request received from: " . ($_SESSION['username'] ?? 'unknown user'));

if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'Employee') {
    error_log("Unauthorized notification request");
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized',
        'session_status' => session_status(),
        'user_info' => isset($_SESSION['username']) ? $_SESSION['username'] : 'not set'
    ]);
    exit;
}

$emp_id = $_SESSION['emp_id'] ?? 0;
error_log("Getting notifications for employee ID: $emp_id");

// Debug query info
$sql = "SELECT id, message, link, created_at, is_read 
        FROM notifications 
        WHERE emp_id = ? AND notif_type = 'assigntask'
        ORDER BY created_at DESC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $emp_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $notifications = [];
    $unread_count = 0;

    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
        if (!$row['is_read']) {
            $unread_count++;
        }
    }

    error_log("Found " . count($notifications) . " notifications, $unread_count unread");
    
    // Return response with debug info
    echo json_encode([
        'success' => true, 
        'notifications' => $notifications, 
        'unread_count' => $unread_count,
        'emp_id' => $emp_id,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    error_log("Error in get_notifications.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage(),
        'emp_id' => $emp_id
    ]);
}
exit;
?>
