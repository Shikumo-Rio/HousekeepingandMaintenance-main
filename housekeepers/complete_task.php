<?php
require_once '../database.php';

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['user_type'] !== 'Employee') {
    header("Location: ../unauthorized.php");
    exit;
}

// Initialize response array
$response = [
    'success' => false,
    'message' => 'Invalid request'
];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['task_id'])) {
    $task_id = $_POST['task_id'];
    $completion_notes = isset($_POST['completion_notes']) ? htmlspecialchars(trim($_POST['completion_notes'])) : '';
    $emp_id = $_SESSION['emp_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // 1. First, update task status to 'completed' (removed completion_notes field)
        $updateTask = $conn->prepare("UPDATE assigntasks SET status = 'completed', completed_at = NOW() WHERE task_id = ? AND emp_id = ?");
        $updateTask->bind_param("ii", $task_id, $emp_id);
        
        if (!$updateTask->execute()) {
            throw new Exception("Failed to update task status");
        }
        
        // 2. Process used inventory items
        if (isset($_POST['used_quantity']) && is_array($_POST['used_quantity'])) {
            foreach ($_POST['used_quantity'] as $item_id => $quantity) {
                $quantity = intval($quantity);
                
                if ($quantity > 0) {
                    // First, check current quantity
                    $checkStock = $conn->prepare("SELECT quantity, item_name FROM inventory WHERE id = ?");
                    $checkStock->bind_param("i", $item_id);
                    $checkStock->execute();
                    $result = $checkStock->get_result();
                    
                    if ($row = $result->fetch_assoc()) {
                        $currentStock = $row['quantity'];
                        $itemName = $row['item_name'];
                        
                        if ($currentStock < $quantity) {
                            throw new Exception("Not enough stock for item: {$itemName}");
                        }
                        
                        // Update inventory quantity
                        $newQuantity = $currentStock - $quantity;
                        $updateInventory = $conn->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
                        $updateInventory->bind_param("ii", $newQuantity, $item_id);
                        
                        if (!$updateInventory->execute()) {
                            throw new Exception("Failed to update inventory for item: {$itemName}");
                        }
                        
                        // Log usage
                        $insertUsage = $conn->prepare("INSERT INTO inventory_usage (task_id, item_id, quantity, used_by, used_at, notes) VALUES (?, ?, ?, ?, NOW(), ?)");
                        $insertUsage->bind_param("iiiss", $task_id, $item_id, $quantity, $_SESSION['username'], $completion_notes);
                        
                        if (!$insertUsage->execute()) {
                            throw new Exception("Failed to log inventory usage for item: {$itemName}");
                        }
                    } else {
                        throw new Exception("Item not found: $item_id");
                    }
                }
            }
        }
        
        // If everything went well, commit the transaction
        $conn->commit();
        
        $response['success'] = true;
        $response['message'] = 'Task completed successfully';
        
    } catch (Exception $e) {
        // Roll back the transaction if something failed
        $conn->rollback();
        $response['message'] = $e->getMessage();
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>
