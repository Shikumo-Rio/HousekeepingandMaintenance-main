<?php
// This file contains the fixes for the login redirection issue

// This function should be added to login.php where it processes the form submission
function handleFaceVerification($user) {
    // Set pending login information in session
    $_SESSION['pending_login'] = true;
    $_SESSION['pending_username'] = $user['username'];
    $_SESSION['pending_user_id'] = $user['emp_id'];
    $_SESSION['pending_user_type'] = $user['user_type'];
    
    // Check if user has face images registered
    global $conn;
    $face_check = $conn->prepare("SELECT COUNT(*) as count FROM face_images WHERE username = ?");
    $face_check->bind_param("s", $user['username']);
    $face_check->execute();
    $face_result = $face_check->get_result()->fetch_assoc();
    
    if ($face_result['count'] > 0) {
        // User has face images, redirect to face verification
        header("Location: verify_face.php");
        exit;
    } else {
        // User doesn't have face images, skip verification
        completeLogin($user);
    }
}

// This function completes the login process
function completeLogin($user) {
    // Clear any pending login status
    unset($_SESSION['pending_login']);
    unset($_SESSION['pending_username']);
    unset($_SESSION['pending_user_id']);
    unset($_SESSION['pending_user_type']);
    
    // Set regular session variables
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['emp_id'] = $user['emp_id'];
    
    // Update user status
    global $conn;
    $update_stmt = $conn->prepare("UPDATE login_accounts SET is_online = 1, last_activity = NOW() WHERE username = ?");
    $update_stmt->bind_param("s", $user['username']);
    $update_stmt->execute();
    
    // Update employee status
    $update_employee_status = $conn->prepare("UPDATE employee SET status = 'active' WHERE emp_id = ?");
    $update_employee_status->bind_param("i", $user['emp_id']);
    $update_employee_status->execute();
    
    // Log the login
    $logQuery = "INSERT INTO login_logs (emp_id) VALUES (?)";
    $log_stmt = $conn->prepare($logQuery);
    $log_stmt->bind_param("i", $user['emp_id']);
    $log_stmt->execute();
    
    // Redirect based on user type
    switch ($user['user_type']) {
        case 'Employee':
            header("Location: /housekeepingandmaintenance-main/housekeepers/index.php");
            break;
        case 'Admin':
            header("Location: dashboard.php");
            break;
        case 'Maintenance':
            header("Location: /housekeepingandmaintenance-main/maintenance-department/maintenance.php");
            break;
        case 'maintenance-staff':
            header("Location: /housekeepingandmaintenance-main/maintenance-staff/staff.php");
            break;
        default:
            header("Location: login.php");
    }
    exit;
}

// Implementation guide:
/*
In your login.php, when processing the login form:

1. After successfully verifying username/password:
   
   // Get user info
   $user = $result->fetch_assoc();
   
   // Check for face verification
   handleFaceVerification($user);

This will ensure proper redirection to the face verification page only when
the user has registered face images, otherwise it will complete the login process.
*/
?>
