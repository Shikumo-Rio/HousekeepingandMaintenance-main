<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
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

    // Determine if it's an array of requests or a single request
    $requests = [];
    if (isset($input[0]) && is_array($input)) {
        $requests = $input;
    } else {
        $requests[] = $input;
    }

    $responses = [];
    // Process each request
    foreach ($requests as $request) {
        // Validate required fields
        $required_fields = ['request_id', 'role', 'quantity', 'reason', 'preferred_shift', 'urgency_level', 'requested_by'];
        foreach ($required_fields as $field) {
            if (!isset($request[$field]) || $request[$field] === "") {
                http_response_code(400);
                echo json_encode(["error" => "Missing field: $field"]);
                exit;
            }
        }
        
        try {
            // Check if request with the same ID already exists
            $checkStmt = $pdo->prepare("SELECT id FROM employee_requests WHERE request_id = :request_id");
            $checkStmt->execute([
                ':request_id' => $request['request_id']
            ]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Update existing request
                $updateStmt = $pdo->prepare("UPDATE employee_requests SET 
                    role = :role, 
                    quantity = :quantity, 
                    reason = :reason, 
                    preferred_shift = :preferred_shift, 
                    urgency_level = :urgency_level, 
                    status = :status, 
                    requested_by = :requested_by,
                    request_date = NOW() 
                    WHERE id = :id");
                    
                $updateStmt->execute([
                    ':role' => $request['role'],
                    ':quantity' => $request['quantity'],
                    ':reason' => $request['reason'],
                    ':preferred_shift' => $request['preferred_shift'],
                    ':urgency_level' => $request['urgency_level'],
                    ':status' => $request['status'] ?? 'Pending',
                    ':requested_by' => $request['requested_by'],
                    ':id' => $existing['id']
                ]);
                
                $responses[] = [
                    "success" => true,
                    "message" => "Request updated successfully.",
                    "id" => $existing['id']
                ];
            } else {
                // Insert new request
                $insertStmt = $pdo->prepare("INSERT INTO employee_requests 
                    (request_id, role, quantity, reason, preferred_shift, urgency_level, status, requested_by, request_date) 
                    VALUES 
                    (:request_id, :role, :quantity, :reason, :preferred_shift, :urgency_level, :status, :requested_by, NOW())");
                    
                $insertStmt->execute([
                    ':request_id' => $request['request_id'],
                    ':role' => $request['role'],
                    ':quantity' => $request['quantity'],
                    ':reason' => $request['reason'],
                    ':preferred_shift' => $request['preferred_shift'],
                    ':urgency_level' => $request['urgency_level'],
                    ':status' => $request['status'] ?? 'Pending',
                    ':requested_by' => $request['requested_by']
                ]);
                
                $responses[] = [
                    "success" => true,
                    "message" => "Request created successfully.",
                    "id" => $pdo->lastInsertId()
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

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Read JSON input
    $json_input = file_get_contents("php://input");
    $input = json_decode($json_input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid JSON input"]);
        exit;
    }

    // Get request_id from URL parameter
    $request_id = $_GET['request_id'] ?? null;
    
    if (!$request_id) {
        http_response_code(400);
        echo json_encode(["error" => "Missing request_id parameter"]);
        exit;
    }

    // Validate required fields
    $required_fields = ['role', 'quantity', 'reason', 'preferred_shift', 'urgency_level', 'requested_by'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || $input[$field] === "") {
            http_response_code(400);
            echo json_encode(["error" => "Missing field: $field"]);
            exit;
        }
    }
    
    try {
        // Check if request with the ID exists
        $checkStmt = $pdo->prepare("SELECT id FROM employee_requests WHERE request_id = :request_id");
        $checkStmt->execute([
            ':request_id' => $request_id
        ]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existing) {
            http_response_code(404);
            echo json_encode(["error" => "Request not found"]);
            exit;
        }
        
        // Update existing request
        $updateStmt = $pdo->prepare("UPDATE employee_requests SET 
            role = :role, 
            quantity = :quantity, 
            reason = :reason, 
            preferred_shift = :preferred_shift, 
            urgency_level = :urgency_level, 
            status = :status, 
            requested_by = :requested_by,
            request_date = NOW() 
            WHERE id = :id");
            
        $updateStmt->execute([
            ':role' => $input['role'],
            ':quantity' => $input['quantity'],
            ':reason' => $input['reason'],
            ':preferred_shift' => $input['preferred_shift'],
            ':urgency_level' => $input['urgency_level'],
            ':status' => $input['status'] ?? 'Pending',
            ':requested_by' => $input['requested_by'],
            ':id' => $existing['id']
        ]);
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Request updated successfully.",
            "id" => $existing['id']
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database Error", "message" => $e->getMessage()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query("SELECT * FROM employee_requests");
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
