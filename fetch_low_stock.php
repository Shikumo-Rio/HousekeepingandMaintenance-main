<?php
require_once 'database.php';

header('Content-Type: application/json');

// Determine which items to fetch based on the 'type' parameter
$type = isset($_GET['type']) ? $_GET['type'] : 'low';

// Construct the SQL query based on the type
if ($type == 'out') {
    // Fetch out-of-stock items
    $sql = "SELECT * FROM inventory WHERE quantity = 0 ORDER BY category, item_name";
} else {
    // Fetch low stock items (default)
    $sql = "SELECT * FROM inventory WHERE quantity > 0 AND quantity < 10 ORDER BY category, item_name";
}

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['error' => 'Database query failed: ' . $conn->error]);
    exit;
}

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode($items);
?>
