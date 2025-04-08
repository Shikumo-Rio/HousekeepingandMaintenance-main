<?php
require_once("../database.php");
// Check if the user is verified
if (!isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
    header("Location: index.php");
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
    header("Location: index.php");
    exit();
}

// Set default timezone to ensure consistent date handling
date_default_timezone_set('Asia/Manila');

// Get the view parameter - default to 'today'
$view = isset($_GET['view']) ? $_GET['view'] : 'today';
$todayDate = date('Y-m-d');

// Check if there's a checkout message
$checkoutMessage = '';
if (isset($_SESSION['booking_status']) && $_SESSION['booking_status'] == 'past') {
    $checkoutMessage = $_SESSION['booking_message'] ?? 
                       "Your stay ended on {$_SESSION['check_out']}. You cannot access services after checkout.";
}

// For debugging purposes
if (isset($_GET['debug'])) {
    echo "<pre>";
    echo "Session variables:\n";
    print_r($_SESSION);
    echo "\nCurrent date: " . date('d-m-Y') . "\n";
    echo "</pre>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <style>
        .status-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .status-pending {
            color: #f39c12;
        }
        .status-completed {
            color: #27ae60;
        }
        .status-processing {
            color: #3498db;
        }
        .back-button {
            margin-bottom: 15px;
        }
        .view-toggle {
            margin-bottom: 20px;
        }
        .view-toggle .btn {
            margin-right: 5px;
        }
        .active-view {
            background-color: #4e73df;
            color: white;
        }
        .empty-state {
            text-align: center;
            padding: 30px 0;
            color: #6c757d;
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
        
        <!-- Main Content Section -->
        <div class="menu-body py-3 mt-4 p-3">
            <div class="back-button">
                <a href="services.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Services
                </a>
            </div>
            
            <?php if (!empty($checkoutMessage)): ?>
                <div class="alert alert-warning mb-4" role="alert">
                    <?php echo $checkoutMessage; ?>
                </div>
            <?php endif; ?>

            <h4 class="fw-semibold">Your Request Status</h4>
            
            <!-- View Toggle Buttons -->
            <div class="view-toggle">
                <a href="?view=today" class="btn btn-sm <?php echo $view == 'today' ? 'active-view' : 'btn-outline-primary'; ?>">
                    Today's Requests
                </a>
                <a href="?view=all" class="btn btn-sm <?php echo $view == 'all' ? 'active-view' : 'btn-outline-primary'; ?>">
                    All Past Requests
                </a>
            </div>
            
            <!-- Service Requests Section -->
            <div class="mt-4">
                <h5 class="fw-semibold">Service Requests</h5>
                
                <?php
                // Get service requests from customer_messages table
                if ($view == 'today') {
                    $serviceStmt = $conn->prepare("SELECT id, request, details, status, created_at, priority FROM customer_messages 
                                                WHERE uname = ? AND DATE(created_at) = ? 
                                                ORDER BY created_at DESC");
                    $serviceStmt->bind_param("ss", $uname, $todayDate);
                } else {
                    $serviceStmt = $conn->prepare("SELECT id, request, details, status, created_at, priority FROM customer_messages 
                                                WHERE uname = ? 
                                                ORDER BY created_at DESC");
                    $serviceStmt->bind_param("s", $uname);
                }
                
                $serviceStmt->execute();
                $serviceResult = $serviceStmt->get_result();
                
                if ($serviceResult->num_rows > 0) {
                    while ($row = $serviceResult->fetch_assoc()) {
                        $statusClass = '';
                        switch (strtolower($row['status'])) {
                            case 'pending':
                                $statusClass = 'status-pending';
                                break;
                            case 'completed':
                                $statusClass = 'status-completed';
                                break;
                            default:
                                $statusClass = 'status-processing';
                        }
                        
                        // Convert the date to a consistent format
                        $createdDate = DateTime::createFromFormat('Y-m-d H:i:s', $row['created_at']) ?: 
                                      new DateTime($row['created_at']);
                        
                        echo '<div class="status-card">';
                        echo '<div class="d-flex justify-content-between">';
                        echo '<h6>' . htmlspecialchars($row['request']) . '</h6>';
                        echo '<span class="badge ' . $statusClass . '">' . htmlspecialchars($row['status']) . '</span>';
                        echo '</div>';
                        echo '<p class="small mb-1">' . htmlspecialchars($row['details']) . '</p>';
                        echo '<p class="small text-muted mb-0">Requested on: ' . $createdDate->format('d-m-Y g:i A') . '</p>';
                        if ($row['priority']) {
                            echo '<p class="small text-danger mb-0">Priority: ' . htmlspecialchars($row['priority']) . '</p>';
                        }
                        echo '</div>';
                    }
                } else {
                    if ($view == 'today') {
                        echo '<div class="empty-state"><i class="fas fa-info-circle mb-2"></i><p>No service requests for today.</p></div>';
                    } else {
                        echo '<div class="empty-state"><i class="fas fa-info-circle mb-2"></i><p>No service requests found.</p></div>';
                    }
                }
                $serviceStmt->close();
                ?>
            </div>
            
            <!-- Food Orders Section -->
            <div class="mt-4">
                <h5 class="fw-semibold">Food Orders</h5>
                
                <?php
                // Get food orders from foodorders table
                if ($view == 'today') {
                    $foodStmt = $conn->prepare("SELECT id, code, food_item, quantity, totalprice, status, created_at 
                                               FROM foodorders 
                                               WHERE customer_name = ? AND DATE(created_at) = ? 
                                               ORDER BY created_at DESC");
                    $foodStmt->bind_param("ss", $uname, $todayDate);
                } else {
                    $foodStmt = $conn->prepare("SELECT id, code, food_item, quantity, totalprice, status, created_at 
                                               FROM foodorders 
                                               WHERE customer_name = ? 
                                               ORDER BY created_at DESC");
                    $foodStmt->bind_param("s", $uname);
                }
                
                $foodStmt->execute();
                $foodResult = $foodStmt->get_result();
                
                if ($foodResult->num_rows > 0) {
                    while ($row = $foodResult->fetch_assoc()) {
                        $statusClass = '';
                        switch (strtolower($row['status'])) {
                            case 'pending':
                                $statusClass = 'status-pending';
                                break;
                            case 'completed':
                                $statusClass = 'status-completed';
                                break;
                            default:
                                $statusClass = 'status-processing';
                        }
                        
                        // Convert the date to a consistent format
                        $createdDate = DateTime::createFromFormat('Y-m-d H:i:s', $row['created_at']) ?: 
                                      new DateTime($row['created_at']);
                        
                        echo '<div class="status-card">';
                        echo '<div class="d-flex justify-content-between">';
                        echo '<h6>Order #' . htmlspecialchars($row['code']) . '</h6>';
                        echo '<span class="badge ' . $statusClass . '">' . htmlspecialchars($row['status']) . '</span>';
                        echo '</div>';
                        echo '<p class="small mb-1">' . htmlspecialchars($row['food_item']) . ' x ' . htmlspecialchars($row['quantity']) . '</p>';
                        echo '<p class="small mb-1">Total: $' . htmlspecialchars($row['totalprice']) . '</p>';
                        echo '<p class="small text-muted mb-0">Ordered on: ' . $createdDate->format('d-m-Y g:i A') . '</p>';
                        echo '</div>';
                    }
                } else {
                    if ($view == 'today') {
                        echo '<div class="empty-state"><i class="fas fa-info-circle mb-2"></i><p>No food orders for today.</p></div>';
                    } else {
                        echo '<div class="empty-state"><i class="fas fa-info-circle mb-2"></i><p>No food orders found.</p></div>';
                    }
                }
                $foodStmt->close();
                ?>
            </div>
        </div>
    </div>
</body>
</html>
