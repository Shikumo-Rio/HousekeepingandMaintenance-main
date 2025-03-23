<?php
require_once '../database.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

// Handle AJAX request to clean a room
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get data from POST request
    $roomId = isset($_POST['room_id']) ? $_POST['room_id'] : '';
    $roomNumber = isset($_POST['room_number']) ? $_POST['room_number'] : '';
    $notes = isset($_POST['room_notes']) ? $_POST['room_notes'] : ''; // Changed from 'notes' to 'room_notes'
    
    // Validate input
    if (empty($roomId) || empty($roomNumber)) {
        echo json_encode(['success' => false, 'message' => 'Missing room information']);
        exit;
    }
    
    // Handle file upload if provided
    $imagePath = '';
    if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] == 0) { // Changed from 'image' to 'room_image'
        $uploadDir = '../uploads/room_images/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Generate unique filename
        $fileName = time() . '_' . basename($_FILES['room_image']['name']);
        $targetFilePath = $uploadDir . $fileName;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['room_image']['tmp_name'], $targetFilePath)) {
            $imagePath = $targetFilePath;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
            exit;
        }
    }

    // 1. First, log the cleaning activity in our database
    try {
        $stmt = $conn->prepare("INSERT INTO cleaning_logs (room_id, room_number, cleaned_by, cleaned_at, notes, image_path) VALUES (?, ?, ?, NOW(), ?, ?)");
        $stmt->bind_param("sssss", $roomId, $roomNumber, $_SESSION['username'], $notes, $imagePath);
        
        if ($stmt->execute()) {
            $cleaningLogId = $conn->insert_id;
            
            // 2. Now make the API call to update the room status using the new endpoint
            $apiUrl = "https://core2.paradisehoteltomasmorato.com/integ/cleanrm.php";
            
            // Prepare data for the new API structure
            $apiData = [
                'api_key' => 'b5fb3418cb1a7e88903d64e55373c48e48f9c53aabdcba0357f0107233d9dbda',
                'room_id' => $roomId,
                'room_no' => $roomNumber,
                'status' => 'Available',
                'clean_status' => 'Clean' // Set the clean_status to Clean
            ];
            
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                // Log API error but don't necessarily fail the request
                error_log("API Error on room update: " . curl_error($ch));
            }
            
            curl_close($ch);
            
            // Always return success since we've logged the cleaning
            echo json_encode([
                'success' => true, 
                'message' => 'Room ' . $roomNumber . ' has been marked as clean',
                'cleaning_log_id' => $cleaningLogId
            ]);
            
        } else {
            throw new Exception("Failed to insert cleaning log");
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
