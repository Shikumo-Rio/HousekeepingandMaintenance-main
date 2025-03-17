<?php
header("Content-Type: application/json");
require '../database.php';
require '../configs/config.php';

// Get API Key from URL
$api_key = $_GET['api_key'] ?? '';

// Validate API Key from config file
if ($api_key !== API_KEY) {
    echo json_encode(["error" => "Invalid API Key"]);
    http_response_code(401); // Unauthorized
    exit;
}

// Fetch data from MySQL table
try {
    $stmt = $pdo->query("SELECT * FROM checkout_notices");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($data);
} catch (PDOException $e) {
    echo json_encode(["error" => "Database Error", "message" => $e->getMessage()]);
}


echo json_encode($data);
