<?php
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

// Get counts for the cards
$total_requests = $conn->query("SELECT COUNT(*) as count FROM maintenance_requests")->fetch_assoc()['count'];
$confirmed_requests = $conn->query("SELECT COUNT(*) as count FROM maintenance_requests WHERE status = 'Completed'")->fetch_assoc()['count'];
$emailed_requests = $conn->query("SELECT COUNT(*) as count FROM maintenance_requests WHERE emailed = 1")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/css/bootstrap.min.css">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/inventory.css"> 
    <link rel="icon" href="img/logo.webp">
    <title>Maintenance Management</title>
</head>
<body>

    <?php include('index.php'); ?>

    
    <!-- Cards Row -->
    <div class="container">
        <!-- Title Heading -->
        <div class="p-4 mb-4 title-heading card">
            <div class="d-flex justify-content-between align-items-center">
                <h3>Maintenance Requests</h3>
                <button class="btn btn-success" onclick="showExportModal()">
                    <i class="fas fa-file-export"></i> Export
                </button>
            </div>
        </div>

        <div class="row m-0 text-center mb-4">
        <!-- Requests Card -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card">
                    <div class="underline"></div> <!-- Add underline here for consistency -->
                    <div class="card-body">
                        <h5 class="card-title">Requests</h5>
                        <h3 class="card-text"><i class="fas fa-list"></i> <?php echo $total_requests; ?></h3>
                    </div>
                </div>
            </div>
    
            <!-- Confirmed Card -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card">
                    <div class="underline"></div> <!-- Add underline here for consistency -->
                    <div class="card-body">
                        <h5 class="card-title">Confirmed</h5>
                        <h3 class="card-text"><i class="fas fa-check"></i> <?php echo $confirmed_requests; ?></h3>
                    </div>
                </div>
            </div>
    
            <!-- Emailed Card -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card">
                    <div class="underline"></div> <!-- Add underline here for consistency -->
                    <div class="card-body">
                        <h5 class="card-title">Emailed</h5>
                        <h3 class="card-text"><i class="fas fa-envelope"></i> <?php echo $emailed_requests; ?></h3>
                    </div>
                </div>
            </div>
        </div>  
    </div>

    <!-- Maintenance Requests Table -->
    <div class="container">
        <div class="card shadow-lg rounded-3">
            <div class="card-body m-0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="mb-2 mt-4 m-3">Requests Overview</h3>
                    <div class="email me-4 mt-2">
                        <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#emailModal">
                         Email Request
                        </button>
                     </div>
                </div>
                <div class='table-responsive'>
                    <table class="table table-hover">
                        <thead class="striky-top">
                            <tr class="bg-dark text-light">
                                <th>Request Title</th>
                                <th>Description</th>
                                <th>Location</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Scheduled On</th>
                                <th>Being work by</th>
                                
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT mr.*, 
                                    GROUP_CONCAT(DISTINCT e.name, ' (', am.emp_id, ')') as assigned_employees,
                                    GROUP_CONCAT(DISTINCT am.emp_id) as assigned_emp_ids
                                  FROM maintenance_requests mr 
                                  LEFT JOIN assigned_maintenance am ON mr.id = am.maintenance_request_id
                                  LEFT JOIN employee e ON am.emp_id = e.emp_id 
                                  GROUP BY mr.id";
                            $result = $conn->query($sql);

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['request_title']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['room_no']) . "</td>";
                                    echo "<td><span class='badge text-bg-" .
                                        ($row['priority'] == 'High' ? 'danger' :
                                        ($row['priority'] == 'Medium' ? 'warning' : 'success')) .
                                        "'>" . htmlspecialchars($row['priority']) . "</span></td>";
                                    echo "<td><span class='badge text-bg-" .
                                        ($row['status'] == 'Pending' ? 'secondary' : 
                                        ($row['status'] == 'Completed' ? 'success' : 'warning')) .
                                        "'>" . htmlspecialchars($row['status']) . "</span></td>";
                                    echo "<td>" . ($row['schedule'] ? htmlspecialchars($row['schedule']) : '<span class="text-muted">Not scheduled</span>') . "</td>";
                                    echo "<td>" . ($row['assigned_employees'] ? htmlspecialchars($row['assigned_employees']) : '<span class="text-muted">Not assigned</span>') . "</td>";
                                    
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7'>No records found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Guest Maintenance Table -->
    <div class="container mt-4 mb-4">
        <div class="card shadow-lg rounded-3">
            <div class="card-body m-0">
                <h3 class="mb-4 mt-2">Guest Maintenance Requests</h3>
                <div class='table-responsive'>
                    <table class="table table-hover">
                        <thead class="striky-top">
                            <tr class="bg-dark text-light">
                                <th>ID</th>
                                <th>Guest Name</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Room</th>
                                <th>Status</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT id, uname, title, description, room, status, created_at FROM guest_maintenance";
                            $result = $conn->query($sql);

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['uname']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['room']) . "</td>";
                                    echo "<td><span class='badge text-bg-" .
                                        ($row['status'] == 'Pending' ? 'secondary' : 
                                        ($row['status'] == 'Completed' ? 'success' : 'warning')) .
                                        "'>" . htmlspecialchars($row['status']) . "</span></td>";
                                    echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7'>No guest maintenance records found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Email Modal -->
    <div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="emailModalLabel">Email Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="emailForm" method="POST" action="email_request.php">
                        <div class="form-floating mb-3">
                            <select class="form-select" id="requestID" name="requestID" required>
                                <option value="">Select Request</option>
                                <?php
                                $sql = "SELECT id, request_title, room_no FROM maintenance_requests";
                                $result = $conn->query($sql);
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='" . $row['id'] . "'>Request #" . $row['id'] . 
                                         " - Room " . $row['room_no'] . " - " . $row['request_title'] . "</option>";
                                }
                                ?>
                            </select>
                            <label for="requestID">Select Maintenance Request</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="emailAddress" name="emailAddress" required>
                            <label for="emailAddress">Email To</label>
                        </div>
                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="additionalNotes" name="additionalNotes" style="height: 100px"></textarea>
                            <label for="additionalNotes">Additional Notes (Optional)</label>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success btn-sm rounded-pill px-4 py-2">Send Email</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>  
    </div>

    <!-- Add this modal before the closing body tag -->
    <div class="modal fade" id="emailResponseModal" tabindex="-1" aria-labelledby="emailResponseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="emailResponseModalLabel">Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="emailResponseMessage"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalLabel">Export Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">What would you like to export?</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exportType" id="exportTypeMaintenance" value="maintenance_requests" checked>
                            <label class="form-check-label" for="exportTypeMaintenance">Maintenance Requests</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exportType" id="exportTypeGuest" value="guest_maintenance">
                            <label class="form-check-label" for="exportTypeGuest">Guest Maintenance Requests</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exportType" id="exportTypeBoth" value="both">
                            <label class="form-check-label" for="exportTypeBoth">Both Tables</label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Export Format</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exportFormat" id="exportFormatExcel" value="excel" checked>
                            <label class="form-check-label" for="exportFormatExcel">Excel (.xls)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exportFormat" id="exportFormatPDF" value="pdf">
                            <label class="form-check-label" for="exportFormatPDF">PDF</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="exportData()">Export</button>
                </div>
            </div>
        </div>
    </div>

     <!-- Include Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function updateStatus(requestId, newStatus) {
        // ...existing updateStatus code...
    }

    // Add this new function to handle email submission
    function sendEmail(formElement) {
        formElement.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('email_request.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const modalElement = document.getElementById('emailResponseModal');
                const messageElement = document.getElementById('emailResponseMessage');
                const modal = new bootstrap.Modal(modalElement);

                if (data.success) {
                    messageElement.innerHTML = `
                        <div class="text-success">
                            <i class="fas fa-check-circle fa-3x mb-3"></i>
                            <p>${data.message}</p>
                        </div>`;
                    
                    // Close email modal and show response modal
                    bootstrap.Modal.getInstance(document.getElementById('emailModal')).hide();
                    modal.show();

                    // Automatically reload page after 2 seconds on success
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    messageElement.innerHTML = `
                        <div class="text-danger">
                            <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                            <p>${data.message}</p>
                            <small class="text-muted">${data.error || ''}</small>
                        </div>`;
                    modal.show();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const modalElement = document.getElementById('emailResponseModal');
                const messageElement = document.getElementById('emailResponseMessage');
                const modal = new bootstrap.Modal(modalElement);
                
                messageElement.innerHTML = `
                    <div class="text-danger">
                        <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                        <p>An error occurred while sending the email.</p>
                    </div>`;
                modal.show();
            });
        });
    }

    // Export functionality
    function showExportModal() {
        // Show the modal using Bootstrap 5
        const exportModal = new bootstrap.Modal(document.getElementById('exportModal'));
        exportModal.show();
    }

    function exportData() {
        const exportType = document.querySelector('input[name="exportType"]:checked').value;
        const exportFormat = document.querySelector('input[name="exportFormat"]:checked').value;
        
        // Build the URL with parameters
        const url = `export_maintenance.php?type=${exportType}&format=${exportFormat}`;
        
        // Open in new window/tab
        window.open(url, '_blank');
        
        // Close the modal
        const exportModalEl = document.getElementById('exportModal');
        const exportModal = bootstrap.Modal.getInstance(exportModalEl);
        exportModal.hide();
    }

    // Initialize email form submission
    document.addEventListener('DOMContentLoaded', function() {
        const emailForm = document.getElementById('emailForm');
        if (emailForm) {
            sendEmail(emailForm);
        }
    });
    </script>
</body>
</html>
