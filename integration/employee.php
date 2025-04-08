<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
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

// Function to handle POST requests - Add new employee
function handlePostRequest($pdo) {
    // Read JSON input
    $json_input = file_get_contents("php://input");
    $input = json_decode($json_input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid JSON input"]);
        exit;
    }

    // Determine if it's an array of employees or a single employee
    $employees = [];
    if (isset($input[0]) && is_array($input)) {
        $employees = $input;
    } else {
        $employees[] = $input;
    }

    $responses = [];
    // Process each employee
    foreach ($employees as $employee) {
        // Validate required fields
        $required_fields = ['emp_id', 'name', 'role', 'email'];
        foreach ($required_fields as $field) {
            if (!isset($employee[$field]) || $employee[$field] === "") {
                http_response_code(400);
                echo json_encode(["error" => "Missing field: $field"]);
                exit;
            }
        }
        
        try {
            // Check if employee with the same ID already exists
            $checkStmt = $pdo->prepare("SELECT emp_id FROM employee WHERE emp_id = :emp_id");
            $checkStmt->execute([
                ':emp_id' => $employee['emp_id']
            ]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Update existing employee
                $updateStmt = $pdo->prepare("UPDATE employee SET 
                    name = :name, 
                    role = :role, 
                    email = :email 
                    WHERE emp_id = :emp_id");
                    
                $updateStmt->execute([
                    ':name' => $employee['name'],
                    ':role' => $employee['role'],
                    ':email' => $employee['email'],
                    ':emp_id' => $employee['emp_id']
                ]);
                
                $responses[] = [
                    "success" => true,
                    "message" => "Employee updated successfully.",
                    "emp_id" => $employee['emp_id']
                ];
            } else {
                // Insert new employee
                $insertStmt = $pdo->prepare("INSERT INTO employee 
                    (emp_id, name, role, email) 
                    VALUES 
                    (:emp_id, :name, :role, :email)");
                    
                $insertStmt->execute([
                    ':emp_id' => $employee['emp_id'],
                    ':name' => $employee['name'],
                    ':role' => $employee['role'],
                    ':email' => $employee['email']
                ]);
                
                $responses[] = [
                    "success" => true,
                    "message" => "Employee added successfully.",
                    "emp_id" => $employee['emp_id']
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
}

// Function to handle GET requests - Retrieve employees
function handleGetRequest($pdo) {
    try {
        // Check if a specific employee ID is provided
        if (isset($_GET['emp_id'])) {
            $stmt = $pdo->prepare("SELECT * FROM employee WHERE emp_id = :emp_id");
            $stmt->execute([':emp_id' => $_GET['emp_id']]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data) {
                http_response_code(404);
                echo json_encode(["error" => "Employee not found"]);
                return;
            }
        } else {
            $stmt = $pdo->query("SELECT * FROM employee");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo json_encode($data);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database Error", "message" => $e->getMessage()]);
    }
}

// Handle different HTTP methods
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Handle preflight requests
    http_response_code(200);
    exit;
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handlePostRequest($pdo);
    exit;
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    handleGetRequest($pdo);
    exit;
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}
?>
