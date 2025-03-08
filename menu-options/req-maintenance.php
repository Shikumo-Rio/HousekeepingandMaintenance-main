<?php
session_start();
require_once("../database.php");

if (!isset($_SESSION['verified']) || !isset($_SESSION['uname'])) {
    header("Location: ../index.html");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uname = $_SESSION['uname'];
    $room = $_SESSION['room_number'];
    $status = "Pending";
    
    // Get form data
    $title = $_POST['requestTitle'] ?? '';
    $description = $_POST['description'] ?? '';
    
    // Insert into guest-maintenance table
    $stmt = $conn->prepare("INSERT INTO `guest-maintenance` (uname, title, description, room, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $uname, $title, $description, $room, $status);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Maintenance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

</head>
<body>
    <div class="menu-container container p-2">
         <!-- Header Section -->
        <div class="menu-header d-flex align-items-center justify-content-between py-2 p-3">
            <div class="d-flex align-items-center">
                <img src="../img/logo.webp" alt="User Icon" class="rounded-circle me-2" width="40" height="40">
                <div>
                    <h5 class="mb-0">Paradise Hotel</h5>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center mb-4">
                    <a href="services.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                </div>
        <h5 class="mt-4 m-2 mb-2 fw-semibold">Report Maintenance</h5>

        <!-- Maintenance Request Form -->
        <div class="card p-4 mt-4">
            <p class="text-muted mb-4 text-center">Please describe the issue you are experiencing.</p>
            <form id="maintenanceForm">
                <div class="form-floating mb-3">
                    <input type="text" id="requestTitle" name="requestTitle" class="form-control" placeholder="Enter request title" required>
                    <label for="requestTitle">Title</label>
                </div>

                <div class="form-floating mb-3">
                    <textarea id="description" name="description" class="form-control" placeholder="Describe the issue" style="height: 120px;" required></textarea>
                    <label for="description">Description</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="text" id="roomNumber" class="form-control" value="<?php echo $_SESSION['room_number']; ?>" readonly>
                    <label for="roomNumber">Room Number</label>
                </div>

                <button type="submit" class="btn btn-success w-100">Submit Request</button>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#maintenanceForm').submit(function(event) {
                event.preventDefault();
                
                const formData = new FormData(this);

                fetch('req-maintenance.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Your maintenance request has been submitted successfully!');
                        this.reset();
                        document.getElementById('roomNumber').value = '<?php echo $_SESSION['room_number']; ?>';
                    } else {
                        alert('Error submitting request');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error submitting request');
                });
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
