<?php
require_once '../database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $remarks = $_POST['remarks'];
    $emp_id = $_SESSION['username'];
    
    // Handle file upload
    $target_dir = "../uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file = $_FILES['photo'];
    $imageFileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newFilename = 'proof_' . time() . '_' . $request_id . '.' . $imageFileType;
    $target_file = $target_dir . $newFilename;
    $db_file_path = 'uploads/' . $newFilename;  // Store relative path in database
    
    $response = ['success' => false, 'message' => ''];
    
    // Check if image file is valid
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png'])) {
        $response['message'] = "Only JPG, JPEG & PNG files are allowed.";
        echo json_encode($response);
        exit;
    }
    
    try {
        $conn->begin_transaction();
        
        // Insert into completed_maintenance
        $insert_query = "INSERT INTO completed_maintenance (maintenance_request_id, emp_id, completed_at, remarks, photo) VALUES (?, ?, NOW(), ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("isss", $request_id, $emp_id, $remarks, $db_file_path);
        $stmt->execute();
        
        // Update assigned_maintenance status
        $update_assigned = "UPDATE assigned_maintenance SET status = 'Completed' WHERE maintenance_request_id = ? AND emp_id = ?";
        $stmt = $conn->prepare($update_assigned);
        $stmt->bind_param("is", $request_id, $emp_id);
        $stmt->execute();
        
        // Update maintenance_requests status
        $update_request = "UPDATE maintenance_requests SET status = 'Completed' WHERE id = ?";
        $stmt = $conn->prepare($update_request);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $target_file)) {
            throw new Exception("Failed to upload file.");
        }
        
        $conn->commit();
        $response['success'] = true;
        $response['message'] = "Task marked as completed successfully!";
        
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = "Error: " . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}
?>
