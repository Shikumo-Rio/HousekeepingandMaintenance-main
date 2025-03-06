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
    <div class="menu-container container p-0 m-0">
        <!-- Header Section -->
        <div class="menu-header d-flex align-items-center justify-content-between py-2 p-3">
            <div class="d-flex align-items-center">
                <img src="../img/logo.webp" alt="User Icon" class="rounded-circle me-2" width="45" height="45">
                <div>
                    <h5 class="mb-0 fw-semibold">Paradise Hotel</h5>
                </div>
            </div>
        </div>
        <!-- Image Section Below Header -->
        <img src="header-image.jpeg" alt="Hotel View" class="header-image">

        <!-- Main Chat Section -->
        <div class="menu-body py-3 mt-4 p-3">
            <h5 class="fw-semibold">All Services</h5>
            <!-- Menu-based Options as List Cards -->
            <div class="msg-header">
                <div class="user-options grid-template">
                    <a href="req-room-service.php" class="menu-card">
                        <div class="icon-wrapper">
                            <i class="fas fa-bell-concierge"></i>
                        </div>
                        <div class="menu-text">
                            <h5 class="fw-semibold">Request Room Service</h5>
                            <p>(e.g., extra towels, pillows, etc.)</p>
                        </div>
                    </a>
                    <a href="req-housekeeping.php" class="menu-card">
                        <div class="icon-wrapper">
                            <i class="fas fa-broom"></i>
                        </div>
                        <div class="menu-text">
                            <h5 class="fw-semibold">Request Housekeeping</h5>
                            <p>(e.g., room cleaning, trash collection)</p>
                        </div>
                    </a>
                    <a href="req-maintenance.php" class="menu-card">
                        <div class="icon-wrapper">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="menu-text">
                            <h5 class="fw-semibold">Report Maintenance</h5>
                            <p>(e.g., broken appliances, leaky faucets)</p>
                        </div>
                    </a>
                    <a href="lost-and-found.php" class="menu-card">
                        <div class="icon-wrapper">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="menu-text">
                            <h5 class="fw-semibold">Lost and Found</h5>
                            <p>(e.g., report lost items or check for found items)</p>
                        </div>
                    </a>
                    <a href="steppingOut.php" class="menu-card">
                        <div class="icon-wrapper">
                            <i class="fas fa-hotel"></i>
                        </div>
                        <div class="menu-text">
                            <h5 class="fw-semibold">Stepping Out</h5>
                            <p>(e.g., settle charges, return your keys)</p>
                        </div>
                    </a>
                </div>
            </div>
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
