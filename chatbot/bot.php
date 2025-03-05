<?php
require_once("../database.php");
// Check if the user is verified
if (!isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
    header("Location: index.html");
    exit();
}

if (isset($_SESSION['uname'])) {
    $uname = $_SESSION['uname'];
} else {
    header("Location: index.html");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat | Bot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>
<body>
    <div class="chat-container container p-0">
        <!-- Header Section -->
        <div class="chat-header d-flex align-items-center justify-content-between py-2">
            <div class="d-flex align-items-center">
                <img src="../img/logo.webp" alt="User Icon" class="rounded-circle me-2" width="40" height="40">
                <div>
                    <h5 class="mb-0">Paradise Chatbot</h5>
                </div>
            </div>
        </div>

        <!-- Main Chat Section -->
        <div class="chat-body py-3">
            <div class="bot-inbox">
                <div class="icon">
                    <img src="../img/logo.webp" alt="User Icon" style="width: 40px; height: 40px;">
                </div>
                <div class="msg-header">
                    <p>Hello there <?php echo htmlspecialchars($uname); ?>, I'm here to help you! 👋<br>
                    Please select an option below:</p>
                </div>
            </div>

            <!-- Options for Users (styled like bot-inbox) -->
            <div class="bot-inbox options-container">
                <div class="icon">
                    <img src="../img/logo.webp" alt="User Icon" style="width: 40px; height: 40px;">
                </div>
                <div class="msg-header">
                    <div class="user-options">
                        <button class="btn option-btn" data-option="Room service">Room service</button>
                        <button class="btn option-btn" data-option="Extra Amenities">Extra Amenities</button>
                        <button class="btn option-btn" data-option="Checkout time">Checkout time</button>
                        <button class="btn option-btn" data-option="Checkout">Checkout</button>
                    </div>
                </div>
            </div>
        </div>


        <!-- Bottom Chat Input Section -->
        <div class="chat-input-container py-2">
            <form id="messageForm" class="d-flex">
                <input id="data" name="message" type="text" placeholder="Type your concern here.." class="form-control" required>
                <button id="send-btn" type="button" class="btn ms-2">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('.option-btn').on('click', function() {
                let option = $(this).data('option');
                let msg = '<div class="user-inbox inbox"><div class="msg-header"><p>' + option + '</p></div></div>';
                $(".chat-body").append(msg);

                $.ajax({
                    url: 'message.php',
                    type: 'POST',
                    data: { text: option },
                    success: function(result) {
                        let reply = '<div class="bot-inbox inbox"><div class="icon"><img src="../img/logo.webp" alt="User Icon" style="width: 40px; height: 40px;"></div><div class="msg-header"><p>' + result + '</p></div></div>';
                        $(".chat-body").append(reply);
                        $(".chat-body").scrollTop($(".chat-body")[0].scrollHeight);
                    }
                });
            });

            $("#send-btn").on("click", function(e) {
                e.preventDefault();
                let value = $("#data").val();
                if (value === "") return;
                let msg = '<div class="user-inbox inbox"><div class="msg-header"><p>' + value + '</p></div></div>';
                $(".chat-body").append(msg);
                $("#data").val('');

                $.ajax({
                    url: 'save_message.php',
                    type: 'POST',
                    data: { message: value },
                    success: function() {
                        $.ajax({
                            url: 'message.php',
                            type: 'POST',
                            data: { text: value },
                            success: function(result) {
                                let reply = '<div class="bot-inbox inbox"><div class="icon"><img src="../img/logo.webp" alt="User Icon" style="width: 40px; height: 40px;"></div><div class="msg-header"><p>' + result + '</p></div></div>';
                                $(".chat-body").append(reply);
                                $(".chat-body").scrollTop($(".chat-body")[0].scrollHeight);
                            }
                        });
                    }
                });
            });
        });

    </script>
</body>
</html>
