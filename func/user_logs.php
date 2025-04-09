<?php
/**
 * User Logs - Functions for tracking user activities
 */

// Function to record a user activity log
function addUserLog($conn, $log_type, $action, $details) {
    // Get username from session if available
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'System';
    
    try {
        // Prepare statement
        $stmt = $conn->prepare("INSERT INTO user_logs (emp_id, log_type, action, details, created_at) 
                               VALUES (?, ?, ?, ?, NOW())");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
                               
        // Bind parameters
        $stmt->bind_param("ssss", $username, $log_type, $action, $details);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        // Execute and close
        $stmt->close();
        
        return true;
    } catch (Exception $e) {
        // Log error but don't disrupt the main functionality
        error_log("Error logging user activity: " . $e->getMessage());
        return false;
    }
}

// Function for logging task status changes
function logTaskStatusChange($conn, $task_id, $old_status, $new_status) {
    $details = "Task #$task_id status changed from '$old_status' to '$new_status'";
    return addUserLog($conn, 'task', 'status_change', $details);
}

// Function for logging task assignments
function logTaskAssignment($conn, $task_id, $emp_id, $emp_name) {
    $details = "Task #$task_id assigned to employee #$emp_id ($emp_name)";
    return addUserLog($conn, 'task', 'assignment', $details);
}

// Function for logging new service requests
function logNewServiceRequest($conn, $task_id, $request_type, $room) {
    $details = "New service request #$task_id created for $request_type in room $room";
    return addUserLog($conn, 'task', 'creation', $details);
}

/**
 * Log user activity to the user_logs table
 * 
 * @param object $conn Database connection
 * @param string $username Username performing the action
 * @param string $log_type Type of log (task, system, etc.)
 * @param string $action The action performed
 * @param string $details Additional details
 */
function insertUserLog($conn, $username, $log_type, $action, $details) {
    try {
        $stmt = $conn->prepare("INSERT INTO user_logs (emp_id, log_type, action, details, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $username, $log_type, $action, $details);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        // Silently fail - we don't want logging to break core functionality
        error_log("Error logging user activity: " . $e->getMessage());
    }
}

/**
 * Direct logging to user_logs table using basic SQL query
 * Use this function to log user activities without modifying existing code
 * 
 * @param object $conn Database connection
 * @param string $action The action being performed
 * @param string $details Additional details about the action
 */
function logDirectToDatabase($conn, $action, $details) {
    // Get current username from session or use 'System' as default
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'System';
    $log_type = 'user_action';
    
    // Direct SQL query to insert log
    $sql = "INSERT INTO user_logs (emp_id, log_type, action, details, created_at) 
            VALUES ('$username', '$log_type', '$action', '$details', NOW())";
    
    // Execute query - using non-prepared statement for minimal code changes
    mysqli_query($conn, $sql);
}

/**
 * Function to log report generation actions
 * 
 * @param object $conn Database connection
 * @param string $username Username performing the action
 * @param string $reportType Type of report being generated
 * @param string $format Format of the report
 */
function logReportGeneration($conn, $username, $reportType, $format) {
    $logType = 'Export';
    $action = 'Generate Report';
    $details = "Exported $reportType data in $format format";
    $stmt = $conn->prepare("INSERT INTO user_logs (emp_id, log_type, action, details, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $username, $logType, $action, $details);
    $stmt->execute();
}
?>
