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

    // Kung array ng items, gamitin; kung solong item, i-wrap ito sa array.
    $items = [];
    if (isset($input[0]) && is_array($input)) {
        $items = $input;
    } else {
        $items[] = $input;
    }

    $responses = [];
    // I-loop ang bawat item
    foreach ($items as $item) {
        // Validate required fields
        $required_fields = ['inventory_id', 'category', 'item_name', 'sku', 'quantity'];
        foreach ($required_fields as $field) {
            if (!isset($item[$field]) || $item[$field] === "") {
                http_response_code(400);
                echo json_encode(["error" => "Missing field: $field"]);
                exit;
            }
        }
        
        try {
            // I-check kung may existing record na may parehong category, item_name, at sku
            $checkStmt = $pdo->prepare("SELECT id, quantity FROM inventory WHERE category = :category AND item_name = :item_name AND sku = :sku");
            $checkStmt->execute([
                ':category'   => $item['category'],
                ':item_name'  => $item['item_name'],
                ':sku'        => $item['sku']
            ]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Update: dagdagan ang quantity
                $newQuantity = $existing['quantity'] + $item['quantity'];
                $updateStmt = $pdo->prepare("UPDATE inventory SET quantity = :quantity WHERE id = :id");
                $updateStmt->execute([
                    ':quantity' => $newQuantity,
                    ':id'       => $existing['id']
                ]);
                $responses[] = [
                    "success" => true,
                    "message" => "Item updated successfully.",
                    "id"      => $existing['id'],
                    "new_quantity" => $newQuantity
                ];
            } else {
                // Insert bagong record
                $insertStmt = $pdo->prepare("INSERT INTO inventory (inventory_id, category, item_name, sku, quantity) VALUES (:inventory_id, :category, :item_name, :sku, :quantity)");
                $insertStmt->execute([
                    ':inventory_id' => $item['inventory_id'],
                    ':category'     => $item['category'],
                    ':item_name'    => $item['item_name'],
                    ':sku'          => $item['sku'],
                    ':quantity'     => $item['quantity']
                ]);
                $responses[] = [
                    "success" => true,
                    "message" => "Item inserted successfully.",
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
        $stmt = $pdo->query("SELECT id, inventory_id, category, item_name, sku, quantity FROM inventory");
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
