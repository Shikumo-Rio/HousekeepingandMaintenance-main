<?php
session_start();
require_once("../database.php");

// Check if the user is verified
if (!isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
    header("Location: index.html");
    exit();
}

// Function to prettify JSON for display
function prettyJson($json) {
    if (is_string($json)) {
        $json = json_decode($json, true);
    }
    return json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Date Debugging</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .debug-section {
            margin-bottom: 20px;
            border-left: 4px solid #17a2b8;
            padding-left: 15px;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Date Debugging Information</h1>
            <a href="services.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Return to Services
            </a>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Date Processing</h4>
            </div>
            <div class="card-body">
                <div class="debug-section">
                    <h5>Date Debug Information</h5>
                    <pre><?php print_r($_SESSION['date_debug'] ?? 'No date debug info available'); ?></pre>
                </div>
                
                <div class="debug-section">
                    <h5>Current PHP Date/Time</h5>
                    <?php
                    date_default_timezone_set('Asia/Manila');
                    echo "<p>Server time: " . date('d-m-Y H:i:s') . "</p>";
                    echo "<p>Timezone: " . date_default_timezone_get() . "</p>";
                    ?>
                </div>
                
                <div class="debug-section">
                    <h5>Booking Status Calculation</h5>
                    <?php
                    $current = new DateTime();
                    $current->setTime(0, 0, 0);
                    
                    $checkIn = isset($_SESSION['check_in']) ? 
                        DateTime::createFromFormat('d-m-Y', $_SESSION['check_in']) : null;
                    $checkOut = isset($_SESSION['check_out']) ? 
                        DateTime::createFromFormat('d-m-Y', $_SESSION['check_out']) : null;
                    
                    if ($checkIn && $checkOut) {
                        echo "<p>Current date: " . $current->format('d-m-Y') . "</p>";
                        echo "<p>Check-in date: " . $checkIn->format('d-m-Y') . "</p>";
                        echo "<p>Check-out date: " . $checkOut->format('d-m-Y') . "</p>";
                        echo "<p>Is checked in? " . ($current >= $checkIn ? 'Yes' : 'No') . "</p>";
                        echo "<p>Is checked out? " . ($current > $checkOut ? 'Yes' : 'No') . "</p>";
                    } else {
                        echo "<p>Could not parse date objects</p>";
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">Booking Record from API</h4>
            </div>
            <div class="card-body">
                <pre><?php echo htmlspecialchars(prettyJson($_SESSION['debug_booking_record'] ?? 'No booking record available')); ?></pre>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0">Raw API Response</h4>
            </div>
            <div class="card-body">
                <pre><?php 
                    if (isset($_SESSION['raw_api_response'])) {
                        $raw = $_SESSION['raw_api_response'];
                        // Try to prettify if it's JSON
                        $decoded = json_decode($raw, true);
                        if ($decoded) {
                            echo htmlspecialchars(prettyJson($decoded));
                        } else {
                            echo htmlspecialchars($raw);
                        }
                    } else {
                        echo "No raw API response available";
                    }
                ?></pre>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h4 class="mb-0">Session Data</h4>
            </div>
            <div class="card-body">
                <pre><?php 
                    $safeSession = $_SESSION;
                    // Remove potentially sensitive data
                    unset($safeSession['raw_api_response']);
                    echo htmlspecialchars(prettyJson($safeSession));
                ?></pre>
            </div>
        </div>
        
        <a href="services.php" class="btn btn-primary">Return to Services</a>
        <a href="services.php?debug=1" class="btn btn-outline-info">View Services with Debug</a>
    </div>
</body>
</html>
