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

    // Handle both single message and array of messages
    $messages = [];
    if (isset($input[0]) && is_array($input)) {
        $messages = $input;
    } else {
        $messages[] = $input;
    }

    $responses = [];
    // Process each message
    foreach ($messages as $message) {
        // Validate required fields
        $required_fields = ['uname', 'request', 'details', 'room', 'priority'];
        foreach ($required_fields as $field) {
            if (!isset($message[$field]) || $message[$field] === "") {
                http_response_code(400);
                echo json_encode(["error" => "Missing field: $field"]);
                exit;
            }
        }
        
        try {
            // Set default status if not provided
            $status = isset($message['status']) ? $message['status'] : 'Pending';
            
            // Insert new message
            $insertStmt = $pdo->prepare("INSERT INTO customer_messages (uname, request, details, room, status, created_at, priority) 
                                         VALUES (:uname, :request, :details, :room, :status, NOW(), :priority)");
            $insertStmt->execute([
                ':uname'    => $message['uname'],
                ':request'  => $message['request'],
                ':details'  => $message['details'],
                ':room'     => $message['room'],
                ':status'   => $status,
                ':priority' => $message['priority']
            ]);
            
            $responses[] = [
                "success" => true,
                "message" => "Customer message submitted successfully.",
                "id"      => $pdo->lastInsertId()
            ];
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

// Remove the GET handler and directly respond with method not allowed for any non-POST method
http_response_code(405);
echo json_encode(["error" => "Method not allowed."]);
?>
