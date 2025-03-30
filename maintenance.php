<?php

// Database connection setup using MySQLi
require_once('database.php');

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
if ($_SESSION['user_type'] !== 'Admin') {
    // Redirect to unauthorized access page or admin dashboard
    header("Location: unauthorized.php"); // You can create this page
    exit;
}
// Handle submission of maintenance requests
if (isset($_POST['submit_request'])) {
    $requestTitle = $_POST['request_title'];
    $description = $_POST['description'];
    $room_no = $_POST['room_no'];
    $priority = $_POST['priority'];

    // Automatically set the status to 'Pending'
    $status = 'Pending';

    $stmt = $conn->prepare("INSERT INTO maintenance_requests (request_title, description, room_no, priority, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $requestTitle, $description, $room_no, $priority, $status);
    $stmt->execute();

    $message = "Maintenance request submitted successfully!";
    $stmt->close();
}

// Handle updating of request status
if (isset($_POST['update_status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE maintenance_requests SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();

    $message = "Request status updated successfully!";
    $stmt->close();
}

// Fetch maintenance requests, ordered by priority (High > Medium > Low)
$sql = "
    SELECT * FROM maintenance_requests 
    ORDER BY 
        CASE priority
            WHEN 'High' THEN 1
            WHEN 'Medium' THEN 2
            WHEN 'Low' THEN 3
        END
";
$result = $conn->query($sql);
$requests = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/css/bootstrap.min.css">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <script src="https://kit.fontawesome.com/ae360af17e.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/maintenance.css"> 
    <link rel="icon" href="img/logo.webp">
</head>
<body>
    <?php include('index.php'); ?>

    <div class="container my-5 px-4 mt-4">
        <div class="p-4 nt-4 m-0 title-heading card">
            <h3 class="flex-grow-1">Maintenance Management</h3>
        </div>
        <!-- Buttons for Submitting and Updating Requests -->
        <div class="submit mt-4 mb- 4 me-2">
            <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#registerModal">
                 Submit Request
            </button>
        </div>
        <div class="row m-0 justify-content-center">
            <div class="card-body p-2 m-0">
                <div class="d-flex justify-content-between align-items-center mb-5">

                    <!-- Notification Modal (Replaces Success Message) -->
                    <div class="modal fade" id="emailResponseModal" tabindex="-1" aria-labelledby="emailResponseModalLabel" aria-hidden="true" data-bs-backdrop="static">
                        <div class="modal-dialog modal-dialog-centered p-0 w-25">
                            <div class="modal-content border-0 shadow-lg rounded-4">
                                <div class="d-flex justify-content-end border-0">
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center p-0">
                                    <i class="fas fa-check-circle text-success fa-3x mb-3" style="font-size: 3rem;"></i>
                                    <div id="emailResponseMessage"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal for Submitting Maintenance Request -->
                <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content shadow-lg rounded-4">
                            <div class="modal-header border-0">
                                <h5 class="modal-title fw-semibold" id="registerModalLabel">Submit Maintenance Request</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" style="font-size: 12px;" id="request_title" name="request_title" placeholder="Enter request title" required>
                                        <label for="request_title">Request Title</label>
                                    </div>
                                    <div class="form-floating mb-3">
                                        <textarea class="form-control" style="font-size: 12px;" id="description" name="description" rows="3" placeholder="Enter description" required></textarea>
                                         <label for="description">Description</label>
                                    </div>
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" style="font-size: 12px;" id="room_no" name="room_no" placeholder="Enter room number" required>
                                        <label for="room_no">Room Number</label>
                                    </div>
                                    <div class="form-floating mb-3">
                                        <select class="form-select" style="font-size: 12px;" id="priority" name="priority" required>
                                            <option value="Low">Low</option>
                                            <option value="Medium">Medium</option>
                                            <option value="High">High</option>
                                        </select>
                                        <label for="priority">Priority</label>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" name="submit_request" class="btn btn-success btn-sm px-4 py-2" style="font-size: 12px;">
                                            <i class="bx bx-send me-1"></i>Submit
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Display Maintenance Requests -->
                <div class="row gx-6 justify-content-start">
                    <!-- High Priority Requests -->
                    <div class="col-lg-12 col-md-12 col-sm-12">
                        <h6 class="mb-4 fw-semibold">High Priority</h6>
                        <div class="row g-3 justify-content-start">
                            <?php foreach ($requests as $request): ?>
                                <?php if ($request['priority'] === 'High'): ?>
                                    <div class="col-md-2">
                                        <div class="request-card card" onclick="openUpdateModal(<?php echo $request['id']; ?>, '<?php echo $request['status']; ?>', '<?php echo addslashes($request['request_title']); ?>', '<?php echo addslashes($request['description']); ?>', '<?php echo $request['room_no']; ?>', '<?php echo $request['priority']; ?>')">
                                            <div class="request-id">#<?php echo $request['id']; ?></div>
                                            <div class="request-title"><?php echo $request['request_title']; ?></div>
                                            <p class="text-muted"><?php echo $request['description']; ?></p>
                                            <div>Room # <?php echo $request['room_no']; ?></div>
                                            <div class="request-priority">
                                                <span style="font-weight: bold; color: red;">
                                                    <?php echo $request['priority']; ?>
                                                </span>
                                            </div>
                                            <div class="status">
                                                <span style="font-weight: bold; color: <?php 
                                                    switch($request['status']) {
                                                        case 'Pending':
                                                            echo 'gray';
                                                            break;
                                                        case 'Completed':
                                                            echo 'green';
                                                            break;
                                                        case 'In Progress':
                                                            echo 'orange';
                                                            break;
                                                    }
                                                ?>">
                                                <?php echo $request['status']; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="row g-3 justify-content-start">
                    <!-- Medium Priority Requests -->
                    <div class="col-lg-12 col-md-12 col-sm-12">
                        <h6 class="mb-4 fw-semibold">Medium Priority</h6>
                        <div class="row g-3 justify-content-start">
                            <?php foreach ($requests as $request): ?>
                                <?php if ($request['priority'] === 'Medium'): ?>
                                    <div class="col-md-2">
                                        <div class="request-card card" onclick="openUpdateModal(<?php echo $request['id']; ?>, '<?php echo $request['status']; ?>', '<?php echo addslashes($request['request_title']); ?>', '<?php echo addslashes($request['description']); ?>', '<?php echo $request['room_no']; ?>', '<?php echo $request['priority']; ?>')">
                                            <div class="request-id">#<?php echo $request['id']; ?></div>
                                            <div class="request-title"><?php echo $request['request_title']; ?></div>
                                            <p class="text-muted"><?php echo $request['description']; ?></p>
                                            <div>Room # <?php echo $request['room_no']; ?></div>
                                            <div class="request-priority">
                                                <span style="font-weight: bold; color: orange;">
                                                    <?php echo $request['priority']; ?>
                                                </span>
                                            </div>
                                            <div class="status">
                                                <span style="font-weight: bold; color: <?php 
                                                    switch($request['status']) {
                                                        case 'Pending':
                                                            echo 'gray';
                                                            break;
                                                        case 'Completed':
                                                            echo 'green';
                                                            break;
                                                        case 'In Progress':
                                                            echo 'orange';
                                                            break;
                                                    }
                                                ?>">
                                                <?php echo $request['status']; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="row g-3 justify-content-start">
                    <!-- Low Priority Requests -->
                    <div class="col-lg-12 col-md-12 col-sm-12">
                        <h6 class="mb-4 fw-semibold">Low Priority</h6>
                        <div class="row g-3 p-0 justify-content-start">
                            <?php foreach ($requests as $request): ?>
                                <?php if ($request['priority'] === 'Low'): ?>
                                    <div class="col-md-2">
                                        <div class="request-card card" onclick="openUpdateModal(<?php echo $request['id']; ?>, '<?php echo $request['status']; ?>', '<?php echo addslashes($request['request_title']); ?>', '<?php echo addslashes($request['description']); ?>', '<?php echo $request['room_no']; ?>', '<?php echo $request['priority']; ?>')">
                                            <div class="request-id">#<?php echo $request['id']; ?></div>
                                            <div class="request-title"><?php echo $request['request_title']; ?></div>
                                            <p class="text-muted"><?php echo $request['description']; ?></p>
                                            <div>Room # <?php echo $request['room_no']; ?></div>
                                            <div class="request-priority">
                                                <span style="font-weight: bold; color: green;">
                                                    <?php echo $request['priority']; ?>
                                                </span>
                                            </div>
                                            <div class="status">
                                                <span style="font-weight: bold; color: <?php 
                                                    switch($request['status']) {
                                                        case 'Pending':
                                                            echo 'gray';
                                                            break;
                                                        case 'Completed':
                                                            echo 'green';
                                                            break;
                                                        case 'In Progress':
                                                            echo 'orange';
                                                            break;
                                                    }
                                                ?>">
                                                <?php echo $request['status']; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <!-- Repeat similar structure for 'Medium Priority' and 'Low Priority' -->
                </div>
            </div>
        </div>

        <!-- Modal for Updating Request Status -->
        <div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg rounded-4">
                    <div class="modal-header border-0">
                        <h5 class="modal-title fw-semibold" id="updateModalLabel">Update Maintenance Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <form action="" method="POST" >
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control rounded-2" style="font-size: 12px;" id="id" name="id" placeholder="Enter request ID" required>
                                <label for="id">Request ID</label>
                            </div>
                            <div class="form-floating mb-3">
                                <select class="form-select rounded-3" style="font-size: 12px;" id="status" name="status" required>
                                    <option value="Pending">Pending</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                </select>
                                <label for="status">Status</label>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" name="update_status" class="btn btn-success btn-sm px-4 py-2" style="font-size: 12px;">
                                    <i class="bx bx-send me-1"></i>Submit
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
    </div>

    <!-- Include Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Show notification modal if there's a message
        <?php if (isset($message)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Set the message text
            document.getElementById('emailResponseMessage').innerHTML = "<?php echo $message; ?>";
            
            // Create and show the modal without dimming background
            var notificationModal = new bootstrap.Modal(document.getElementById('emailResponseModal'), {
                backdrop: false
            });
            notificationModal.show();
            
            // Hide the modal after 2 seconds
            setTimeout(function() {
                notificationModal.hide();
            }, 2000);
        });
        <?php endif; ?>

        function openUpdateModal(id, status, requestTitle, description, roomNo, priority) {
            document.getElementById('id').value = id;
            document.getElementById('status').value = status;

            var updateModal = new bootstrap.Modal(document.getElementById('updateModal'));
            updateModal.show(); // Show modal when data is set
        }
    </script>
</body>
</html>
