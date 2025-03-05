<?php
// connecting to the database
require_once("../database.php");


// getting user message through ajax
$getMesg = mysqli_real_escape_string($conn, $_POST['text']);

// Store user messages in session
if (!isset($_SESSION['conversation'])) {
    $_SESSION['conversation'] = [];
}
$_SESSION['conversation'][] = $getMesg; // Add the latest message to the session

// Check user query against database queries
$check_data = "SELECT replies FROM chatbot WHERE queries LIKE '%$getMesg%'";
$run_query = mysqli_query($conn, $check_data) or die("Error");

// If user query matched a database query, show the reply
if (mysqli_num_rows($run_query) > 0) {
    $fetch_data = mysqli_fetch_assoc($run_query);
    $reply = $fetch_data['replies'];
    echo $reply;
} else {
    // Check for keywords to generate a dynamic response
    $keywords = ['towel', 'food', 'check out', 'internet', 'pillow']; // Example keywords
    $dynamicReply = "Sorry, I can't understand you!";
    
    foreach ($keywords as $keyword) {
        if (stripos($getMesg, $keyword) !== false) {
            // Generate a dynamic reply based on the keyword
            switch ($keyword) {
                case 'towel':
                    $dynamicReply = "Sure! I can get you extra towels.";
                    break;
                case 'food':
                    $dynamicReply = "What kind of food would you like to order?";
                    break;
                case 'check out':
                    $dynamicReply = "What time would you like to check out?";
                    break;
                case 'internet':
                    $dynamicReply = "The Wi-Fi password is 'Paradise123'.";
                    break;
                case 'pillow':
                    $dynamicReply = "Sure! I can get you extra pillows.";
                    break;
            }
            break; // Exit loop after finding the first matching keyword
        }
    }

    // If no keyword matched, return the generic reply
    echo $dynamicReply;
}
?>
