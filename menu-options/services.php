<?php
require_once("../database.php");
// Check if the user is verified
if (!isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
    header("Location: index.html");
    exit();
}

if (isset($_SESSION['uname'])) {
    $uname = $_SESSION['uname'];
    
    // Get customer room number from database if not already in session
    if (!isset($_SESSION['room_number'])) {
        $stmt = $conn->prepare("SELECT room FROM guess WHERE uname = ?");
        $stmt->bind_param("s", $uname);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $_SESSION['room_number'] = $row['room'];
        }
        $stmt->close();
    }
} else {
    header("Location: index.html");
    exit();
}

// Check if we need to debug the date issue
$showDebug = isset($_GET['debug']) && $_GET['debug'] == 1;

// Check if user has an active booking (is within check-in and check-out period)
// Default to true if flags not set to prevent breaking functionality
$isCheckedIn = $_SESSION['is_checked_in'] ?? true;
$isCheckedOut = $_SESSION['is_checked_out'] ?? false;
$isActiveBooking = $isCheckedIn && !$isCheckedOut;

$bookingMessage = $_SESSION['booking_message'] ?? '';
$bookingStatus = $_SESSION['booking_status'] ?? 'unknown';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="../img/logo.webp">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <style>
        .disabled-card {
            opacity: 0.6;
            pointer-events: none;
        }
        .booking-alert {
            border-left: 4px solid #f39c12;
            background-color: #fff8e1;
        }
    </style>
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

        <!-- Debug Section -->
        <?php if ($showDebug): ?>
        <div class="alert alert-secondary m-3">
            <h5 class="mb-2">Debug Information</h5>
            <pre><?php print_r($_SESSION['date_debug'] ?? 'No debug info available'); ?></pre>
            <p>API Check-out: <?php echo htmlspecialchars($_SESSION['debug_booking_record']['check_out'] ?? 'Not set'); ?></p>
            <p>Parsed Check-out: <?php echo htmlspecialchars($_SESSION['check_out'] ?? 'Not set'); ?></p>
            <p>Current status: <?php echo $isActiveBooking ? 'Active booking' : 'Inactive booking'; ?></p>
            <a href="date_debug.php" class="btn btn-sm btn-primary">View Detailed Debug</a>
        </div>
        <?php endif; ?>

        <!-- Booking Status Alert -->
        <?php if (!$isActiveBooking && !empty($bookingMessage)): ?>
            <div class="alert booking-alert m-3" role="alert">
                <h6 class="fw-bold mb-1">
                    <?php if ($bookingStatus == 'upcoming'): ?>
                        <i class="fas fa-calendar-alt me-2"></i> Upcoming Stay
                    <?php elseif ($bookingStatus == 'past'): ?>
                        <i class="fas fa-calendar-check me-2"></i> Past Stay
                    <?php else: ?>
                        <i class="fas fa-info-circle me-2"></i> Booking Notice
                    <?php endif; ?>
                </h6>
                <p class="mb-0"><?php echo htmlspecialchars($bookingMessage); ?></p>
            </div>
        <?php endif; ?>

        <!-- Main Chat Section -->
        <div class="menu-body py-3 mt-4 p-3">
            <h5 class="fw-semibold">All Services</h5>
            <!-- Menu-based Options as List Cards -->
            <div class="msg-header">
                <div class="user-options grid-template">
                    <a href="<?php echo $isActiveBooking ? 'req-room-service.php' : '#'; ?>" class="menu-card <?php echo !$isActiveBooking ? 'disabled-card' : ''; ?>">
                        <div class="icon-wrapper">
                            <i class="fas fa-bell-concierge"></i>
                        </div>
                        <div class="menu-text">
                            <h5 class="fw-semibold">Request Room Service</h5>
                            <p>(e.g., extra towels, pillows, etc.)</p>
                        </div>
                    </a>
                    <a href="<?php echo $isActiveBooking ? 'req-housekeeping.php' : '#'; ?>" class="menu-card <?php echo !$isActiveBooking ? 'disabled-card' : ''; ?>">
                        <div class="icon-wrapper">
                            <i class="fas fa-broom"></i>
                        </div>
                        <div class="menu-text">
                            <h5 class="fw-semibold">Request Housekeeping</h5>
                            <p>(e.g., room cleaning, trash collection)</p>
                        </div>
                    </a>
                    <a href="<?php echo $isActiveBooking ? 'req-maintenance.php' : '#'; ?>" class="menu-card <?php echo !$isActiveBooking ? 'disabled-card' : ''; ?>">
                        <div class="icon-wrapper">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="menu-text">
                            <h5 class="fw-semibold">Report Maintenance</h5>
                            <p>(e.g., broken appliances, leaky faucets)</p>
                        </div>
                    </a>
                    <a href="<?php echo $isActiveBooking ? 'lost-and-found.php' : '#'; ?>" class="menu-card <?php echo !$isActiveBooking ? 'disabled-card' : ''; ?>">
                        <div class="icon-wrapper">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="menu-text">
                            <h5 class="fw-semibold">Lost and Found</h5>
                            <p>(e.g., report lost items or check for found items)</p>
                        </div>
                    </a>
                    <a href="<?php echo $isActiveBooking ? 'steppingOut.php' : '#'; ?>" class="menu-card <?php echo !$isActiveBooking ? 'disabled-card' : ''; ?>">
                        <div class="icon-wrapper">
                            <i class="fas fa-hotel"></i>
                        </div>
                        <div class="menu-text">
                            <h5 class="fw-semibold">Stepping Out</h5>
                            <p>(e.g., settle charges, return your keys)</p>
                        </div>
                    </a>
                    <a href="check-status.php" class="menu-card">
                        <div class="icon-wrapper">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="menu-text">
                            <h5 class="fw-semibold">Check Request Status</h5>
                            <p>(view status of your service requests and food orders)</p>
                        </div>
                    </a>
                </div>
            </div>
            
            <?php if (!$isActiveBooking): ?>
            <div class="alert alert-info mt-4" role="alert">
                <h6 class="fw-bold"><i class="fas fa-info-circle me-2"></i> Limited Access</h6>
                <p class="mb-0">
                    <?php if ($bookingStatus == 'upcoming'): ?>
                        You can only access the Check Request Status feature before your check-in date.
                    <?php elseif ($bookingStatus == 'past'): ?>
                        You can only access the Check Request Status feature after your check-out date.
                    <?php else: ?>
                        Some services are currently unavailable. Please contact reception for assistance.
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Handle clicks on disabled cards
            $('.disabled-card').on('click', function(e) {
                e.preventDefault();
                alert('This service is not available <?php echo ($bookingStatus == "upcoming") ? "before check-in" : "after check-out"; ?>. Please contact reception for assistance.');
            });

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
