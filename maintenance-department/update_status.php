<?php
session_start();
require_once '../database.php';

if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'Maintenance') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $status = $_POST['status'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update request status, primary emp_id, and reset needs_assistance
        $stmt = $conn->prepare("UPDATE maintenance_requests SET status = ?, emp_id = COALESCE(emp_id, ?), needs_assistance = 0 WHERE id = ?");
        $employees = json_decode($_POST['employees']);
        $primary_emp = !empty($employees) ? $employees[0] : null;
        $stmt->bind_param("ssi", $status, $primary_emp, $request_id);
        $stmt->execute();

        // Handle employee assignments if present
        if (isset($_POST['employees'])) {
            // Get existing assignments
            $stmt = $conn->prepare("SELECT emp_id FROM assigned_maintenance WHERE maintenance_request_id = ?");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing_employees = [];
            while ($row = $result->fetch_assoc()) {
                $existing_employees[] = $row['emp_id'];
            }

            // Add only new assignments
            if (!empty($employees)) {
                $stmt = $conn->prepare("INSERT INTO assigned_maintenance (maintenance_request_id, emp_id, assigned_at, status) VALUES (?, ?, NOW(), 'In Progress') ON DUPLICATE KEY UPDATE status = 'In Progress'");
                foreach ($employees as $emp_id) {
                    if (!in_array($emp_id, $existing_employees)) {
                        $stmt->bind_param("is", $request_id, $emp_id);
                        $stmt->execute();
                    }
                }
            }
        }

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
