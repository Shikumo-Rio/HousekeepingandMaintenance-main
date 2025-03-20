<?php
require_once '../database.php';

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php"); // Redirect to login if not logged in
    exit;
}

if ($_SESSION['user_type'] !== 'Employee') {
    // Redirect to unauthorized access page or admin dashboard
    header("Location: ../unauthorized.php"); // You can create this page
    exit;
}

function getAssignedTask($housekeeperId) {
    global $conn;
    $sql = "SELECT * FROM assigntasks WHERE emp_id = ? AND status = 'working'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $housekeeperId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

$message = ""; // Initialize success message variable

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_request'])) {
    $requestTitle = htmlspecialchars(trim($_POST['request_title']));
    $description = htmlspecialchars(trim($_POST['description']));
    $room_no = htmlspecialchars(trim($_POST['room_no']));
    $priority = htmlspecialchars(trim($_POST['priority']));
    $status = 'Pending'; // Default status

    // Prepare SQL statement
    $stmt = $conn->prepare("INSERT INTO maintenance_requests (request_title, description, room_no, priority, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $requestTitle, $description, $room_no, $priority, $status);

    if ($stmt->execute()) {
        $message = "Maintenance request submitted successfully!";
        $status = "success";
    } else {
        $message = "Error submitting request: " . $conn->error;
        $status = "error";
    }

    $stmt->close();

    // Display Bootstrap modal with message
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('messageBody').innerText = '" . addslashes($message) . "';
            var messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
            messageModal.show();

            // Auto-close after 3 seconds
            setTimeout(() => {
                messageModal.hide();
                " . ($status === "success" ? "window.location.href = 'index.php';" : "") . "
            }, 3000);
        });
    </script>";
}

$message = ""; // Message for success or error feedback

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_requestlostnfound'])) {
    $found_by = isset($_POST['found_by']) ? htmlspecialchars(trim($_POST['found_by'])) : '';
    $type = isset($_POST['type']) ? htmlspecialchars(trim($_POST['type'])) : '';
    $room = isset($_POST['room']) ? htmlspecialchars(trim($_POST['room'])) : '';
    $date = isset($_POST['date']) ? htmlspecialchars(trim($_POST['date'])) : '';
    $item = isset($_POST['item']) ? htmlspecialchars(trim($_POST['item'])) : '';
    $description = isset($_POST['description']) ? htmlspecialchars(trim($_POST['description'])) : '';
    $picture_path = ''; // Default empty path if no file uploaded
    $message = '';
    $status = 'error'; // Default error status

    // Handle file upload
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "../uploads/";
        $file_name = time() . "_" . basename($_FILES["picture"]["name"]); // Unique filename
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $uploadOk = 1;

        // Validate image file
        $check = getimagesize($_FILES["picture"]["tmp_name"]);
        if ($check === false) {
            $message = "File is not an image.";
            $uploadOk = 0;
        }

        // Check if file already exists
        if (file_exists($target_file)) {
            $message = "File already exists.";
            $uploadOk = 0;
        }

        // Check file size (limit: 5MB)
        if ($_FILES["picture"]["size"] > 5000000) {
            $message = "File size is too large.";
            $uploadOk = 0;
        }

        // Allow only JPG, JPEG, and PNG formats
        if (!in_array($imageFileType, ["jpg", "jpeg", "png"])) {
            $message = "Only JPG, JPEG, and PNG files are allowed.";
            $uploadOk = 0;
        }

        // Move file if no errors
        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES["picture"]["tmp_name"], $target_file)) {
                $picture_path = $target_file;
            } else {
                $message = "Error uploading file.";
                $uploadOk = 0;
            }
        }
    }

    // Proceed with database insertion only if no errors
    if ($uploadOk == 1) {
        if (!empty($found_by) && !empty($room) && !empty($date) && !empty($item) && !empty($description) && !empty($type)) {
            $status = 'pending';

            // Prepare the SQL statement
            $stmt = $conn->prepare("INSERT INTO lost_and_found (found_by, type, room, date, item, description, status, picture) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $found_by, $type, $room, $date, $item, $description, $status, $picture_path);

            if ($stmt->execute()) {
                $message = "Ticket created successfully!";
                $status = "success";
            } else {
                $message = "Database Error: " . $stmt->error;
            }
        } else {
            $message = "Please fill out all required fields.";
        }
    }

    // Display Bootstrap modal with the message
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('messageBody').innerText = '" . addslashes($message) . "';
            var messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
            messageModal.show();

            // Auto-close after 3 seconds
            setTimeout(() => {
                messageModal.hide();
                " . ($status === "success" ? "window.location.href = 'index.php';" : "") . "
            }, 3000);
        });
    </script>";
}

// Fetch inventory items from the API - replacing with direct database query to get specific columns
function getInventoryItems() {
    global $conn;
    $items = [];
    
    $sql = "SELECT id, inventory_id, category, item_name, quantity FROM inventory";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }
    
    return $items;
}

$inventory_items = getInventoryItems();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Housekeepers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="../img/logo.webp">

</head>
<body>
    <!-- Message Modal -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="messageModalLabel">Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center" id="messageBody">
                <!-- Message will be inserted here dynamically -->
            </div>
        </div>
    </div>
</div>

    <?php include 'nav.php'; ?>
    <div class="main-container">
        <div class="content container-fluid" id="content">
            <!-- Lost and Found & Maintenance Buttons -->
            <div class="row justify-content-center p-0 me-4 mx-4 mt-4">
                <div class="col-6 col-md-4 mb-3 mt-0 d-flex justify-content-center">
                    <button class="btn mb-3 mx-2" data-bs-toggle="modal" data-bs-target="#lostFoundModal">
                        <i class="fa-solid fas fa-box fa-2x"></i>Lost and Found
                    </button>
                    <button class="btn mb-3 mx-2" data-bs-toggle="modal" data-bs-target="#maintenanceModal">
                        <i class="fa-solid fas fa-tools fa-2x"></i>Maintenance
                    </button>
                    <button onclick="window.location.href='inventory.php'" class="btn mb-4 mx-2">
                        <i class="fa-solid fas fa-warehouse fa-2x"></i> Inventory
                    </button>
                </div>
            </div>
        
            <!-- Task section -->
            <div class="content-task m-0">
                <?php
                $housekeeperId = $_SESSION['emp_id']; // Assuming user_id is stored in session
                $task = getAssignedTask($housekeeperId);
                if ($task) {
                    echo '<div class="card mb-3 shadow-sm border-0">';
                    echo '<div class="card-header d-flex justify-content-between align-items-center" style="background-color: #f8f9fa;">';
                    echo '<span class="badge bg-warning text-dark">' . htmlspecialchars($task['status']) . '</span>';
                    echo '<small class="text-muted">' . date("F j, g:i A", strtotime($task['create_at'])) . '</small>';
                    echo '</div>';
                    
                    echo '<div class="card-body">';
                    echo '<h6 class="fw-bold">id# ' . htmlspecialchars($task['task_id']) . '</h6>';
                    echo '<p class="text-muted m-0"><strong>' . htmlspecialchars($task['room']) . '</strong></p>';
                    echo '<p class="text-muted m-0"><strong>' . htmlspecialchars($task['uname']) . '</strong></p>';
                    
                    echo '<hr class="my-2">';
                    echo '<p class="fw-bold m-0">Request</p>';
                    echo '<p class="m-0">' . htmlspecialchars($task['request']) . '</p>';
                    echo '<p class="fw-bold m-0">Details</p>';
                    echo '<p class="m-0">' . htmlspecialchars($task['details']) . '</p>';
                    
                    echo '<div class="d-flex justify-content-center align-items-center mt-3">';
                    echo '<button class="btn btn-complete btn-sm" data-bs-toggle="modal" data-bs-target="#completeModal">Complete</button>';
                    echo '<button class="btn btn-report btn-sm" data-bs-toggle="modal" data-bs-target="#reportModal">Report</button>';
                    echo '</div>';
                    
                    echo '</div>';
                    echo '</div>';
                } else {
                    echo '<div class="alert alert-info text-center">No task available</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Report Modal -->
    <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="reportModalLabel">Report Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Please provide details for reporting this task.</p>
                    <form>
                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="reportDetails" name="reportDetails" rows="3" placeholder="Enter details" required></textarea>
                            <label for="reportDetails" class="form-label">Enter details</label>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-report btn-sm">Report</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Lost and Found Modal -->
    <div class="modal fade" id="lostFoundModal" tabindex="-1" aria-labelledby="lostFoundModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="registerModalLabel">Submit Lost/Found</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data"> <!-- Add enctype -->
                        <div class="form-floating form-floating-sm mb-3">
                            <input type="text" class="form-control" id="found_by" name="found_by" placeholder="Found by" required>
                            <label for="found_by">Found by</label>
                        </div>
                        <div class="form-floating mb-3">
                            <select class="form-select" id="type" name="type" placeholder="Select type" required>
                                <option value="">Select Type</option>
                                <option value="Lost">Lost</option>
                                <option value="Found">Found</option>
                            </select>
                            <label for="type">Type</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="room" name="room" placeholder="Enter room" required>
                            <label for="room">Room</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="date" class="form-control" id="date" name="date" placeholder="Date" required>
                            <label for="date">Date</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="item" name="item" placeholder="Item" required>
                            <label for="item">Item</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="description" name="description" placeholder="Description" required>
                            <label for="description">Description</label>
                        </div>
                        <div class="mb-3">
                            <input type="file" class="form-control" id="picture" name="picture" accept="image/*" required>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" name="submit_requestlostnfound" class="btn btn-primary btn-sm rounded-pill px-4 py-2">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Maintenance Modal -->
    <div class="modal fade" id="maintenanceModal" tabindex="-1" aria-labelledby="maintenanceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="maintenanceModalLabel">Maintenance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Maintenance form or content goes here -->
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="request_title" name="request_title" placeholder="Enter request title" required>
                            <label for="request_title" class="form-label">Enter request Title</label>
                        </div>
                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter description" required></textarea>
                            <label for="description" class="form-label">Enter description</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="room_no" name="room_no" placeholder="Enter room number" required>
                            <label for="room_no" class="form-label">Enter Room Number</label>
                        </div>
                        <div class="form-floating mb-3">
                            <select class="form-select" id="priority" name="priority" required>
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                            <label for="priority" class="form-label">Enter Priority</label>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" name="submit_request" class="btn btn-primary btn-sm rounded-pill px-4 py-2">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory Modal -->
    <div class="modal fade" id="inventoryModal" tabindex="-1" aria-labelledby="inventoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="inventoryModalLabel">Inventory</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Inventory form or content goes here -->
                    <p>Submit inventory requests.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-sm">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Complete Task Modal -->
    <div class="modal fade" id="completeModal" tabindex="-1" aria-labelledby="completeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="completeTaskModalLabel">Complete Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="completeTaskForm" action="complete_task.php" method="POST">
                        <?php if (isset($task['task_id'])): ?>
                            <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">
                        <?php endif; ?>
                        
                        <h6 class="mb-3">Select items used for this task:</h6>
                        
                        <div class="inventory-container" style="max-height: 400px; overflow-y: auto;">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead class="sticky-top bg-light">
                                        <tr>
                                            <th>Item Name</th>
                                            <th>Category</th>
                                            <th class="text-center">Available</th>
                                            <th class="text-center">Quantity Used</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($inventory_items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center">
                                                    <input type="number" class="form-control form-control-sm text-center used-quantity" 
                                                        style="max-width: 80px;"
                                                        name="used_quantity[<?php echo $item['id']; ?>]" 
                                                        value="0" min="0" max="<?php echo $item['quantity']; ?>">
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="form-floating mb-3 mt-3">
                            <textarea class="form-control" id="completionNotes" name="completion_notes" rows="3" placeholder="Enter notes (optional)"></textarea>
                            <label for="completionNotes" class="form-label">Completion Notes (Optional)</label>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Mark as Complete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Handle form submission
            document.getElementById('completeTaskForm').addEventListener('submit', function (e) {
                e.preventDefault();
                
                // Check if any item has a quantity greater than available
                let hasError = false;
                document.querySelectorAll('.used-quantity').forEach(input => {
                    const max = parseInt(input.getAttribute('max'));
                    const value = parseInt(input.value);
                    
                    if (value > max) {
                        hasError = true;
                        input.classList.add('is-invalid');
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });
                
                if (hasError) {
                    alert('Some quantities exceed available stock.');
                    return;
                }
                
                const formData = new FormData(this);
                
                fetch('complete_task.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Create a Bootstrap alert
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success alert-dismissible fade show';
                        alertDiv.role = 'alert';
                        alertDiv.innerHTML = `
                            <strong>Success!</strong> ${data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        
                        // Insert at the top of the modal body
                        const modalBody = document.querySelector('#completeModal .modal-body');
                        modalBody.insertBefore(alertDiv, modalBody.firstChild);
                        
                        // Reload after 2 seconds
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        alert('Error completing task: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while completing the task.');
                });
            });

            // Add event listener for input validation
            document.querySelectorAll('.used-quantity').forEach(input => {
                input.addEventListener('input', function() {
                    const max = parseInt(this.getAttribute('max'));
                    const value = parseInt(this.value);
                    
                    if (value > max) {
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                    }
                });
            });
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
