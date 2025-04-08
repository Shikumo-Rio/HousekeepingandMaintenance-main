<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require '../database.php';
require '../configs/config.php';

// Get API Key from URL
$api_key = $_GET['api_key'] ?? '';
$valid_api_key = '9f08a7e6e4c8d6a1f41d8b17829c6a12e54c8d4f6a44e5d7b98276e3a3a5b9f5';

// Validate API Key
if ($api_key !== $valid_api_key) {
    http_response_code(403);
    echo json_encode(["error" => "Invalid API Key"]);
    exit;
}

// Define allowed status values
$allowedStatuses = ['Pending', 'In Process', 'Ready', 'Delivered', 'Canceled', 'Rejected'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle status update request
    if (isset($_GET['action']) && $_GET['action'] === 'update_status') {
        $orderCode = $_GET['code'] ?? '';
        $newStatus = $_GET['status'] ?? '';
        
        // Validate input
        if (empty($orderCode)) {
            http_response_code(400);
            echo json_encode(["error" => "Order code is required"]);
            exit;
        }
        
        if (!in_array($newStatus, $allowedStatuses)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid status value"]);
            exit;
        }
        
        try {
            // Update order status
            $updateStmt = $pdo->prepare("UPDATE foodorders SET status = :status WHERE code = :code");
            $updateStmt->execute([
                ':status' => $newStatus,
                ':code' => $orderCode
            ]);
            
            if ($updateStmt->rowCount() > 0) {
                // Get updated order details to return
                $stmt = $pdo->prepare("SELECT * FROM foodorders WHERE code = :code");
                $stmt->execute([':code' => $orderCode]);
                $updatedOrder = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    "success" => true,
                    "message" => "Order status updated successfully",
                    "order" => $updatedOrder
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    "error" => "Order not found or no changes made"
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Database Error", "message" => $e->getMessage()]);
        }
        exit;
    }

    // Handle order creation/update (original functionality)
    $json_input = file_get_contents("php://input");
    $input = json_decode($json_input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid JSON input"]);
        exit;
    }

    $orders = [];
    if (isset($input[0]) && is_array($input[0])) {
        $orders = $input;
    } else {
        $orders[] = $input;
    }

    $responses = [];
    $pdo->beginTransaction();
    
    try {
        foreach ($orders as $order) {
            $required_fields = ['code', 'customer_name', 'food_item', 'quantity', 'totalprice'];
            foreach ($required_fields as $field) {
                if (!isset($order[$field]) || $order[$field] === "") {
                    $pdo->rollBack();
                    http_response_code(400);
                    echo json_encode(["error" => "Missing field: $field"]);
                    exit;
                }
            }
            
            // Validate data types
            if (!is_numeric($order['quantity']) || $order['quantity'] <= 0) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(["error" => "Quantity must be a positive number"]);
                exit;
            }
            
            if (!is_numeric($order['totalprice']) || $order['totalprice'] <= 0) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(["error" => "Total price must be a positive number"]);
                exit;
            }
            
            // Check if order exists
            $checkStmt = $pdo->prepare("SELECT id FROM foodorders WHERE code = :code");
            $checkStmt->execute([':code' => $order['code']]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            $status = isset($order['status']) && in_array($order['status'], $allowedStatuses) 
                ? $order['status'] 
                : 'Pending';
            
            if ($existing) {
                // Update existing order
                $updateStmt = $pdo->prepare("UPDATE foodorders SET 
                    customer_name = :customer_name, 
                    food_item = :food_item, 
                    quantity = :quantity, 
                    totalprice = :totalprice, 
                    status = :status,
                    updated_at = NOW()
                    WHERE id = :id");
                
                $updateStmt->execute([
                    ':customer_name' => $order['customer_name'],
                    ':food_item'     => $order['food_item'],
                    ':quantity'      => $order['quantity'],
                    ':totalprice'    => $order['totalprice'],
                    ':status'        => $status,
                    ':id'            => $existing['id']
                ]);
                
                $orderId = $existing['id'];
            } else {
                // Insert new order
                $insertStmt = $pdo->prepare("INSERT INTO foodorders (
                    code, customer_name, food_item, quantity, totalprice, status, created_at
                ) VALUES (
                    :code, :customer_name, :food_item, :quantity, :totalprice, :status, NOW()
                )");
                
                $insertStmt->execute([
                    ':code'          => $order['code'],
                    ':customer_name' => $order['customer_name'],
                    ':food_item'     => $order['food_item'],
                    ':quantity'      => $order['quantity'],
                    ':totalprice'    => $order['totalprice'],
                    ':status'        => $status
                ]);
                
                $orderId = $pdo->lastInsertId();
            }
            
            // Get the full order details to return
            $stmt = $pdo->prepare("SELECT * FROM foodorders WHERE id = ?");
            $stmt->execute([$orderId]);
            $orderDetails = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $responses[] = $orderDetails;
        }
        
        $pdo->commit();
        http_response_code(201);
        echo json_encode(count($responses) === 1 ? $responses[0] : $responses);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Database Error", "message" => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Handle different actions
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'get_orders':
                $status = $_GET['status'] ?? null;
                $code = $_GET['code'] ?? null;
                
                if ($code) {
                    // Get single order by code
                    $stmt = $pdo->prepare("SELECT * FROM foodorders WHERE code = :code");
                    $stmt->execute([':code' => $code]);
                    $order = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($order) {
                        echo json_encode($order);
                    } else {
                        http_response_code(404);
                        echo json_encode(["error" => "Order not found"]);
                    }
                } elseif ($status) {
                    // Filter by status
                    if (!in_array($status, $allowedStatuses)) {
                        http_response_code(400);
                        echo json_encode(["error" => "Invalid status value"]);
                        exit;
                    }
                    
                    $stmt = $pdo->prepare("SELECT * FROM foodorders WHERE status = :status ORDER BY created_at DESC");
                    $stmt->execute([':status' => $status]);
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($data);
                } else {
                    // Get all orders
                    $stmt = $pdo->query("SELECT * FROM foodorders ORDER BY created_at DESC");
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($data);
                }
                break;
                
            case 'get_statuses':
                // Return list of allowed statuses
                echo json_encode($allowedStatuses);
                break;
                
            default:
                // Original GET functionality (backward compatibility)
                $status = $_GET['status'] ?? null;
                
                if ($status) {
                    $stmt = $pdo->prepare("SELECT * FROM foodorders WHERE status = :status ORDER BY created_at DESC");
                    $stmt->execute([':status' => $status]);
                } else {
                    $stmt = $pdo->query("SELECT * FROM foodorders ORDER BY created_at DESC");
                }
                
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($data);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database Error", "message" => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Method not allowed."]);
?>
