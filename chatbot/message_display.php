<?php
// Start the session to access session variables

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="wrapper">
        <div class="message">
            <?php
            // Check if there is a logout message in the session
            if (isset($_SESSION['logout_message'])) {
                echo "<p>" . $_SESSION['logout_message'] . "</p>";
                // Remove the message from the session
                unset($_SESSION['logout_message']);
            } else {
                echo "<p>No message to display.</p>";
            }
            ?>
        </div>
    </div>
</body>
</html>
