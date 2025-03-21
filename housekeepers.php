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
        <div class="p-4 housekeepers-heading card">
            <h3>Housekeepers</h3>
        </div>
        <div class="housekeeper-btn">
            <button class="btn" data-bs-toggle="modal" data-bs-target="#requestEmployeeModal">
                <i class="fa-solid fa-plus"></i>Request Employee
            </button>
            <button class="btn ms-2" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                <i class="fa-solid fa-plus"></i>Add Employee
            </button>
        </div>

        <!-- Room Attendants Section -->
        <h4 class="mt-4">Room Attendants</h4>
        <div class="row gx-6 mt-0 m-0">
            <?php foreach ($employees['room_attendant'] as $employee) : ?>
                <?php include('employee_card.php'); ?>
            <?php endforeach; ?>
        </div>

        <!-- Linen Attendants Section -->
        <h4 class="mt-4">Linen Attendants</h4>
        <div class="row gx-6 mt-0 m-0">
            <?php foreach ($employees['linen_attendant'] as $employee) : ?>
                <?php include('employee_card.php'); ?>
            <?php endforeach; ?>
        </div>

        <!-- Employee Details Modal -->
        <div class="modal fade" id="employeeDetailsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Employee Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Basic Information</h6>
                                    </div>
                                    <div class="card-body" id="basicInfo">Loading...</div>
                                </div>
                            </div>
                            
                            <!-- Performance Metrics -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Performance Metrics</h6>
                                    </div>
                                    <div class="card-body" id="performanceInfo">Loading...</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="mt-3 d-flex justify-content-end">
                            <button class="btn btn-danger" onclick="removeEmployee(currentEmpId)">
                                <i class="fas fa-trash"></i> Remove Employee
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
                    <div class="modal-body">
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
                            <p><strong>Status:</strong> <span class="badge bg-${data.status === 'Active' ? 'success' : 'secondary'}">${data.status}</span></p>
                        </div>
                    `;

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
            if(confirm('Are you sure you want to delete this employee? This action cannot be undone.')) {
                fetch('remove_employee.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({emp_id: empId})
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        // Close the modal first
                        const modal = bootstrap.Modal.getInstance(document.getElementById('employeeDetailsModal'));
                        if (modal) {
                            modal.hide();
                        }
                        
                        // Show success message and reload
                        alert('Employee successfully removed.');
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to remove employee.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while removing the employee.');
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
                if (data.success) {
                    // Get selected role and quantity for display
                    const roleSelect = document.getElementById('role');
                    const roleText = roleSelect.options[roleSelect.selectedIndex].text;
                    const quantity = document.getElementById('quantity').value;
                    
                    // Set details in success modal
                    document.getElementById('requestDetails').textContent = `${quantity} ${roleText}(s)`;
                    
                    // Set content in the email response message
                    document.getElementById('emailResponseMessage').innerHTML = `
                        <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                        <p class="mb-0">Request has been successfully added!</p>
                        <p class="fw-bold fs-5 mb-0">${quantity} ${roleText}(s)</p>
                    `;
                    
                    // Hide request modal
                    const requestModal = bootstrap.Modal.getInstance(document.getElementById('requestEmployeeModal'));
                    requestModal.hide();
                    
                    // Show success modal
                    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();
                    
                    // Set timeout to auto-close the success modal after 2 seconds
                    setTimeout(() => {
                        successModal.hide();
                        // Ensure proper cleanup after modal is hidden
                        document.body.classList.remove('modal-open');
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) {
                            backdrop.parentNode.removeChild(backdrop);
                        }
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                    }, 2000);
                    
                    form.reset();
                    loadRequestHistory();
                } else {
                    alert(data.message || 'An error occurred while submitting the request.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting the request.');
            });
        }

        function cleanupModal() {
            // More robust cleanup function
            setTimeout(() => {
                document.body.classList.remove('modal-open');
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => {
                    if (backdrop && backdrop.parentNode) {
                        backdrop.parentNode.removeChild(backdrop);
                    }
                });
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }, 100); // Short delay to ensure modal has finished transition
        }

        // Update modal event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const modalElement = document.getElementById('requestEmployeeModal');
            modalElement.addEventListener('hidden.bs.modal', cleanupModal);
            
            // Fix success modal cleanup
            const successModalElement = document.getElementById('successModal');
            successModalElement.addEventListener('hidden.bs.modal', cleanupModal);
            
            // Global handler to ensure backdrop is removed if it persists
            document.addEventListener('click', function() {
                const body = document.body;
                if (!document.querySelector('.modal.show') && body.classList.contains('modal-open')) {
                    cleanupModal();
                }
            });
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

                    // Generate pagination controls
                    paginationControls.innerHTML = '';
                    if (data.totalPages > 1) {
                        // Previous button
                        if (data.currentPage > 1) {
                            paginationControls.innerHTML += `
                                <button class="btn btn-sm btn-outline-secondary" onclick="loadRequestHistory(${data.currentPage - 1})">
                                    Previous
                                </button>
                            `;
                        }

                        // Page numbers
                        for (let i = 1; i <= data.totalPages; i++) {
                            if (i === data.currentPage) {
                                paginationControls.innerHTML += `
                                    <button class="btn btn-sm btn-secondary">${i}</button>
                                `;
                            } else {
                                paginationControls.innerHTML += `
                                    <button class="btn btn-sm btn-outline-secondary" onclick="loadRequestHistory(${i})">${i}</button>
                                `;
                            }
                        }

                        // Next button
                        if (data.currentPage < data.totalPages) {
                            paginationControls.innerHTML += `
                                <button class="btn btn-sm btn-outline-secondary" onclick="loadRequestHistory(${data.currentPage + 1})">
                                    Next
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
            
            // Add success modal cleanup
            const successModalElement = document.getElementById('successModal');
            successModalElement.addEventListener('hidden.bs.modal', cleanupModal);
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
                <div class="modal-body p-4">
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
                                    <select name="role" class="form-control rounded-3" id="role" required>
                                        <option value="room_attendant">Room Attendant</option>
                                        <option value="linen_attendant">Linen Attendant</option>

                                    </select>
                                    <label for="role">Employee Role</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <input type="number" name="quantity" class="form-control rounded-3" id="quantity" required>
                                    <label for="quantity">Number of Employees Needed</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <textarea name="reason" class="form-control rounded-3" id="reason" style="height: 100px;" required></textarea>
                                    <label for="reason">Reason for Request</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <select name="preferred_shift" class="form-control rounded-3" id="preferred_shift" required>
                                        <option value="morning">Morning (6AM - 2PM)</option>
                                        <option value="afternoon">Afternoon (2PM - 10PM)</option>
                                        <option value="night">Night (10PM - 6AM)</option>
                                    </select>
                                    <label for="preferred_shift">Preferred Schedule/Shift</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <select name="urgency_level" class="form-control rounded-3" id="urgency_level" required>
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                    <label for="urgency_level">Urgency Level</label>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" name="requestEmployee" class="btn btn-success btn-sm rounded-pill px-4 py-2">Submit Request</button>
                                </div>
                            </form>
                        </div>

                        <!-- Request History Tab -->
                        <div class="tab-pane fade" id="request-history">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Request ID</th>
                                            <th>Role</th>
                                            <th>Quantity</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>HR Response</th>
                                        </tr>
                                    </thead>
                                    <tbody id="requestHistoryBody">
                                        <!-- Data will be loaded here -->
                                    </tbody>
                                </table>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <small class="text-muted">Showing 10 records per page</small>
                                    <div class="btn-group" id="paginationControls">
                                        <!-- Pagination buttons will be added here -->
                                    </div>
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
                <div class="modal-body p-4">
                    <form action="" method="POST">
                        <div class="form-floating mb-3">
                            <input type="text" name="name" class="form-control rounded-3" id="name" required>
                            <label for="name">Employee Name</label>
                        </div>
                        <div class="form-floating mb-3">
                            <select name="status" class="form-control rounded-3" id="status" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                            <label for="status">Status</label>
                        </div>
                        <div class="form-floating mb-3">
                            <select name="role" class="form-control rounded-3" id="role" required>
                                <option value="housekeeper">Housekeeper</option>
                                <option value="room_attendant">Room Attendant</option>
                                <option value="linen_attendant">Linen Attendant</option>
                            </select>
                            <label for="role">Employee Role</label>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" name="addEmployee" class="btn btn-success btn-sm rounded-pill px-4 py-2">Add Employee</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="successModalLabel">Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="emailResponseMessage">
                        <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                        <p class="mb-0">Request has been successfully added!</p>
                        <p class="fw-bold fs-5 mb-0" id="requestDetails"></p>
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
                            <p><strong>Status:</strong> <span class="badge bg-${data.status === 'Active' ? 'success' : 'secondary'}">${data.status}</span></p>
                        </div>
                    `;

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
            if(confirm('Are you sure you want to delete this employee? This action cannot be undone.')) {
                fetch('remove_employee.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({emp_id: empId})
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        // Close the modal first
                        const modal = bootstrap.Modal.getInstance(document.getElementById('employeeDetailsModal'));
                        if (modal) {
                            modal.hide();
                        }
                        
                        // Show success message and reload
                        alert('Employee successfully removed.');
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to remove employee.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while removing the employee.');
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
                if (data.success) {
                    // Get selected role and quantity for display
                    const roleSelect = document.getElementById('role');
                    const roleText = roleSelect.options[roleSelect.selectedIndex].text;
                    const quantity = document.getElementById('quantity').value;
                    
                    // Set details in success modal
                    document.getElementById('requestDetails').textContent = `${quantity} ${roleText}(s)`;
                    
                    // Set content in the email response message
                    document.getElementById('emailResponseMessage').innerHTML = `
                        <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                        <p class="mb-0">Request has been successfully added!</p>
                        <p class="fw-bold fs-5 mb-0">${quantity} ${roleText}(s)</p>
                    `;
                    
                    // Hide request modal
                    const requestModal = bootstrap.Modal.getInstance(document.getElementById('requestEmployeeModal'));
                    requestModal.hide();
                    
                    // Show success modal
                    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();
                    
                    // Set timeout to auto-close the success modal after 2 seconds
                    setTimeout(() => {
                        successModal.hide();
                        // Ensure proper cleanup after modal is hidden
                        document.body.classList.remove('modal-open');
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) {
                            backdrop.parentNode.removeChild(backdrop);
                        }
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                    }, 2000);
                    
                    form.reset();
                    loadRequestHistory();
                } else {
                    alert(data.message || 'An error occurred while submitting the request.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting the request.');
            });
        }

        function cleanupModal() {
            // More robust cleanup function
            setTimeout(() => {
                document.body.classList.remove('modal-open');
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => {
                    if (backdrop && backdrop.parentNode) {
                        backdrop.parentNode.removeChild(backdrop);
                    }
                });
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }, 100); // Short delay to ensure modal has finished transition
        }

        // Update modal event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const modalElement = document.getElementById('requestEmployeeModal');
            modalElement.addEventListener('hidden.bs.modal', cleanupModal);
            
            // Fix success modal cleanup
            const successModalElement = document.getElementById('successModal');
            successModalElement.addEventListener('hidden.bs.modal', cleanupModal);
            
            // Global handler to ensure backdrop is removed if it persists
            document.addEventListener('click', function() {
                const body = document.body;
                if (!document.querySelector('.modal.show') && body.classList.contains('modal-open')) {
                    cleanupModal();
                }
            });
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

                    // Generate pagination controls
                    paginationControls.innerHTML = '';
                    if (data.totalPages > 1) {
                        // Previous button
                        if (data.currentPage > 1) {
                            paginationControls.innerHTML += `
                                <button class="btn btn-sm btn-outline-secondary" onclick="loadRequestHistory(${data.currentPage - 1})">
                                    Previous
                                </button>
                            `;
                        }

                        // Page numbers
                        for (let i = 1; i <= data.totalPages; i++) {
                            if (i === data.currentPage) {
                                paginationControls.innerHTML += `
                                    <button class="btn btn-sm btn-secondary">${i}</button>
                                `;
                            } else {
                                paginationControls.innerHTML += `
                                    <button class="btn btn-sm btn-outline-secondary" onclick="loadRequestHistory(${i})">${i}</button>
                                `;
                            }
                        }

                        // Next button
                        if (data.currentPage < data.totalPages) {
                            paginationControls.innerHTML += `
                                <button class="btn btn-sm btn-outline-secondary" onclick="loadRequestHistory(${data.currentPage + 1})">
                                    Next
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
            
            // Add success modal cleanup
            const successModalElement = document.getElementById('successModal');
            successModalElement.addEventListener('hidden.bs.modal', cleanupModal);
        });
        </script>

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
