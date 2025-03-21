<?php
require_once 'database.php';

// Set the content type to JSON
header('Content-Type: application/json');

try {
    // Fetch inventory items from local database
    $sql = "SELECT * FROM inventory ORDER BY id ASC";
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }
    
    $inventory = array();
    
    while ($row = $result->fetch_assoc()) {
        $inventory[] = array(
            'id' => $row['id'],
            'category' => $row['category'] ?? 'Uncategorized',
            'item_name' => $row['item_name'],
            'sku' => $row['sku'] ?? '',
            'quantity' => $row['quantity'] ?? 0
        );
    }
    
    // Output the inventory as JSON
    echo json_encode($inventory);
    
} catch (Exception $e) {
    // Return error as JSON
    http_response_code(500);
    echo json_encode(array(
        'error' => true,
        'message' => $e->getMessage()
    ));
}
?>
