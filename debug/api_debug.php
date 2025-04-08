<?php
// Restrict access to this page - comment out for development or use proper authentication
// For production, you might want to restrict this page by IP address or admin login
/*
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access Denied');
}
*/

// Define API key and URL
$api_key = "f5230f2a7c74ff308dbd06998debf755b9fa279d5beba28e9f0a86fd79e42383";
$api_url = "https://core2.paradisehoteltomasmorato.com/integ/checkin.php?api_key={$api_key}";

// Current date in the format used for comparison
$current_date = date('d-m-Y');
$current_timestamp = strtotime($current_date);

// Error handling
$error = null;
$bookings = [];
$raw_response = null;

// Fetch data from the API
try {
    // Set a reasonable timeout
    $context = stream_context_create([
        'http' => [
            'timeout' => 10 // seconds
        ]
    ]);

    $api_response = @file_get_contents($api_url, false, $context);
    
    if ($api_response === false) {
        throw new Exception("Failed to connect to the API. Please verify the API endpoint and your internet connection.");
    }
    
    $response = json_decode($api_response, true);
    $raw_response = $response; // Save raw response for display later
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Failed to parse JSON response: " . json_last_error_msg());
    }
    
    // Extract bookings data
    if (isset($response['data']) && is_array($response['data'])) {
        $bookings = $response['data'];
    } elseif (is_array($response)) {
        $bookings = $response;
    } else {
        throw new Exception("API response is not in the expected format. Received: " . gettype($response));
    }
    
    if (empty($bookings)) {
        throw new Exception("No booking data found in the API response.");
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Function to determine status based on dates
function getStatusInfo($check_in, $check_out, $current_date) {
    $check_in_ts = strtotime($check_in);
    $check_out_ts = strtotime($check_out);
    $current_ts = strtotime($current_date);
    
    if ($current_ts < $check_in_ts) {
        return [
            'status' => 'future',
            'message' => 'Not checked in yet',
            'can_login' => false,
            'class' => 'bg-warning text-dark',
            'icon' => 'calendar-minus'
        ];
    } elseif ($current_ts > $check_out_ts) {
        return [
            'status' => 'past',
            'message' => 'Already checked out',
            'can_login' => false,
            'class' => 'bg-danger text-white',
            'icon' => 'calendar-x'
        ];
    } else {
        return [
            'status' => 'active',
            'message' => 'Active stay (can login)',
            'can_login' => true,
            'class' => 'bg-success text-white',
            'icon' => 'calendar-check'
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Debug Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .header {
            background-color: #343a40;
            color: white;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 5px;
        }
        .card {
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .card-header {
            font-weight: 600;
        }
        .login-code {
            font-family: monospace;
            padding: 8px;
            background: #f1f1f1;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        .date-box {
            padding: 6px 10px;
            border-radius: 3px;
            font-weight: 500;
            display: inline-block;
        }
        .json-raw {
            background-color: #272822;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            max-height: 400px;
            overflow: auto;
            font-family: monospace;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">Paradise Hotel API Debug Tool</h1>
                <p class="m-0">Current Date: <?php echo $current_date; ?></p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <h4><i class="fas fa-exclamation-triangle me-2"></i> Error</h4>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php else: ?>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">API Summary</div>
                    <div class="card-body">
                        <p><strong>Total Bookings:</strong> <?php echo count($bookings); ?></p>
                        <p><strong>API Endpoint:</strong> <span class="text-muted"><?php echo htmlspecialchars($api_url); ?></span></p>
                        <p><strong>Response Structure:</strong> 
                            <?php if (isset($response['data'])): ?>
                                <span class="badge bg-info">Nested under "data" key</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Direct array</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Login Status Guide</div>
                    <div class="card-body">
                        <p><span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Active</span> - Guest can log in</p>
                        <p><span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> Future</span> - Guest can't log in yet (before check-in date)</p>
                        <p><span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i> Past</span> - Guest can't log in (after check-out date)</p>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="h4 mb-3">Booking Details & Login Information</h2>
        
        <?php foreach ($bookings as $index => $booking): ?>
            <?php 
                $firstName = explode(' ', $booking['customer_name'])[0]; 
                $username = strtolower($firstName . $booking['room_no']);
                $username_without_dash = strtolower($firstName . str_replace('-', '', $booking['room_no']));
                $status_info = getStatusInfo($booking['check_in'], $booking['check_out'], $current_date);
            ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><?php echo htmlspecialchars($booking['customer_name']); ?></span>
                    <span class="badge <?php echo $status_info['class']; ?>">
                        <i class="fas fa-<?php echo $status_info['icon']; ?> me-1"></i>
                        <?php echo $status_info['message']; ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="card-title">Booking Information</h5>
                            <p><strong>Room:</strong> <?php echo htmlspecialchars($booking['room_no']); ?></p>
                            <p>
                                <strong>Check-in:</strong> 
                                <span class="date-box <?php echo strtotime($booking['check_in']) > $current_timestamp ? 'bg-warning text-dark' : 'bg-info text-white'; ?>">
                                    <?php echo htmlspecialchars($booking['check_in']); ?>
                                </span>
                            </p>
                            <p>
                                <strong>Check-out:</strong> 
                                <span class="date-box <?php echo strtotime($booking['check_out']) < $current_timestamp ? 'bg-danger text-white' : 'bg-info text-white'; ?>">
                                    <?php echo htmlspecialchars($booking['check_out']); ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h5 class="card-title">Login Information</h5>
                            <p><strong>Username Options:</strong></p>
                            <div class="login-code mb-2">
                                <?php echo htmlspecialchars($username); ?> 
                                <?php if ($status_info['can_login']): ?>
                                    <i class="fas fa-check-circle text-success"></i>
                                <?php else: ?>
                                    <i class="fas fa-times-circle text-danger"></i>
                                <?php endif; ?>
                            </div>
                            
                            <div class="login-code">
                                <?php echo htmlspecialchars($username_without_dash); ?> 
                                <i class="fas fa-info-circle text-info" data-bs-toggle="tooltip" title="Alternative format without dash"></i>
                            </div>
                            
                            <p class="mt-3">
                                <strong>Can Log In:</strong>
                                <?php if ($status_info['can_login']): ?>
                                    <span class="badge bg-success">Yes</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">No - <?php echo $status_info['message']; ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="card mt-5">
            <div class="card-header">
                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" 
                       data-bs-target="#rawJsonData">
                    Show Raw JSON Data
                </button>
            </div>
            <div class="collapse" id="rawJsonData">
                <div class="card-body">
                    <pre class="json-raw"><?php echo htmlspecialchars(json_encode($raw_response, JSON_PRETTY_PRINT)); ?></pre>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>
