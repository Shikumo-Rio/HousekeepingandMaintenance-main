<?php
// Include your database connection file
include 'database.php';

// Check if the task ID is provided via GET request
if (isset($_GET['id'])) {
    $taskId = intval($_GET['id']); // Convert the task ID to an integer for security

    // Prepare SQL query to fetch task details based on the provided ID
    $query = "SELECT id, uname, request, details, room, created_at 
              FROM assigntask 
              WHERE id = ?";
              
    if ($stmt = $conn->prepare($query)) {
        // Bind the task ID to the prepared statement
        $stmt->bind_param("i", $taskId);
        $stmt->execute();

        // Get the result and fetch it as an associative array
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $taskDetails = $result->fetch_assoc();
            
            // Return the result as a JSON object
            echo json_encode($taskDetails);
        } else {
            // If no task is found, return an error message
            echo json_encode(['error' => 'Task not found.']);
        }
        
        // Close the statement
        $stmt->close();
    } else {
        // If query fails, return an error
        echo json_encode(['error' => 'Failed to prepare SQL statement.']);
    }
} else {
    // If task ID is not provided, return an error
    echo json_encode(['error' => 'Task ID not provided.']);
}

// Close the database connection
$conn->close();
?>
