<?php
// Ensure clean output
ob_end_clean();

// Set strict headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Disable error reporting
error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once('../database.php');
    
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection failed");
    }

    $query = "SELECT * FROM customer_messages ORDER BY created_at DESC";
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception($conn->error);
    }
    
    $tasks = array();
    while ($row = $result->fetch_assoc()) {
        // Clean and sanitize each value
        array_walk_recursive($row, function(&$item) {
            if ($item !== null) {
                $item = trim(mb_convert_encoding($item, 'UTF-8', 'UTF-8'));
            }
        });
        $tasks[] = $row;
    }
    
    echo json_encode($tasks, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// Ensure no additional output
exit();
