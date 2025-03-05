<?php
session_start();
require_once 'database.php'; // Ensure this includes your MySQLi connection setup

// Check if the user is logged in
if (isset($_SESSION['username'])) {
    $emp_id = $_SESSION['emp_id'];

    // Update login_accounts to set is_online to 0 (offline)
    $stmt = $conn->prepare("UPDATE login_accounts SET is_online = 0 WHERE emp_id = ?");
    $stmt->bind_param("i", $emp_id);
    $stmt->execute();

    // Update employee table to set status to inactive
    $update_employee_status = $conn->prepare("UPDATE employee SET status = 'inactive' WHERE emp_id = ?");
    $update_employee_status->bind_param("i", $emp_id);
    $update_employee_status->execute();

    // Destroy session
    session_destroy();

    // Redirect to login page
    header("Location: login.php");
    exit;
}


?>