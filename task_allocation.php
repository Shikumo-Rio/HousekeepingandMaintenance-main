<?php
require_once 'database.php'; 
require_once 'func/user_logs.php'; // Include the logging function

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
if ($_SESSION['user_type'] !== 'Admin') {
    // Redirect to unauthorized access page or admin dashboard
    header("Location: unauthorized.php"); // You can create this page
    exit;
}
// Query to get the total number of employees (with user_type 'employee')
$totalQuery = "
    SELECT COUNT(*) AS total_employees 
    FROM employee e
    JOIN login_accounts la ON e.emp_id = la.emp_id
    WHERE la.user_type = 'employee'
";
$totalResult = $conn->query($totalQuery);
$totalEmployees = $totalResult->fetch_assoc()['total_employees'];

$activeQuery = "
    SELECT COUNT(*) AS active_employees 
    FROM employee e 
    JOIN login_accounts la ON e.emp_id = la.emp_id 
    WHERE e.status = 'active' AND la.user_type = 'employee'
";
$activeResult = $conn->query($activeQuery);
$activeEmployees = $activeResult->fetch_assoc()['active_employees'];

// Query to get distinct employees who have tasks
$employeesQuery = "SELECT DISTINCT emp_id, emp_name FROM assigntasks";
$employeesResult = $conn->query($employeesQuery);

// Query to get all employees
$employeeQuery = "SELECT emp_id, name, status FROM employee";
$employeeResult = $conn->query($employeeQuery);

// Check if query was successful for task-assigned employees
if (!$employeesResult) {
    die("Query failed: " . $conn->error);
}

// Add these lines before search conditions
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Number of records per page
$offset = ($page - 1) * $limit;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['exportFormat'], $_POST['startDate'], $_POST['endDate'])) {
    $username = $_SESSION['username'];
    $format = $_POST['exportFormat'];
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];

    // Log the export action
    logReportGeneration($conn, $username, 'Task Logs', $format);

    echo json_encode(['success' => true]);
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css" integrity="sha512-5Hs3dF2AEPkpNAR7UiOHba+lRSJNeM2ECkwxUIxC1Q/FLycGTbNapWXB4tP889k5T5Ju8fs4b1P5z/iB4nMfSQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/housekeepertasks.css">
    <link rel="icon" href="img/logo.webp">
    <title>Housekeeping</title>
    
</head>
<body>
    <?php include('index.php'); ?>

    <div class="container mt-2">
        <!-- Task Allocation Section -->
        <div class="p-3 mt-4 mb-4 task-allocation-heading card">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="ms-2">AI Task Allocation</h3>
                <!-- Settings Icon to Trigger Modal -->
                <button class="btn btn-settings btn-sm d-flex align-items-center justify-content-center m-0" 
                        style="width: 40px; height: 40px; border-radius: 50%; border: none;" 
                        data-bs-toggle="modal" data-bs-target="#settingsModal">
                    <i class="bi bi-gear fs-4 mb-2"></i> <!-- Green icon and white text -->
                </button>

            </div>
        </div>
        <div class="row mt-4 m-0 mb-4">
        <!-- Card 1: Total Housekeepers -->
        <div class="col-md-6">
            <div class="card-with-line p-3 text-center card">
                <h5 class="card-title">Total Housekeepers</h5>
                <div class="d-flex justify-content-center align-items-center">
                    <h4 class="text-success mb-0"><?php echo $totalEmployees; ?></h4>
                </div>
            </div>  
        </div>
        <!-- Card 2: Active Housekeepers -->
        <div class="col-md-6">
            <div class="card-with-line p-3 text-center card">
                <h5 class="card-title">Active Housekeepers</h5>
                <div class="d-flex justify-content-center align-items-center">
                    <h4 class="text-success mb-0"><?php echo $activeEmployees; ?></h4>
                </div>
            </div>
        </div>
    </div>


    <!-- Allocated Tasks Section -->
    <div class="p-3 task-allocation-heading card">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="ms-2">Logs</h3>
            <div class="dropdown">
                <button class="btn btn-success-export dropdown-toggle-export" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-file-export"></i>Generate Report
                </button>
                <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                    <li><a class="dropdown-item" href="#" onclick="exportLogs('excel'); return false;">Excel</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportLogs('pdf'); return false;">PDF</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div id="logsContainer">
        <?php
        echo "<div class='card border-0 shadow-sm mb-3 m-0'>
                <div class='d-flex justify-content-between align-items-center m-4'>
                    <h5 class='card-title'>All Task Logs</h5>
                </div>";

        // Search and filter form
        echo "<div class='row mb-3 custom-filter-container'>
                <div class='col-md-4'>
                    <div class='dropdown mx-4 mt-0'>
                        <button class='btn btn-secondary dropdown-toggle w-100' type='button' id='employeeFilter' data-bs-toggle='dropdown' aria-expanded='false'>
                            Filter by Employee
                        </button>
                        <ul class='dropdown-menu w-100' aria-labelledby='employeeFilter'>
                            <li><a class='dropdown-item' href='?'>All Employees</a></li>";
                        
                            // Fetch unique employees from task_logs
                            $empQuery = "SELECT DISTINCT tl.emp_id, e.name 
                                        FROM task_logs tl 
                                        JOIN employee e ON tl.emp_id = e.emp_id 
                                        ORDER BY e.name";
                            $empResult = $conn->query($empQuery);
                            while($emp = $empResult->fetch_assoc()) {
                                $selected = (isset($_GET['emp_id']) && $_GET['emp_id'] == $emp['emp_id']) ? 'active' : '';
                                echo "<li><a class='dropdown-item {$selected}' href='?emp_id={$emp['emp_id']}'>" . 
                                    htmlspecialchars($emp['name']) . "</a></li>";
                            }
                            echo "      
                        </ul>
                    </div>
                </div>
                <div class='col-md-3'>
                    <input type='date' id='dateFilter' class='form-control' placeholder='Select Date'>
                </div>
                <div class='col-md-4 mx-4'>
                    <input type='text' id='searchInput' class='form-control' placeholder='Search by ID...'>
                </div>
            </div>";

            // Remove the search condition from PHP since we'll handle it client-side
            $searchCondition = "";
            if (isset($_GET['date'])) {
                $date = $conn->real_escape_string($_GET['date']);
                $searchCondition .= $searchCondition ? " AND" : " WHERE";
                $searchCondition .= " DATE(log_time) = '$date'";
            }

            if (isset($_GET['emp_id'])) {
                $emp_id = $conn->real_escape_string($_GET['emp_id']);
                $searchCondition .= $searchCondition ? " AND" : " WHERE";
                $searchCondition .= " emp_id = '$emp_id'";
            }

            // Update the query to use proper LIMIT syntax
            $logsQuery = "SELECT log_id, task_id, emp_id, action, change_details, log_time 
                        FROM task_logs" . 
                        ($searchCondition ? $searchCondition : "") . 
                        " ORDER BY log_time DESC LIMIT ?, ?";

            // Use prepared statement to prevent SQL injection
            $stmt = $conn->prepare($logsQuery);
            $stmt->bind_param("ii", $offset, $limit);
            $stmt->execute();
            $logsResult = $stmt->get_result();

            // Get total records for pagination
            $totalQuery = "SELECT COUNT(*) as count FROM task_logs" . ($searchCondition ? $searchCondition : "");
            $totalResult = $conn->query($totalQuery);
            $totalRows = $totalResult->fetch_assoc()['count'];
            $totalPages = ceil($totalRows / $limit);

            if ($logsResult->num_rows > 0) {
                echo "<div class='table-responsive logs-table'>
                        <table class='table table-hover' id='logsTable'>
                        <thead>
                            <tr class='bg-dark text-light'>
                                <th scope='col'>Log ID</th>
                                <th scope='col'>Task ID</th>
                                <th scope='col'>Employee ID</th>
                                <th scope='col'>Action</th>
                                <th scope='col'>Details</th>
                                <th scope='col'>Date</th>
                            </tr>
                        </thead>
                        <tbody>";
                
                while ($logRow = $logsResult->fetch_assoc()) {
                    // Determine badge color based on the action
                    $badgeColor = '';
                    switch (strtolower($logRow['action'])) {
                        case 'assigned':
                            $badgeColor = 'primary'; // Blue
                            break;
                        case 'updated':
                            $badgeColor = 'warning'; // Yellow
                            break;
                        case 'completed':
                            $badgeColor = 'success'; // Green
                            break;
                        case 'canceled':
                            $badgeColor = 'danger'; // Red
                            break;
                        default:
                            $badgeColor = 'secondary'; // Gray
                            break;
                    }

                    echo "<tr>
                            <td>" . htmlspecialchars($logRow['log_id']) . "</td>
                            <td>" . htmlspecialchars($logRow['task_id']) . "</td>
                            <td>" . htmlspecialchars($logRow['emp_id']) . "</td>
                            <td><span class='badge bg-$badgeColor'>" . ucfirst($logRow['action']) . "</span></td>
                            <td>" . htmlspecialchars($logRow['change_details']) . "</td>
                            <td>" . htmlspecialchars($logRow['log_time']) . "</td>
                        </tr>";
                }

                echo "</tbody></table></div>";

            // Define how many page numbers to show
            $limit = 5;
            $start = max(1, $page - floor($limit / 2));
            $end = min($totalPages, $start + $limit - 1);

            // Adjust start if end is less than limit
            if ($end - $start < $limit - 1) {
                $start = max(1, $end - $limit + 1);
            }

            // Add pagination controls
            echo "<nav aria-label='Page navigation' class='mt-3 mb-4'>
                    <ul class='pagination justify-content-center'>";

            // Previous button
            if ($page > 1) {
                echo "<li class='page-item'>
                        <a class='page-link logs-pagination' href='?page=" . ($page - 1) . "'>
                            Previous
                        </a>
                    </li>";
            } else {
                echo "<li class='page-item disabled'>
                        <a class='page-link'>
                            Previous
                        </a>
                    </li>";
            }

            // Display limited page numbers
            for ($i = $start; $i <= $end; $i++) {
                $active = $page === $i ? 'active' : '';
                echo "<li class='page-item $active'>
                        <a class='page-link logs-pagination' href='?page=$i'>$i</a>
                    </li>";
            }

            // Next button
            if ($page < $totalPages) {
                echo "<li class='page-item'>
                        <a class='page-link logs-pagination' href='?page=" . ($page + 1) . "'>
                            Next
                        </a>
                    </li>";
            } else {
                echo "<li class='page-item disabled'>
                        <a class='page-link'>
                            Next 
                        </a>
                    </li>";
            }

            echo "</ul></nav>";

            } else {
                echo "<p class='mb-0'>No task logs found.</p>";
            }


            // Update the JavaScript for maintaining filters
            echo "<script>
                document.getElementById('searchInput').addEventListener('keyup', function() {
                    const searchValue = this.value.toLowerCase();
                    const table = document.getElementById('logsTable');
                    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

                    for (let row of rows) {
                        const cells = row.getElementsByTagName('td');
                        let found = false;
                        
                        for (let cell of cells) {
                            if (cell.textContent.toLowerCase().includes(searchValue)) {
                                found = true;
                                break;
                            }
                        }
                        
                        row.style.display = found ? '' : 'none';
                    }
                });
            document.getElementById('dateFilter').addEventListener('change', function() {
                const dateValue = this.value;
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('date', dateValue);
                if (currentUrl.searchParams.has('emp_id')) {
                    currentUrl.searchParams.set('emp_id', currentUrl.searchParams.get('emp_id'));
                }
                window.location.href = currentUrl.toString();
            });
            </script>";

            echo "</div></div>";
            $conn->close();
            ?>
        </div>
    </div>

    <!-- Settings Modal -->
    <div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-semibold">
                        <i class="bx bx-user-check me-2"></i> Assign Housekeepers to Floors
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body px-4 pb-4">
                    <!-- Settings Form -->
                    <form>
                        <div class="mb-4">
                            <label for="housekeeperSelect" class="form-label fw-bold">Select Housekeeper</label>
                            <select class="form-select rounded-3" id="housekeeperSelect" style="font-size: 12px;">
                                <option value="" disabled selected>Select Housekeeper</option>
                                <option value="housekeeper1">Housekeeper 1</option>
                                <option value="housekeeper2">Housekeeper 2</option>
                                <option value="housekeeper3">Housekeeper 3</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="floorSelect" class="form-label fw-bold">Assign Floor</label>
                            <select class="form-select rounded-3" style="font-size: 12px;" id="floorSelect" multiple>
                                <option value="floor1">Floor 1</option>
                                <option value="floor2">Floor 2</option>
                                <option value="floor3">Floor 3</option>
                                <option value="floor4">Floor 4</option>
                                <option value="floor5">Floor 5</option>
                            </select>
                            <div class="form-text text-muted">
                                Hold down "Ctrl" (Windows) or "Command" (Mac) to select multiple floors.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="shiftTime" class="form-label fw-bold">Assign Shift Time</label>
                            <input type="time" class="form-control rounded-3" style="font-size: 12px;" id="shiftTime" required>
                        </div>
                        <div class="border-0 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary rounded-3 shadow-sm px-2" style="font-size: 12px;" data-bs-dismiss="modal">
                                <i class="bx bx-x me-1"></i> Close
                            </button>
                            <button type="button" class="btn btn-success shadow-sm px-2" style="font-size: 12px;" id="saveSettingsButton">
                                <i class="bx bx-save me-1"></i> Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Date Range Modal for Export -->
    <div class="modal fade" id="dateRangeModal" tabindex="-1" aria-labelledby="dateRangeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="dateRangeModalLabel">Select Export Options</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4">
                    <form id="exportForm">
                        <input type="hidden" id="exportFormat" name="format" value="excel">
                        
                        <div class="mb-3">
                            <label for="startDate" class="form-label fw-bold">Start Date</label>
                            <input type="date" class="form-control rounded-3" id="startDate" name="start" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="endDate" class="form-label fw-bold">End Date</label>
                            <input type="date" class="form-control rounded-3" id="endDate" name="end" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Filter by Action Type</label>
                            <select class="form-select rounded-3" id="actionFilter" name="action">
                                <option value="">All Actions</option>
                                <option value="assigned">Assigned</option>
                                <option value="updated">Updated</option>
                                <option value="completed">Completed</option>
                                <option value="canceled">Canceled</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Export Format</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exportFormatOption" id="exportFormatExcel" value="excel" checked>
                                <label class="form-check-label" for="exportFormatExcel">Excel (.xls)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exportFormatOption" id="exportFormatPDF" value="pdf">
                                <label class="form-check-label" for="exportFormatPDF">PDF</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary px-3 rounded-3" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-success px-3 rounded-3" id="confirmExport">
                        <i class="bx bx-check me-1"></i> Next
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Password Verification Modal -->
    <div class="modal fade" id="passwordVerificationModal" tabindex="-1" aria-labelledby="passwordVerificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="passwordVerificationModalLabel">Admin Verification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4">
                    <p class="mb-3">Please enter your admin password to continue with the export.</p>
                    <div class="mb-3">
                        <label for="adminPassword" class="form-label">Password</label>
                        <input type="password" class="form-control rounded-3" id="adminPassword" placeholder="Enter your password">
                        <div id="passwordError" class="text-danger mt-2" style="display: none;">
                            Incorrect password. Please try again.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary px-3 rounded-3" data-bs-dismiss="modal">
                        <i class="bx bx-x-circle me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-success px-3 rounded-3" id="verifyPasswordBtn">
                        <i class="bx bx-check me-1"></i> Verify & Export
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/bootstrap.min.js"></script>
    <script src="js/script.js"></script>
    <script type="text/javascript" src="../js/jquery.dataTables.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function resetModal() {
            setTimeout(function() {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                $('body').css('padding-right', '');
                $('body').css('overflow', '');
            }, 150);
        }

        function exportLogs(format) {
            // Set the export format in the hidden field
            document.getElementById('exportFormat').value = format;
            
            // Update the radio button to match the selected format
            document.getElementById('exportFormatExcel').checked = (format === 'excel');
            document.getElementById('exportFormatPDF').checked = (format === 'pdf');
            
            // Show the date range modal
            var dateRangeModal = new bootstrap.Modal(document.getElementById('dateRangeModal'));
            dateRangeModal.show();
            
            // Handle the export confirmation
            document.getElementById('confirmExport').onclick = function() {
                const form = document.getElementById('exportForm');
                
                // Update the format based on the selected radio button
                document.getElementById('exportFormat').value = document.querySelector('input[name="exportFormatOption"]:checked').value;
                
                // Store the form data for later use
                window.exportParameters = {
                    format: document.getElementById('exportFormat').value,
                    start: document.getElementById('startDate').value,
                    end: document.getElementById('endDate').value,
                    action: document.getElementById('actionFilter').value
                };
                
                // Close the current modal
                dateRangeModal.hide();
                
                // Open the password verification modal after a short delay
                setTimeout(function() {
                    resetModal();
                    // Clear any previous password input and error message
                    document.getElementById('adminPassword').value = '';
                    document.getElementById('passwordError').style.display = 'none';
                    
                    // Show the password verification modal
                    var passwordModal = new bootstrap.Modal(document.getElementById('passwordVerificationModal'));
                    passwordModal.show();
                }, 300);
            };
        }

        // Setup password verification handlers
        document.getElementById('verifyPasswordBtn').addEventListener('click', function() {
            var password = document.getElementById('adminPassword').value;
            
            if (!password) {
                document.getElementById('passwordError').textContent = 'Password cannot be empty';
                document.getElementById('passwordError').style.display = 'block';
                return;
            }
            
            // Verify the admin password
            fetch('task_allocation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `exportFormat=${encodeURIComponent(window.exportParameters.format)}&startDate=${encodeURIComponent(window.exportParameters.start)}&endDate=${encodeURIComponent(window.exportParameters.end)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Password is correct, proceed with export
                    var passwordModal = bootstrap.Modal.getInstance(document.getElementById('passwordVerificationModal'));
                    passwordModal.hide();
                    
                    // Build the export URL with parameters
                    let url = `func/export_logs.php?format=${window.exportParameters.format}`;
                    
                    if (window.exportParameters.start) 
                        url += `&start=${window.exportParameters.start}`;
                    if (window.exportParameters.end) 
                        url += `&end=${window.exportParameters.end}`;
                    if (window.exportParameters.action) 
                        url += `&action=${window.exportParameters.action}`;
                    
                    // Add employee filter if present in the current URL
                    const currentUrl = new URL(window.location.href);
                    if (currentUrl.searchParams.has('emp_id')) {
                        url += `&emp_id=${currentUrl.searchParams.get('emp_id')}`;
                    }
                    
                    // Add the encryption password
                    url += `&encryption_password=${encodeURIComponent(password)}`;
                    
                    // Open in new window/tab
                    window.open(url, '_blank');
                    
                    // Clean up modal
                    resetModal();
                } else {
                    // Show error message
                    document.getElementById('passwordError').textContent = data.message || 'Invalid password';
                    document.getElementById('passwordError').style.display = 'block';
                }
            })
            .catch(error => {
                document.getElementById('passwordError').textContent = 'Error verifying password. Please try again.';
                document.getElementById('passwordError').style.display = 'block';
                console.error('Error:', error);
            });
        });

        // Allow Enter key to trigger verification
        document.getElementById('adminPassword').addEventListener('keypress', function(e) {
            if (e.which === 13) {
                document.getElementById('verifyPasswordBtn').click();
                e.preventDefault();
            }
        });

        //Pagination with AJAX
        document.addEventListener("DOMContentLoaded", function() {
            // Attach event listener to pagination links
            document.body.addEventListener("click", function(event) {
                if (event.target.classList.contains("logs-pagination")) {
                    event.preventDefault(); // Prevent default link behavior
                    
                    let pageUrl = event.target.getAttribute("href"); // Get page URL
                    
                    fetch(pageUrl, {
                        method: 'GET'
                    })
                    .then(response => response.text())
                    .then(html => {
                        // Extract logs content and update it
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const logsContent = doc.querySelector("#logsContainer").innerHTML;
                        
                        document.getElementById('logsContainer').innerHTML = logsContent;

                        // Replace pagination without scrolling to the top
                        const newPagination = doc.querySelector(".pagination").innerHTML;
                        document.querySelector(".pagination").innerHTML = newPagination;
                    })
                    .catch(error => console.error("Error loading logs:", error));
                }
            });

            // Date validation for export
            var today = new Date();
            var dd = String(today.getDate()).padStart(2, '0');
            var mm = String(today.getMonth() + 1).padStart(2, '0');
            var yyyy = today.getFullYear();
            
            var todayStr = yyyy + '-' + mm + '-' + dd;
            
            // Set max date attribute to today
            $('#endDate').attr('max', todayStr);
            $('#startDate').attr('max', todayStr);
            
            var thirtyDaysAgo = new Date();
            thirtyDaysAgo.setDate(today.getDate() - 30);
            var dd30 = String(thirtyDaysAgo.getDate()).padStart(2, '0');
            var mm30 = String(thirtyDaysAgo.getMonth() + 1).padStart(2, '0');
            var yyyy30 = thirtyDaysAgo.getFullYear();
            
            var thirtyDaysAgoStr = yyyy30 + '-' + mm30 + '-' + dd30;
            $('#startDate').val(thirtyDaysAgoStr);

            // Add event listeners to date inputs to prevent future dates
            $('#startDate, #endDate').on('change', function() {
                var selectedDate = new Date($(this).val());
                
                // If selected date is in the future (after today), reset to today
                if (selectedDate > today) {
                    $(this).val(todayStr);
                    alert("You cannot select a future date");
                }
                
                // Ensure end date isn't before start date
                if ($(this).attr('id') === 'endDate') {
                    var startDate = new Date($('#startDate').val());
                    if (selectedDate < startDate) {
                        $(this).val($('#startDate').val());
                        alert("End date cannot be earlier than start date");
                    }
                }
                
                // Ensure start date isn't after end date
                if ($(this).attr('id') === 'startDate') {
                    var endDate = new Date($('#endDate').val());
                    if (selectedDate > endDate) {
                        $('#endDate').val($(this).val());
                    }
                }
            });
        });
    </script>
</body>
</html>
