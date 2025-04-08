<?php
session_start();
require_once("../database.php");


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Debug Information</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .json-key { color: #e83e8c; }
        .json-value { color: #28a745; }
        .json-string { color: #007bff; }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">API Debug Information</h1>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Request Details</h4>
            </div>
            <div class="card-body">
                <h5 class="card-title">API Request Parameters</h5>
                <pre><?php print_r($api_debug['request'] ?? []); ?></pre>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">Response Details</h4>
            </div>
            <div class="card-body">
                <?php if (isset($api_debug['response']) && is_array($api_debug['response'])): ?>
                    <h5 class="card-title">Booking Information</h5>
                    <?php if (isset($api_debug['response']['booking_info'])): ?>
                        <?php $booking = $api_debug['response']['booking_info']; ?>
                        <ul class="list-group mb-4">
                            <li class="list-group-item"><strong>Room:</strong> <?php echo htmlspecialchars($booking['room'] ?? 'N/A'); ?></li>
                            <li class="list-group-item"><strong>Check-in:</strong> <?php echo htmlspecialchars($booking['check_in'] ?? 'N/A'); ?></li>
                            <li class="list-group-item"><strong>Check-out:</strong> <?php echo htmlspecialchars($booking['check_out'] ?? 'N/A'); ?></li>
                            <li class="list-group-item"><strong>Status:</strong> <?php echo htmlspecialchars($api_debug['response']['status'] ?? 'N/A'); ?></li>
                        </ul>
                    <?php endif; ?>
                    
                    <h5 class="card-title">Full Response</h5>
                    <pre><?php print_r($api_debug['response']); ?></pre>
                <?php else: ?>
                    <div class="alert alert-warning">No response data available</div>
                <?php endif; ?>
                
                <?php if (!empty($api_debug['raw_response'])): ?>
                    <h5 class="card-title mt-4">Raw Response</h5>
                    <pre><?php echo htmlspecialchars($api_debug['raw_response']); ?></pre>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($api_debug['error'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Error Information</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($api_debug['error']); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <a href="check-status.php" class="btn btn-primary">Return to Status Page</a>
    </div>
</body>
</html>
