<?php
require 'vendor/autoload.php';

use Phpml\Classification\MLPClassifier;

function allocateTasks($dbConnection) {
    // First check and update employee status for completed tasks
    updateEmployeeStatusForCompletedTasks($dbConnection);
    
    // Get correct path to model file
    $modelPath = __DIR__ . '/task_model.json';
    
    // Check if model exists
    if (!file_exists($modelPath)) {
        error_log("AI Model not found. Please run train_model.php first");
        return false;
    }

    try {
        // Load the trained model with error handling
        $modelData = file_get_contents($modelPath);
        if ($modelData === false) {
            throw new Exception("Failed to read model file");
        }
        $classifier = unserialize(json_decode($modelData));
        if (!$classifier) {
            throw new Exception("Failed to load model data");
        }
    } catch (Exception $e) {
        error_log("Error loading AI model: " . $e->getMessage());
        return false;
    }

    // Task type mapping (matching Flask API)
    $taskMapping = [
        'Request Amenities' => 1,
        'Housekeeping' => 2,
        'Order Food' => 3
    ];

    // Get pending tasks from customer_messages table
    $query = "SELECT * FROM customer_messages WHERE status = 'pending'";
    $result = mysqli_query($dbConnection, $query);

    while ($task = mysqli_fetch_assoc($result)) {
        // Prepare input data
        $request = $taskMapping[$task['request']] ?? 0;
        $room = (int) $task['room'];
        $detailsLength = strlen($task['details']);
        $hourOfDay = (int) date('H', strtotime($task['created_at']));
        
        // Make prediction with all 4 features
        $prediction = $classifier->predict([
            $request,           // task type
            $room,             // room number
            $detailsLength,    // length of details
            $hourOfDay         // hour of the day
        ]);
        
        // If model suggests assignment (prediction = 0)
        if ($prediction === 0) {
            // Determine role based on request type
            $role = ($task['request'] == 'Housekeeping') ? 'linen_attendant' : 'room_attendant';
            
            // Get available employee
            $staffQuery = "SELECT emp_id, name FROM employee 
                         WHERE role = '$role' 
                         AND status = 'active' 
                         LIMIT 1";
            $staffResult = mysqli_query($dbConnection, $staffQuery);
            
            if ($employee = mysqli_fetch_assoc($staffResult)) {
                // Insert into assigntasks table
                $insertQuery = "INSERT INTO assigntasks 
                              (task_id, request, details, room, emp_name, emp_id, status, uname)
                              VALUES (
                                  '{$task['id']}',
                                  '{$task['request']}',
                                  '{$task['details']}',
                                  '{$task['room']}',
                                  '{$employee['name']}',
                                  '{$employee['emp_id']}',
                                  'Working',
                                  '{$task['uname']}'
                              )";
                mysqli_query($dbConnection, $insertQuery);
                
                // Update customer_messages status
                mysqli_query($dbConnection, "UPDATE customer_messages 
                                          SET status = 'working' 
                                          WHERE id = '{$task['id']}'");
                
                // Update employee status
                mysqli_query($dbConnection, "UPDATE employee 
                                          SET status = 'busy' 
                                          WHERE emp_id = '{$employee['emp_id']}'");
            }
        }
    }
}

function updateEmployeeStatusForCompletedTasks($dbConnection) {
    // Find employees with completed tasks
    $query = "SELECT DISTINCT e.emp_id, e.role 
              FROM employee e
              INNER JOIN assigntasks a ON e.emp_id = a.emp_id
              WHERE e.status = 'busy' 
              AND a.status = 'Completed'";
    
    $result = mysqli_query($dbConnection, $query);
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Check if employee has any ongoing tasks
        $checkOngoingQuery = "SELECT COUNT(*) as ongoing_count 
                             FROM assigntasks 
                             WHERE emp_id = '{$row['emp_id']}' 
                             AND status = 'Working'";
        
        $ongoingResult = mysqli_query($dbConnection, $checkOngoingQuery);
        $ongoingCount = mysqli_fetch_assoc($ongoingResult)['ongoing_count'];
        
        // Only update status if no ongoing tasks
        if ($ongoingCount == 0) {
            mysqli_begin_transaction($dbConnection);
            try {
                // Update employee status to active
                $updateQuery = "UPDATE employee 
                              SET status = 'active' 
                              WHERE emp_id = '{$row['emp_id']}'";
                mysqli_query($dbConnection, $updateQuery);
                
                mysqli_commit($dbConnection);
            } catch (Exception $e) {
                mysqli_rollback($dbConnection);
                error_log("Error updating employee status: " . $e->getMessage());
            }
        }
    }
}

// Add event handler for task completion
function handleTaskCompletion($dbConnection, $taskId) {
    mysqli_begin_transaction($dbConnection);
    try {
        // Update task status
        $updateTaskQuery = "UPDATE assigntasks 
                          SET status = 'Completed' 
                          WHERE task_id = ?";
        $stmt = mysqli_prepare($dbConnection, $updateTaskQuery);
        mysqli_stmt_bind_param($stmt, "s", $taskId);
        mysqli_stmt_execute($stmt);
        
        // Update customer message status
        $updateMessageQuery = "UPDATE customer_messages 
                             SET status = 'completed' 
                             WHERE id = ?";
        $stmt = mysqli_prepare($dbConnection, $updateMessageQuery);
        mysqli_stmt_bind_param($stmt, "s", $taskId);
        mysqli_stmt_execute($stmt);
        
        // Call status update function
        updateEmployeeStatusForCompletedTasks($dbConnection);
        
        mysqli_commit($dbConnection);
        return true;
    } catch (Exception $e) {
        mysqli_rollback($dbConnection);
        error_log("Error completing task: " . $e->getMessage());
        return false;
    }
}
