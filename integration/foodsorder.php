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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read JSON input
    $json_input = file_get_contents("php://input");
    $input = json_decode($json_input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid JSON input"]);
        exit;
    }

    // Handle both single orders and arrays of orders
    $orders = [];
    if (isset($input[0]) && is_array($input)) {
        $orders = $input;
    } else {
        $orders[] = $input;
    }

    $responses = [];
    // Process each order
    foreach ($orders as $order) {
        // Validate required fields
        $required_fields = ['code', 'customer_name', 'food_item', 'quantity', 'totalprice'];
        foreach ($required_fields as $field) {
            if (!isset($order[$field]) || $order[$field] === "") {
                http_response_code(400);
                echo json_encode(["error" => "Missing field: $field"]);
                exit;
            }
        }
        
        try {
            // Check if the order code already exists
            $checkStmt = $pdo->prepare("SELECT id FROM foodorders WHERE code = :code");
            $checkStmt->execute([
                ':code' => $order['code']
            ]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Update existing order
                $updateStmt = $pdo->prepare("UPDATE foodorders SET 
                    customer_name = :customer_name, 
                    food_item = :food_item, 
                    quantity = :quantity, 
                    totalprice = :totalprice, 
                    status = :status 
                    WHERE id = :id");
                
                $status = isset($order['status']) ? $order['status'] : 'pending';
                
                $updateStmt->execute([
                    ':customer_name' => $order['customer_name'],
                    ':food_item'     => $order['food_item'],
                    ':quantity'      => $order['quantity'],
                    ':totalprice'    => $order['totalprice'],
                    ':status'        => $status,
                    ':id'            => $existing['id']
                ]);
                
                $responses[] = [
                    "success" => true,
                    "message" => "Order updated successfully.",
                    "id"      => $existing['id']
                ];
            } else {
                // Insert new order
                $status = isset($order['status']) ? $order['status'] : 'pending';
                
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
                
                $responses[] = [
                    "success" => true,
                    "message" => "Order created successfully.",
                    "id"      => $pdo->lastInsertId()
                ];
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Database Error", "message" => $e->getMessage()]);
            exit;
        }
    }
    
    http_response_code(201);
    echo json_encode($responses);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Optional filtering by status
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        
        if ($status) {
            $stmt = $pdo->prepare("SELECT * FROM foodorders WHERE status = :status ORDER BY created_at DESC");
            $stmt->execute([':status' => $status]);
        } else {
            $stmt = $pdo->query("SELECT * FROM foodorders ORDER BY created_at DESC");
        }
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database Error", "message" => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Method not allowed."]);
?>
