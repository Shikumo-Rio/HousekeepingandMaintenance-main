<?php
require_once 'database.php';

if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Add error logging
error_log("Fetching inventory usage data");

// Updated query to join with assigntasks table using the correct column names
$sql = "SELECT u.id, u.task_id, u.item_id, i.item_name, u.quantity, 
               u.used_by, a.emp_name, u.used_at, u.notes
        FROM inventory_usage u
        LEFT JOIN inventory i ON u.item_id = i.id
        LEFT JOIN assigntasks a ON u.task_id = a.task_id
        ORDER BY u.used_at DESC";

try {
    // Log the query for debugging
    error_log("Executing query: " . $sql);
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        // Use emp_name from assigntasks if available, otherwise use used_by field
        if (!empty($row['emp_name'])) {
            $row['used_by'] = $row['emp_name'];
        }
        
        // Remove extra fields to keep response clean
        unset($row['emp_name']);
        
        $data[] = $row;
    }
    
    // Log the number of records retrieved
    error_log("Retrieved " . count($data) . " usage records");
    
    header('Content-Type: application/json');
    echo json_encode($data);
} catch (Exception $e) {
    error_log("Error in fetch_inventory_usage.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?>
