<?php
require_once 'database.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
if ($_SESSION['user_type'] !== 'Admin') {
    header("Location: unauthorized.php");
    exit;
}

// Fetch employees from the database grouped by their roles
$employees = [
    'housekeeper' => [],
    'room_attendant' => [],
    'linen_attendant' => []
];

$query = "
    SELECT e.emp_id, e.name, e.status, e.role 
    FROM employee e
    INNER JOIN login_accounts l ON e.emp_id = l.emp_id
    WHERE l.user_type = 'employee'
";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $role = $row['role'] ?? 'housekeeper';
        $employees[$role][] = $row;
    }
} else {
    echo "Error fetching employees: " . $conn->error;
}

// Handle employee addition
if (isset($_POST['addEmployee'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $status = $conn->real_escape_string($_POST['status']);
    $role = $conn->real_escape_string($_POST['role']);

    $insertEmployee = "INSERT INTO employee (name, status, role) VALUES ('$name', '$status', '$role')";
    
    if ($conn->query($insertEmployee) === TRUE) {
        $emp_id = $conn->insert_id;
        $defaultPassword = password_hash('paradise', PASSWORD_BCRYPT);
        $insertLogin = "INSERT INTO login_accounts (username, password, user_type, emp_id, is_online) 
                        VALUES ('$emp_id', '$defaultPassword', 'employee', '$emp_id', 0)";
        
        if ($conn->query($insertLogin) === TRUE) {
            echo "<script>alert('Employee and login account created successfully!');</script>";
        } else {
            echo "<script>alert('Error creating login account.');</script>";
        }
    } else {
        echo "<script>alert('Error adding employee.');</script>";
    }
}

// Handle employee request submission
if (isset($_POST['requestEmployee'])) {
    header('Location: email_request.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
     <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/housekeepers.css">
    <link rel="icon" href="img/logo.webp">
    <title>Housekeepers</title>
</head>
<body>
    <?php include('index.php'); ?>
    <div class="container py-4">
        <div class="p-4 housekeepers-heading card mt-0">
            <h3>Housekeepers</h3>
        </div>
        <div class="housekeeper-btn mb-4">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestEmployeeModal">
                <i class="fas fa-user-check"></i> Request
            </button>
            <button class="btn btn-success ms-2" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                <i class="fas fa-user-plus"></i> Add
            </button>
        </div>

        <!-- Room Attendants Section -->    
        <div class="row gx-6 mt-0 m-0">
            <div class="emp-type">
                <h6 class="emp-type fw-semibold">Room Attendants</h6>
            </div>
            <?php foreach ($employees['room_attendant'] as $employee) : ?>
                <?php include('employee_card.php'); ?>
            <?php endforeach; ?>
        </div>

        <!-- Linen Attendants Section -->
        <div class="row gx-6 mt-0 m-0">
            <h6 class="mt-4 fw-semibold">Linen Attendants</h6>
            <?php foreach ($employees['linen_attendant'] as $employee) : ?>
                <?php include('employee_card.php'); ?>
            <?php endforeach; ?>
        </div>

        <!-- Employee Details Modal -->
        <div class="modal fade" id="employeeDetailsModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 p-0">
                    <div class="modal-header border-0">
                        <h5 class="modal-title">Employee Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header border-0 bg-transparent">
                                        <h6 class="mb-0 fw-semibold mt-2">Basic Information</h6>
                                    </div>
                                    <div class="card-body" style="font-size: 12px;" id="basicInfo">Loading...</div>
                                </div>
                            </div>
                            
                            <!-- Work Information -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header border-0 bg-transparent">
                                        <h6 class="mb-0 fw-semibold mt-2">Work Information</h6>
                                    </div>
                                    <div class="card-body" style="font-size: 12px;" id="workInfo">Loading...</div>
                                </div>
                            </div>
                            
                            <!-- Performance Metrics -->
                            <div class="col-12">
                                <div class="card mb-3">
                                    <div class="card-header border-0 bg-transparent">
                                        <h6 class="mb-0 fw-semibold mt-2">Performance Metrics</h6>
                                    </div>
                                    <div class="card-body" id="performanceInfo">Loading...</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="mt-3 d-flex justify-content-center gap-3">
                            <button class="btn btn-primary" style="font-size: 12px;" onclick="updateEmployeeDetails(currentEmpId)">
                                <i class="fas fa-edit"></i> Update Details
                            </button>
                            <button class="btn btn-warning" style="font-size: 12px;" onclick="toggleEmployeeStatus(currentEmpId)">
                                <i class="fas fa-user-lock"></i> Toggle Status
                            </button>
                            <button class="btn btn-danger" style="font-size: 12px;" onclick="removeEmployee(currentEmpId)">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Audit Trail Modal -->
        <div class="modal fade" id="auditTrailModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Employee Request History</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="font-size: 12px;">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Request ID</th>
                                        <th>Role</th>
                                        <th>Date Requested</th>
                                        <th>Status</th>
                                        <th>HR Response</th>
                                        <th>Response Date</th>
                                    </tr>
                                </thead>
                                <tbody id="auditTrailBody">
                                    <!-- Only the 10 most recent requests will be shown -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        let currentEmpId = null;

        function showEmployeeDetails(empId) {
            currentEmpId = empId;
            const modal = new bootstrap.Modal(document.getElementById('employeeDetailsModal'));
            
            fetch(`get_employee_details.php?emp_id=${empId}`)
                .then(response => response.json())
                .then(data => {
                    // Basic Information section - only name, role, and ID
                    document.getElementById('basicInfo').innerHTML = `
                        <div class="mb-3">
                            <p><strong>Employee ID:</strong> ${data.emp_id}</p>
                            <p><strong>Name:</strong> ${data.name}</p>
                            <p><strong>Role:</strong> ${data.role}</p>
                        </div>
                    `;

                    // Remove work information as it's not needed
                    document.getElementById('workInfo').innerHTML = '';
                    document.getElementById('workInfo').parentElement.style.display = 'none';

                    // Performance Metrics section - only total tasks and average completion time
                    document.getElementById('performanceInfo').innerHTML = `
                        <div class="row text-center">
                            <div class="col-md-6">
                                <h4>${data.total_tasks || 0}</h4>
                                <small>Total Tasks</small>
                            </div>
                            <div class="col-md-6">
                                <h4>${formatTime(data.avg_completion_time || 0)}</h4>
                                <small>Average Completion Time</small>
                            </div>
                        </div>
                    `;

                    modal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading employee details');
                });
        }

        function updateEmployeeDetails(empId) {
            // Implementation for updating employee details
        }

        function toggleEmployeeStatus(empId) {
            if(confirm('Are you sure you want to change this employee\'s status?')) {
                fetch('toggle_employee_status.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({emp_id: empId})
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert('Status updated successfully');
                        showEmployeeDetails(empId); // Refresh the modal
                    }
                });
            }
        }

        function removeEmployee(empId) {
            if(confirm('Are you sure you want to remove this employee? This action cannot be undone.')) {
                fetch('remove_employee.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({emp_id: empId})
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert('Employee removed successfully');
                        location.reload();
                    }
                });
            }
        }

        // Add this helper function for time formatting
        function formatTime(minutes) {
            minutes = parseFloat(minutes);
            if (!minutes || isNaN(minutes)) return 'N/A';
            if (minutes < 60) return `${Math.round(minutes)}m`;
            const hours = Math.floor(minutes / 60);
            const mins = Math.round(minutes % 60);
            return `${hours}h ${mins}m`;
        }

        // Add this function after your existing functions
        function showAuditTrail(empId) {
            fetch(`get_audit_trail.php?emp_id=${empId}&limit=10`)  // Added limit parameter
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('auditTrailBody');
                    tbody.innerHTML = '';
                    
                    // Only process up to 10 records
                    data.slice(0, 10).forEach(request => {
                        tbody.innerHTML += `
                            <tr>
                                <td>${request.request_id}</td>
                                <td>${request.role}</td>
                                <td>${request.request_date}</td>
                                <td><span class="badge bg-${getStatusColor(request.status)}">${request.status}</span></td>
                                <td>${request.response_notes || '-'}</td>
                                <td>${request.response_date || '-'}</td>
                            </tr>
                        `;
                    });
                    
                    const auditModal = new bootstrap.Modal(document.getElementById('auditTrailModal'));
                    auditModal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading audit trail');
                });
        }

        function getStatusColor(status) {
            switch(status.toLowerCase()) {
                case 'pending': return 'warning';
                case 'approved': return 'success';
                case 'denied': return 'danger';
                default: return 'secondary';
            }
        }

        function submitEmployeeRequest(event) {
            event.preventDefault();
            const form = document.getElementById('requestEmployeeForm');
            const formData = new FormData(form);
            formData.append('requestEmployee', '1');

            fetch('email_request.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    const modalElement = document.getElementById('requestEmployeeModal');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.dispose(); // Completely dispose of the modal
                    }
                    cleanupModal(); // Clean up modal artifacts
                    form.reset();
                    loadRequestHistory();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting the request.');
            });
        }

        // Add event listener for modal hidden event
        document.getElementById('requestEmployeeModal').addEventListener('hidden.bs.modal', function () {
            document.body.classList.remove('modal-open');
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        });

        // Add this new function for loading request history
        function loadRequestHistory(page = 1) {
            fetch(`get_request_history.php?page=${page}`)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('requestHistoryBody');
                    const paginationControls = document.getElementById('paginationControls');
                    tbody.innerHTML = '';
                    
                    data.requests.forEach(request => {
                        tbody.innerHTML += `
                            <tr>
                                <td>#${request.request_id}</td>
                                <td>${request.role}</td>
                                <td>${request.quantity}</td>
                                <td><span class="badge bg-${getStatusColor(request.status)}">${request.status}</span></td>
                                <td>${request.request_date}</td>
                                <td>${request.response_notes || '-'}</td>
                            </tr>
                        `;
                    });

                    /// Generate pagination controls
                    paginationControls.innerHTML = '';
                    if (data.totalPages > 1) {
                        // Previous button
                        if (data.currentPage > 1) {
                            paginationControls.innerHTML += `
                                <button class="btn btn-sm btn-outline-success mx-1" onclick="loadRequestHistory(${data.currentPage - 1})">
                                    &laquo; Previous
                                </button>
                            `;
                        }

                        // Page numbers
                        for (let i = 1; i <= data.totalPages; i++) {
                            if (i === data.currentPage) {
                                paginationControls.innerHTML += `
                                    <button class="btn btn-sm btn-success mx-1">${i}</button>
                                `;
                            } else {
                                paginationControls.innerHTML += `
                                    <button class="btn btn-sm btn-outline-success mx-1" onclick="loadRequestHistory(${i})">${i}</button>
                                `;
                            }
                        }

                        // Next button
                        if (data.currentPage < data.totalPages) {
                            paginationControls.innerHTML += `
                                <button class="btn btn-sm btn-outline-success mx-1" onclick="loadRequestHistory(${data.currentPage + 1})">
                                    Next &raquo;
                                </button>
                            `;
                        }
                    }

                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading request history');
                });
        }

        function getStatusColor(status) {
            switch(status.toLowerCase()) {
                case 'pending': return 'warning';
                case 'approved': return 'success';
                case 'denied': return 'danger';
                default: return 'secondary';
            }
        }

        function cleanupModal() {
            document.body.classList.remove('modal-open');
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.parentNode.removeChild(backdrop);
            }
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }

        // Update modal event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const modalElement = document.getElementById('requestEmployeeModal');
            modalElement.addEventListener('hidden.bs.modal', cleanupModal);
            modalElement.addEventListener('hide.bs.modal', cleanupModal);
        });
        </script>

    <!-- Updated Modal with Role Selection and History -->
    <div class="modal fade" id="requestEmployeeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Employee Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#new-request" type="button">
                                New Request
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#request-history" type="button" onclick="loadRequestHistory()">
                                Request History
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- New Request Tab -->
                        <div class="tab-pane fade show active" id="new-request">
                            <form id="requestEmployeeForm" method="POST" onsubmit="submitEmployeeRequest(event)">
                                <div class="form-floating mb-3">
                                    <select name="role" class="form-control rounded-3" style="font-size: 12px;" id="role" required>
                                        <option value="room_attendant">Room Attendant</option>
                                        <option value="linen_attendant">Linen Attendant</option>

                                    </select>
                                    <label for="role">Employee Role</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <input type="number" name="quantity" class="form-control rounded-3" style="font-size: 12px;" id="quantity" required>
                                    <label for="quantity">Number of Employees Needed</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <textarea name="reason" class="form-control rounded-3" style="font-size: 12px;" id="reason" style="height: 100px;" required></textarea>
                                    <label for="reason">Reason for Request</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <select name="preferred_shift" class="form-control rounded-3" style="font-size: 12px;" id="preferred_shift" required>
                                        <option value="morning">Morning (6AM - 2PM)</option>
                                        <option value="afternoon">Afternoon (2PM - 10PM)</option>
                                        <option value="night">Night (10PM - 6AM)</option>
                                    </select>
                                    <label for="preferred_shift">Preferred Schedule/Shift</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <select name="urgency_level" class="form-control rounded-3" style="font-size: 12px;" id="urgency_level" required>
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                    <label for="urgency_level">Urgency Level</label>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" name="requestEmployee" class="btn btn-success btn-sm px-4 py-2" style="font-size: 12px;">
                                        <i class="bx bx-send me-1"></i>Submit
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Request History Tab -->
                        <div class="tab-pane fade" id="request-history">
                            <div class="table-responsive rounded shadow-sm p-0">
                                <table class="table table-hover align-middle" style="font-size: 12px;">
                                    <thead class="text-center">
                                        <tr>
                                            <th>Request ID</th>
                                            <th>Role</th>
                                            <th>Quantity</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>HR Response</th>
                                        </tr>
                                    </thead>
                                    <tbody id="requestHistoryBody" class="text-center">
                                        <!-- Data will be loaded here -->
                                    </tbody>
                                </table>

                                <!-- Pagination and Records Info -->
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <small class="text-muted">Showing 10 records per page</small>
                                    <nav>
                                        <ul class="pagination pagination-sm mb-0" id="paginationControls">
                                            <!-- Pagination buttons will be added here -->
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Employee Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Add New Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <form action="" method="POST">
                        <div class="form-floating mb-3">
                            <input type="text" name="name" class="form-control rounded-3" style="font-size: 12px;" id="name" required>
                            <label for="name">Employee Name</label>
                        </div>
                        <div class="form-floating mb-3">
                            <select name="status" class="form-control rounded-3" style="font-size: 12px;" id="status" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                            <label for="status">Status</label>
                        </div>
                        <div class="form-floating mb-3">
                            <select name="role" class="form-control rounded-3" style="font-size: 12px;" id="role" required>
                                <option value="housekeeper">Housekeeper</option>
                                <option value="room_attendant">Room Attendant</option>
                                <option value="linen_attendant">Linen Attendant</option>
                            </select>
                            <label for="role">Employee Role</label>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" name="addEmployee" class="btn btn-success btn-sm px-4 py-2" style="font-size: 12px;">Add Employee</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function editHousekeeper(empId) {
            alert('Edit housekeeper with ID: ' + empId);
        }

        function deleteHousekeeper(empId) {
            if (confirm('Are you sure you want to delete this housekeeper?')) {
                alert('Deleted housekeeper with ID: ' + empId);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
        var firstModal = new bootstrap.Modal(document.getElementById('housekeeperModal'));
        var secondModal = new bootstrap.Modal(document.getElementById('addEmployeeModal'));

        // When the Add Housekeeper button is clicked, hide the first modal and show the second modal
        document.querySelector('.add-housekeeper .btn').addEventListener('click', function () {
            firstModal.hide();
            secondModal.show();
        });

        // Show the first modal when the second modal is closed
        document.getElementById('addEmployeeModal').addEventListener('hide.bs.modal', function () {
            firstModal.show(); // Reopen the first modal
        });
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
