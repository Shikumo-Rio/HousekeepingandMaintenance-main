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

// Simulating room status data. You would fetch this from your database in a real-world application.
$rooms = [];
for ($i = 1; $i <= 50; $i++) {
    $status = rand(1, 3); // Randomly assign status: 1 = Cleaned, 2 = Occupied, 3 = Not Cleaned
    $rooms[] = [
        'room_number' => $i,
        'status' => $status,
    ];
}

// Group rooms by floors (10 rooms per floor)
$floors = [];
foreach ($rooms as $room) {
    $floor = ceil($room['room_number'] / 10); // Group rooms into floors
    $floors[$floor][] = $room;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Styling for room card */
        .room-card {
            height: 200px; /* Fixed height for each card */
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 1rem;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            padding: 10px;
            flex-direction: column;
            /* Ensures the card stays the same size across devices */
            overflow: hidden; /* Prevent overflow if text is too long */
        }

        /* Ensure the content is centered in the card */
        .room-status {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            flex-direction: column;
            text-overflow: ellipsis; /* Truncate the text if it's too long */
            overflow: hidden;
            white-space: nowrap;
        }

        .room-status div {
            font-size: 0.9rem;
        }

        /* Responsive grid system for mobile */
        @media (max-width: 576px) {
            .col-4 {
                flex: 0 0 100%; /* Make cards take full width on mobile */
                max-width: 100%; /* Make sure it doesn't exceed the width */
            }
        }

        /* Responsive grid for larger screens */
        @media (min-width: 576px) {
            .col-4 {
                flex: 0 0 33.33%; /* 3 cards per row for larger devices */
                max-width: 33.33%;
            }
        }

        /* Modal Content */
        .modal-body {
            max-width: 400px;
        }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>
<!-- Room Status Section -->
<div class="container">
    <h1 class="text-center mt-3">Room Status</h1>
    
    <?php foreach ($floors as $floorNumber => $floorRooms): ?>
        <h3 class="mt-5"><?php 
            if ($floorNumber == 1) {
                echo 'Ground Floor';
            } elseif ($floorNumber == 2) {
                echo 'Upper Ground Floor';
            }
            elseif ($floorNumber == 3) {
                echo 'Second Floor';
            }elseif ($floorNumber == 4) {
                echo 'Third Floor';
            } elseif ($floorNumber == 5) {
                echo 'Fourth Floor';
            }
             else {
                echo 'Floor ' . $floorNumber;
            }
        ?></h3>
        <div class="row mt-4">
            <?php foreach ($floorRooms as $room): ?>
                <div class="col-4 col-sm-4 col-md-4 col-lg-4">
                    <!-- Make the card clickable and open the modal -->
                    <div class="card room-card 
                        <?php 
                            if ($room['status'] == 1) echo 'bg-success';
                            elseif ($room['status'] == 2) echo 'bg-danger';
                            else echo 'bg-warning';
                        ?>"
                        data-bs-toggle="modal"
                        data-bs-target="#roomModal"
                        data-room-number="<?php echo $room['room_number']; ?>"
                        data-room-status="<?php echo $room['status']; ?>"
                    >
                        <div class="room-status">
                            <div>
                                Room <?php echo $room['room_number']; ?><br>
                                <?php 
                                    if ($room['status'] == 1) echo 'Cleaned';
                                    elseif ($room['status'] == 2) echo 'Occupied';
                                    else echo 'Not Cleaned';
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

</div>

<!-- Modal for Room Status Update -->
<div class="modal fade" id="roomModal" tabindex="-1" aria-labelledby="roomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="roomModalLabel">Update Room Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="update_room_status.php" method="POST" enctype="multipart/form-data">
                    <div class="form-floating mb-2">
                        <input type="text" class="form-control" id="room_number" name="room_number" placeholder="Room Number" readonly>
                        <label for="room_number" class="form-label">Room Number</label>
                    </div>
                    <div class="form-floating mb-2">
                        <select class="form-select" id="room_status" name="room_status" placeholder="Select room status" required>
                            <option value="1">Cleaned</option>
                            <option value="2">Occupied</option>
                            <option value="3">Not Cleaned</option>
                        </select>
                        <label for="room_status" class="form-label">Room Status</label>
                    </div>
                    <div class="mb-2">
                        <label for="room_image" class="form-label">Upload Proof (Image)</label>
                        <input type="file" class="form-control" id="room_image" name="room_image" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // JavaScript to populate modal with room number and status when a room card is clicked
    const roomModal = document.getElementById('roomModal');
    roomModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const roomNumber = button.getAttribute('data-room-number');
        const roomStatus = button.getAttribute('data-room-status');

        const modalRoomNumber = roomModal.querySelector('#room_number');
        const modalRoomStatus = roomModal.querySelector('#room_status');

        modalRoomNumber.value = roomNumber;
        modalRoomStatus.value = roomStatus;
    });
</script>

</body>
</html>
