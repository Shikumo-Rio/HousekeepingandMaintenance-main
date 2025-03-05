<?php
require_once("../database.php");

// Check if the user is logged in and username is set in the session
if (!isset($_SESSION['uname'])) {
    // If not, redirect to login page or handle it as needed
    header("Location: index.html");
    exit();
}

$uname = $_SESSION['uname'];

$sqlRoom = "SELECT room FROM guess WHERE uname = '$uname'";
$resultRoom = $conn->query($sqlRoom);

// Check if the room was found for the user in the guess table
if ($resultRoom->num_rows > 0) {
    // Fetch the room number
    $rowRoom = $resultRoom->fetch_assoc();
    $room = $rowRoom['room'];
} else {
    // Handle case where room number is not found, if needed
    echo "Room not found for user.";
    exit();
}
// Get the form data (the new message)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['message'])) {
    $newMessage = $conn->real_escape_string($_POST['message']);
    
    // Fetch the current messages for the customer based on the username
    $sql = "SELECT request, details FROM customer_messages WHERE uname = '$uname'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // The customer already exists in the database, so update the next available column
        $row = $result->fetch_assoc();
        $updateColumn = "";

        // Determine which column is empty
        if (empty($row['request'])) {
            $updateColumn = "request";
        } elseif (empty($row['details'])) {
            $updateColumn = "details";
        }
        if ($updateColumn) {
            // Update the next available column with the new message
            $sql = "UPDATE customer_messages SET $updateColumn = '$newMessage', room = '$room' WHERE uname = '$uname'";
            $conn->query($sql); // Handle success silently
        }
    } else {
        // No existing entry for this customer, insert a new row
        $sql = "INSERT INTO customer_messages (uname, room, request, status) VALUES ('$uname', '$room', '$newMessage', 'pending')";
        $conn->query($sql); // Handle success silently
    }
}

// Close the connection
$conn->close();
?>