<?php
require_once'../database.php';
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php"); // Redirect to login if not logged in
    exit;
}

if ($_SESSION['user_type'] !== 'Employee') {
    // Redirect to unauthorized access page or admin dashboard
    header("Location: ../unauthorized.php"); // You can create this page
    exit;
}

// API endpoint for room availability
$apiUrl = "https://core2.paradisehoteltomasmorato.com/integ/avroom.php?api_key=b5fb3418cb1a7e88903d64e55373c48e48f9c53aabdcba0357f0107233d9dbda";

// Initialize variables
$rooms = [];
$errorMessage = "";

// Fetch data from API
function fetchRoomData($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        return ['error' => 'API Error: ' . curl_error($ch)];
    }
    
    curl_close($ch);
    
    // Decode JSON response
    $responseData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'Invalid JSON response: ' . json_last_error_msg()];
    }
    
    return $responseData;
}

// Get the room data
$apiResponse = fetchRoomData($apiUrl);

// Check if there was an error
if (isset($apiResponse['error'])) {
    $errorMessage = $apiResponse['error'];
} else {
    // Process the room data
    if (isset($apiResponse['data']) && is_array($apiResponse['data'])) {
        $rooms = $apiResponse['data'];
    } else {
        $errorMessage = "No room data available";
    }
}

// Filter rooms by status if requested
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
if (!empty($statusFilter)) {
    $rooms = array_filter($rooms, function($room) use ($statusFilter) {
        return $room['status'] === $statusFilter;
    });
}

// Group rooms by floor based on room number prefix
$floors = [];
foreach ($rooms as $room) {
    // Extract floor information from room_no
    if (preg_match('/^([A-Z]+[0-9]?)\-/', $room['room_no'], $matches)) {
        $floorPrefix = $matches[1];
        
        // Map the prefix to floor names
        if (in_array($floorPrefix, ['N', 'E1', 'D1'])) {
            $floorName = 'Ground Floor';
            $sortOrder = 1;
        } elseif (in_array($floorPrefix, ['F', 'F1', 'VV1', 'S1'])) {
            $floorName = 'Second Floor';
            $sortOrder = 2;
        } elseif (in_array($floorPrefix, ['V', 'V1', 'P1'])) {
            $floorName = 'Third Floor';
            $sortOrder = 3;
        } elseif (in_array($floorPrefix, ['E2', 'D2'])) {
            $floorName = 'Ground Floor (24 hrs)';
            $sortOrder = 4;
        } elseif (in_array($floorPrefix, ['F2', 'VV2', 'S2'])) {
            $floorName = 'Second Floor (24 hrs)';
            $sortOrder = 5;
        } elseif (in_array($floorPrefix, ['V2', 'P2'])) {
            $floorName = 'Third Floor (24 hrs)';
            $sortOrder = 6;
        } else {
            $floorName = 'Other Rooms';
            $sortOrder = 7;
        }
        
        // Add the floor if it doesn't exist yet
        if (!isset($floors[$sortOrder])) {
            $floors[$sortOrder] = [
                'name' => $floorName,
                'rooms' => []
            ];
        }
        
        // Add the room to the floor
        $floors[$sortOrder]['rooms'][] = $room;
    } else {
        // If the room doesn't match the pattern, add it to "Other Rooms"
        if (!isset($floors[7])) {
            $floors[7] = [
                'name' => 'Other Rooms',
                'rooms' => []
            ];
        }
        $floors[7]['rooms'][] = $room;
    }
}

// Sort floors by key to maintain order
ksort($floors);

// Count rooms by status
$availableCount = 0;
$unavailableCount = 0;
$uncleanedCount = 0; // New counter for uncleaned rooms

foreach ($rooms as $room) {
    if ($room['status'] === 'Available') {
        $availableCount++;
        
        // Determine if the room is uncleaned (based on room number pattern)
        // This is a placeholder logic - adjust according to your actual business rules
        if (strpos($room['room_no'], 'E1') !== false || strpos($room['room_no'], 'F1') !== false) {
            // We no longer track cleaned rooms separately
        } else {
            $uncleanedCount++;
        }
    } else {
        $unavailableCount++;
    }
}

// Calculate percentages for visualization
$totalRooms = count($rooms);
$availablePercentage = $totalRooms > 0 ? round(($availableCount / $totalRooms) * 100) : 0;
$uncleanedPercentage = $availableCount > 0 ? round(($uncleanedCount / $availableCount) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Availability</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --available-color: #198754;
            --not-available-color: #dc3545;
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --light-bg: #f8f9fa;
            --card-shadow: 0 5px 15px rgba(0,0,0,0.08);
            --hover-shadow: 0 10px 25px rgba(0,0,0,0.15);
            --border-radius: 15px;
            --transition-speed: 0.3s;
        }
        
        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            padding-bottom: 50px;
        }
        
        .page-header {
            background: linear-gradient(45deg, #0d6efd, #198754);
            color: white;
            padding: 30px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
        }
        
        .page-header::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 100%;
            background: rgba(255,255,255,0.1);
            transform: skewX(-30deg);
        }
        
        /* Room card redesign - EVEN SMALLER SIZE */
        .room-card {
            height: 110px; /* Further reduced height */
            margin-bottom: 10px; /* Reduced margin */
            border-radius: 12px; /* Smaller border radius */
            box-shadow: 0 3px 10px rgba(0,0,0,0.05); /* Lighter shadow */
            transition: transform var(--transition-speed), box-shadow var(--transition-speed);
            border: none;
            overflow: hidden;
            position: relative;
        }
        
        .room-card:hover {
            transform: translateY(-5px); /* Smaller hover effect */
            box-shadow: var(--hover-shadow);
        }
        
        .room-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px; /* Thinner top border */
            background-color: var(--secondary-color);
        }
        
        .card.available::before {
            background-color: var(--available-color);
        }
        
        .card.not-available::before {
            background-color: var(--not-available-color);
        }
        
        .room-content {
            padding: 10px; /* Reduced padding */
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .room-number {
            font-size: 1.2rem; /* Smaller font */
            font-weight: 700;
            margin-bottom: 0; /* Remove bottom margin */
            color: #333;
        }
        
        .room-id {
            font-size: 0.7rem; /* Smaller font */
            color: var (--secondary-color);
            margin-bottom: 5px; /* Reduced margin */
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 3px; /* Smaller gap */
            margin-top: auto;
        }
        
        .status-badge {
            font-size: 0.7rem; /* Smaller font */
            padding: 3px 8px; /* Reduced padding */
            border-radius: 30px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        /* Statistics cards - simplified */
        .stats-area {
            margin-bottom: 20px;
        }
        
        .stats-card {
            padding: 10px; /* Further reduced padding */
            border-radius: var(--border-radius);
            margin-bottom: 10px;
            text-align: center;
            box-shadow: var(--card-shadow);
            border: none;
            transition: transform var(--transition-speed);
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-card h5 {
            font-weight: 600;
            margin-bottom: 0; /* Reduced margin */
            font-size: 0.85rem; /* Smaller font */
        }
        
        .stats-card h3 {
            font-size: 2rem; /* Larger font size for emphasis */
            font-weight: 700;
            margin-bottom: 0; /* Remove bottom margin */
        }
        
        .stats-icon {
            font-size: 1.8rem;
            margin-bottom: 5px;
            display: block;
        }
        
        .card-uncleaned {
            background-color: #fff3cd;
            border-left: 5px solid #ffc107;
        }
        
        /* Make stats cards responsive while maintaining 3 in a row */
        @media (max-width: 768px) {
            .stats-card {
                padding: 8px; /* Even smaller padding on mobile */
            }
            
            .stats-card h3 {
                font-size: 1.8rem;
            }
            
            .stats-card h5 {
                font-size: 0.75rem;
            }
            
            .stats-icon {
                font-size: 1.5rem;
                margin-bottom: 3px;
            }
        }
        
        /* Floor headings */
        .floor-heading {
            background: white;
            margin-top: 40px;
            margin-bottom: 25px;
            padding: 15px 20px;
            border-radius: var(--border-radius);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--card-shadow);
            border-left: 5px solid var(--primary-color);
        }
        
        .floor-heading h5 {
            margin: 0;
            font-weight: 600;
            color: #333;
        }
        
        /* Search box */
        .search-container {
            position: relative;
            margin-bottom: 30px;
        }
        
        .search-container .form-control {
            padding: 12px 20px 12px 50px;
            font-size: 1rem;
            border-radius: 50px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e0e0e0;
            transition: all var(--transition-speed);
        }
        
        .search-container .form-control:focus {
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.15);
            border-color: var(--primary-color);
        }
        
        .search-icon {
            position: absolute;
            left: 18px;
            top: 12px;
            color: #aaa;
            font-size: 1.1rem;
            z-index: 10;
        }
        
        /* Filter button and modal styles */
        .filter-btn-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 999;
        }
        
        .floating-filter-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border: none;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .floating-filter-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            background-color: #0b5ed7;
        }
        
        .filter-modal .modal-content {
            border-radius: 20px;
            overflow: hidden;
        }
        
        .filter-option {
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 12px;
            transition: all 0.2s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .filter-option:hover {
            transform: translateY(-3px);
        }
        
        .filter-option.active {
            border-color: var(--primary-color);
        }
        
        .filter-icon {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        
        /* Make design responsive - Adjust for 3 cards per row on mobile */
        @media (max-width: 992px) {
            .room-card {
                height: 100px; /* Even smaller on tablets */
            }
            
            .room-number {
                font-size: 1.1rem;
            }
            
            .status-badge {
                font-size: 0.65rem;
                padding: 2px 6px;
            }
        }
        
        @media (max-width: 576px) {
            .room-card {
                height: 95px; /* Smallest on mobile phones */
            }
            
            .room-number {
                font-size: 1rem;
            }
            
            .room-id {
                font-size: 0.65rem;
                margin-bottom: 3px;
            }
            
            .stats-card h3 {
                font-size: 1.6rem;
            }
            
            /* Ensure 3 cards per row on mobile */
            .room-item {
                width: 33.333% !important; /* Force 3 cards per row */
                padding-left: 3px;
                padding-right: 3px;
            }
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 5px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }
        
        /* Clean button styles */
        .clean-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #20c997;
            border: none;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 10;
            transition: all 0.2s;
        }
        
        .clean-btn:hover {
            transform: scale(1.1);
            background-color: #15a87b;
        }
        
        .clean-btn i {
            font-size: 0.8rem;
        }
        
        /* Success notification */
        #cleaningSuccessAlert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s ease;
        }
        
        #cleaningSuccessAlert.show {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container">
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $errorMessage; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Success notification for cleaning -->
    <div id="cleaningSuccessAlert" class="alert alert-success alert-dismissible fade">
        <span id="cleaningSuccessMessage"></span>
        <button type="button" class="btn-close" onclick="dismissCleaningAlert()"></button>
    </div>
    
    <!-- Room Statistics Cards - Simplified to show only numbers and icons -->
    <div class="row stats-area g-2">
        <div class="col-4">
            <div class="stats-card bg-light">
                <div class="text-center">
                    <i class="fas fa-check-circle text-success stats-icon"></i>
                    <h3 class="text-success"><?php echo $availableCount; ?></h3>
                    <h5>Available</h5>
                </div>
            </div>
        </div>
        
        <div class="col-4">
            <div class="stats-card bg-light">
                <div class="text-center">
                    <i class="fas fa-times-circle text-danger stats-icon"></i>
                    <h3 class="text-danger"><?php echo $unavailableCount; ?></h3>
                    <h5>Unavailable</h5>
                </div>
            </div>
        </div>
        
        <!-- Uncleaned Rooms -->
        <div class="col-4">
            <div class="stats-card bg-light">
                <div class="text-center">
                    <i class="fas fa-broom text-warning stats-icon"></i>
                    <h3 class="text-warning"><?php echo $uncleanedCount; ?></h3>
                    <h5>To Clean</h5>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Search Box -->
    <div class="search-container">
        <i class="fas fa-search search-icon"></i>
        <input type="text" class="form-control" id="searchInput" placeholder="Search by room number...">
    </div>
    
    <!-- Current filter indicator -->
    <div class="filter-indicator mb-3">
        <span class="badge bg-primary p-2">
            <i class="fas fa-filter me-1"></i>
            Status: 
            <?php
                if (empty($statusFilter)) {
                    echo "All Rooms";
                } else {
                    echo $statusFilter;
                }
            ?>
        </span>
    </div>
    
    <?php if (empty($floors)): ?>
        <div class="alert alert-info py-4 text-center">
            <i class="fas fa-info-circle fa-2x mb-3"></i>
            <h5>No rooms found</h5>
            <p class="mb-0">No rooms match your search criteria.</p>
        </div>
    <?php else: ?>
        <?php foreach ($floors as $floorData): ?>
            <div class="floor-section">
                <div class="floor-heading">
                    <h5><i class="fas fa-building me-2"></i> <?php echo $floorData['name']; ?></h5>
                    <span class="badge bg-primary"><?php echo count($floorData['rooms']); ?> Rooms</span>
                </div>
                <div class="row room-container g-1"> <!-- Added g-1 for smaller gutters -->
                    <?php foreach ($floorData['rooms'] as $room): ?>
                        <div class="col-4 col-md-4 col-lg-4 mb-2 room-item" data-room-number="<?php echo $room['room_no']; ?>">
                            <div class="card room-card <?php echo $room['status'] === 'Available' ? 'available' : 'not-available'; ?>"
                                data-bs-toggle="modal"
                                data-bs-target="#roomModal"
                                data-room-id="<?php echo $room['room_id']; ?>"
                                data-room-number="<?php echo $room['room_no']; ?>"
                                data-room-status="<?php echo $room['status']; ?>"
                            >
                                <div class="room-content">
                                    <div>
                                        <h4 class="room-number"><?php echo $room['room_no']; ?></h4>
                                        <div class="room-id">ID: <?php echo $room['room_id']; ?></div>
                                    </div>
                                    <div class="status-indicator">
                                        <?php if ($room['status'] === 'Available'): ?>
                                            <span class="status-badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i> Available
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge bg-danger">
                                                <i class="fas fa-times-circle me-1"></i> Not Available
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- No results message (initially hidden) -->
    <div id="noResultsMessage" class="alert alert-info text-center py-4" style="display: none;">
        <i class="fas fa-search fa-2x mb-3"></i>
        <h5>No matching rooms</h5>
        <p>Try adjusting your search criteria.</p>
    </div>
    
    <!-- Floating Filter Button -->
    <div class="filter-btn-container">
        <button class="floating-filter-btn" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
            <i class="fas fa-filter"></i>
        </button>
    </div>
</div>

<!-- Filter Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
    <div class="offcanvas-header">
        <h6 class="offcanvas-title fw-semibold" id="filterOffcanvasLabel">Filter Rooms</h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form method="GET" id="filterForm">
            <div class="filter-section">
                <h5>Room Status</h5>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="status" id="statusAll" value="" <?php echo empty($statusFilter) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="statusAll">
                        <i class="fas fa-th-list text-primary me-2"></i>All Rooms
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="status" id="statusAvailable" value="Available" <?php echo $statusFilter === 'Available' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="statusAvailable">
                        <i class="fas fa-check-circle text-success me-2"></i>Available Rooms
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="status" id="statusUnavailable" value="Not-Available" <?php echo $statusFilter === 'Not-Available' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="statusUnavailable">
                        <i class="fas fa-times-circle text-danger me-2"></i>Not Available Rooms
                    </label>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary w-100 mb-2">Apply Filter</button>
                <a href="rooms.php" class="btn btn-outline-secondary w-100">Reset Filter</a>
            </div>
        </form>
    </div>
</div>

<!-- Modal for Room Status Update (Redesigned) -->
<div class="modal fade" id="roomModal" tabindex="-1" aria-labelledby="roomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roomModalLabel">
                    <i class="fas fa-door-open me-2"></i>Room Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="room-detail-card">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong><i class="fas fa-hashtag me-2"></i>Room ID:</strong> <span id="modal-room-id"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong><i class="fas fa-door-closed me-2"></i>Room Number:</strong> <span id="modal-room-number"></span></p>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">
                            <p><strong><i class="fas fa-info-circle me-2"></i>Current Status:</strong> <span id="modal-room-status"></span></p>
                        </div>
                    </div>
                </div>
                
                <form id="roomStatusForm" action="update_room_status.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="room_id" name="room_id">
                    <input type="hidden" id="room_number" name="room_number">
                    
                    <div class="mb-4">
                        <label for="room_status" class="form-label fw-bold"><i class="fas fa-exchange-alt me-2"></i>Update Status</label>
                        <select class="form-select form-select-lg" id="room_status" name="room_status" required onchange="handleStatusChange()">
                            <option value="Available">Available</option>
                            <option value="Not-Available">Not Available</option>
                            <option value="Clean">Clean Room</option>
                        </select>
                        <div class="form-text" id="statusHelpText">
                            <i class="fas fa-info-circle me-1"></i>
                            Select "Clean Room" to mark the room as cleaned and make it available.
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="room_notes" class="form-label fw-bold"><i class="fas fa-sticky-note me-2"></i>Notes</label>
                        <textarea class="form-control" id="room_notes" name="room_notes" rows="3" placeholder="Enter details about the room condition..."></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="room_image" class="form-label fw-bold"><i class="fas fa-camera me-2"></i>Upload Proof (Image)</label>
                        <input type="file" class="form-control" id="room_image" name="room_image" accept="image/*">
                        <div class="form-text">Upload an image showing the current state of the room</div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg btn-update" id="updateButton">
                            <i class="fas fa-save me-2"></i>Update Room Status
                        </button>
                    </div>
                </form>
                
                <!-- Loading spinner (hidden by default) -->
                <div id="updateSpinner" class="text-center my-3" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Updating room status...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // JavaScript to populate modal with room data when a room card is clicked
    const roomModal = document.getElementById('roomModal');
    roomModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const roomId = button.getAttribute('data-room-id');
        const roomNumber = button.getAttribute('data-room-number');
        const roomStatus = button.getAttribute('data-room-status');

        // Update modal content
        roomModal.querySelector('#modal-room-id').textContent = roomId;
        roomModal.querySelector('#modal-room-number').textContent = roomNumber;
        
        const statusElement = roomModal.querySelector('#modal-room-status');
        statusElement.textContent = roomStatus;
        
        if (roomStatus === 'Available') {
            statusElement.className = 'badge bg-success';
        } else {
            statusElement.className = 'badge bg-danger';
        }
        
        // Update form values
        roomModal.querySelector('#room_id').value = roomId;
        roomModal.querySelector('#room_number').value = roomNumber;
        roomModal.querySelector('#room_status').value = roomStatus;
        
        // Set the modal title to include the room number
        roomModal.querySelector('.modal-title').innerHTML = `<i class="fas fa-door-open me-2"></i>Room ${roomNumber}`;
        
        // Reset form
        document.getElementById('roomStatusForm').reset();
        document.getElementById('updateSpinner').style.display = 'none';
        
        // Update form values after reset
        roomModal.querySelector('#room_id').value = roomId;
        roomModal.querySelector('#room_number').value = roomNumber;
        roomModal.querySelector('#room_status').value = roomStatus;
        
        // Initial status text update
        handleStatusChange();
    });
    
    // Function to handle status change
    function handleStatusChange() {
        const statusSelect = document.getElementById('room_status');
        const statusHelpText = document.getElementById('statusHelpText');
        const updateButton = document.getElementById('updateButton');
        
        if (statusSelect.value === 'Clean') {
            statusHelpText.innerHTML = '<i class="fas fa-broom me-1"></i> This will mark the room as cleaned and set it to Available.';
            updateButton.className = 'btn btn-success btn-lg btn-update';
            updateButton.innerHTML = '<i class="fas fa-broom me-2"></i>Clean and Update Room';
        } else {
            statusHelpText.innerHTML = '<i class="fas fa-info-circle me-1"></i> Update the room status as needed.';
            updateButton.className = 'btn btn-primary btn-lg btn-update';
            updateButton.innerHTML = '<i class="fas fa-save me-2"></i>Update Room Status';
        }
    }
    
    // Handle room status form submission
    document.getElementById('roomStatusForm').addEventListener('submit', function(e) {
        const status = document.getElementById('room_status').value;
        
        // If cleaning a room, handle it with AJAX to use the update_clean_status.php endpoint
        if (status === 'Clean') {
            e.preventDefault();
            
            // Show spinner
            document.getElementById('updateSpinner').style.display = 'block';
            
            // Get form data
            const formData = new FormData(this);
            
            // Send to the update_clean_status.php endpoint
            fetch('update_clean_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Hide spinner
                document.getElementById('updateSpinner').style.display = 'none';
                
                if (data.success) {
                    // Close modal
                    bootstrap.Modal.getInstance(roomModal).hide();
                    
                    // Show success message
                    showCleaningSuccessMessage(data.message);
                    
                    // Refresh the page after a short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                document.getElementById('updateSpinner').style.display = 'none';
                console.error('Error:', error);
                alert('An error occurred while processing your request. Please try again.');
            });
        }
        // Otherwise, let the form submit normally to update_room_status.php
    });
    
    // Enhanced search functionality with visual feedback
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const roomItems = document.querySelectorAll('.room-item');
        const noResultsMessage = document.getElementById('noResultsMessage');
        
        let foundRooms = false;
        
        roomItems.forEach(item => {
            const roomNumber = item.getAttribute('data-room-number').toLowerCase();
            if (roomNumber.includes(searchValue)) {
                item.style.display = '';
                foundRooms = true;
            } else {
                item.style.display = 'none';
            }
        });
        
        // Check if we need to show/hide empty floor sections
        document.querySelectorAll('.floor-section').forEach(section => {
            const visibleRooms = section.querySelectorAll('.room-item[style=""]').length;
            if (visibleRooms > 0) {
                section.style.display = '';
            } else {
                section.style.display = 'none';
            }
        });
        
        // Show/hide no results message
        if (!foundRooms && searchValue !== '') {
            noResultsMessage.style.display = 'block';
        } else {
            noResultsMessage.style.display = 'none';
        }
    });
    
    // Visual feedback when search is cleared
    document.getElementById('searchInput').addEventListener('search', function() {
        if (this.value === '') {
            document.querySelectorAll('.room-item').forEach(item => {
                item.style.display = '';
            });
            document.querySelectorAll('.floor-section').forEach(section => {
                section.style.display = '';
            });
            document.getElementById('noResultsMessage').style.display = 'none';
        }
    });
    
    // Auto-refresh data every 5 minutes with visual feedback
    let refreshTimer = 300; // 5 minutes in seconds
    let refreshTimerDisplay = document.createElement('div');
    refreshTimerDisplay.className = 'position-fixed bottom-0 start-0 m-3 small text-muted';
    refreshTimerDisplay.style.zIndex = '1000';
    document.body.appendChild(refreshTimerDisplay);
    
    function updateRefreshTimer() {
        const minutes = Math.floor(refreshTimer / 60);
        const seconds = refreshTimer % 60;
        refreshTimerDisplay.textContent = `Auto-refresh in: ${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
    }
    
    updateRefreshTimer();
    
    setInterval(function() {
        refreshTimer -= 1;
        updateRefreshTimer();
        if (refreshTimer <= 0) {
            window.location.reload();
        }
    }, 1000); // Update every second
    
    function showCleaningSuccessMessage(message) {
        const alert = document.getElementById('cleaningSuccessAlert');
        const messageElement = document.getElementById('cleaningSuccessMessage');
        
        messageElement.textContent = message;
        alert.classList.add('show');
        
        // Auto-dismiss after 3 seconds
        setTimeout(() => {
            dismissCleaningAlert();
        }, 3000);
    }
    
    function dismissCleaningAlert() {
        const alert = document.getElementById('cleaningSuccessAlert');
        alert.classList.remove('show');
    }
</script>

</body>
</html>
