<?php
require_once 'database.php'; 

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
    <div class="p-4 mb-4 task-allocation-heading card">
        <div class="d-flex justify-content-between align-items-center">
            <h3>AI Task Allocation</h3>
            <!-- Settings Icon to Trigger Modal -->
            <button class="btn btn-success btn-sm d-flex align-items-center justify-content-center" 
                    style="width: 40px; height: 40px; border-radius: 50%; border: none; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);" 
                    data-bs-toggle="modal" data-bs-target="#settingsModal">
                <i class="bi bi-gear fs-4 text-white mb-2"></i> <!-- Green icon and white text -->
            </button>

        </div>
    </div>
    <div class="row mt-4 m-0 mb-4">
    <!-- Card 1: Total Housekeepers -->
    <div class="col-md-6">
        <div class="card-with-line p-3 text-center card">
            <h5 class="card-title">Total Housekeepers</h5>
            <div class="d-flex justify-content-center align-items-center">
                <i class="bi bi-people-fill text-success fs-4 me-2"></i>
                <h4 class="text-success mb-0"><?php echo $totalEmployees; ?></h4>
            </div>
        </div>  
    </div>
    <!-- Card 2: Active Housekeepers -->
    <div class="col-md-6">
        <div class="card-with-line p-3 text-center card">
            <h5 class="card-title">Active Housekeepers</h5>
            <div class="d-flex justify-content-center align-items-center">
                <i class="bi bi-person-check-fill text-success fs-4 me-2"></i> <!-- Larger icon -->
                <h4 class="text-success mb-0"><?php echo $activeEmployees; ?></h4>
            </div>
        </div>
    </div>
</div>


    <!-- Allocated Tasks Section -->
    <div class="p-4 task-allocation-heading card">
        <div class="d-flex justify-content-between align-items-center">
            <h3>Logs</h3>
            <div class="dropdown">
                <button class="btn btn-success dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-file-export"></i>Export
                </button>
                <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                    <li><a class="dropdown-item" href="#" onclick="exportLogs('excel'); return false;"><i class="bi bi-file-excel me-2"></i>Excel</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportLogs('pdf'); return false;"><i class="bi bi-file-pdf me-2"></i>PDF</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="container"> <!-- Added container for margin -->
    <?php
echo "<div class='card border-0 shadow-sm mb-3'>
        <div class='card-body p-2'>
        <h5 class='card-title mb-4 mt-4 m-2'>All Task Logs</h5>";

// Search and filter form
echo "<div class='row mb-3'>
        <div class='col-md-4'>
            <div class='dropdown'>
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
echo "      </ul>
            </div>
        </div>
        <div class='col-md-4'>
            <input type='date' id='dateFilter' class='form-control'>
        </div>
        <div class='col-md-4'>
            <input type='text' id='searchInput' class='form-control' placeholder='Search...'>
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
    echo "<div class='table-responsive'>
            <table class='table table-hover' id='logsTable'>
            <thead class='sticky-top'>
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

    // Add pagination controls
    echo "<nav aria-label='Page navigation' class='mt-3'>
            <ul class='pagination justify-content-center'>";
    
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $page === $i ? 'active' : '';
        echo "<li class='page-item $active'>
                <a class='page-link' href='?page=$i'>$i</a>
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="settingsModalLabel">Assign Housekeepers to Floors</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Settings Form -->
                    <form>
                        <div class="mb-4">
                            <label for="housekeeperSelect" class="form-label">Select Housekeeper</label>
                            <select class="form-select" id="housekeeperSelect">
                                <option value="" disabled selected>Select Housekeeper</option>
                                <option value="housekeeper1">Housekeeper 1</option>
                                <option value="housekeeper2">Housekeeper 2</option>
                                <option value="housekeeper3">Housekeeper 3</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="floorSelect" class="form-label">Assign Floor</label>
                            <select class="form-select" id="floorSelect" multiple>
                                <option value="floor1">Floor 1</option>
                                <option value="floor2">Floor 2</option>
                                <option value="floor3">Floor 3</option>
                                <option value="floor4">Floor 4</option>
                                <option value="floor5">Floor 5</option>
                            </select>
                            <div class="form-text text-muted">
                                Hold down the "Ctrl" (Windows) or "Command" (Mac) key to select multiple floors.
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="shiftTime" class="form-label">Assign Shift Time</label>
                            <input type="time" class="form-control" id="shiftTime" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" id="saveSettingsButton">Save Settings</button>
                </div>
            </div>
        </div>
    </div>
    
<!-- Date Range Modal for Export -->
<div class="modal fade" id="dateRangeModal" tabindex="-1" aria-labelledby="dateRangeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dateRangeModalLabel">Select Date Range for Export</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm">
                    <input type="hidden" id="exportFormat" name="format" value="excel">
                    
                    <div class="mb-3">
                        <label for="startDate" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="startDate" name="start" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="endDate" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="endDate" name="end" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmExport">Export</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="../js/jquery.min.js"></script>
<script type="text/javascript" src="../js/bootstrap.min.js"></script>
<script src="js/script.js"></script>
<script type="text/javascript" src="../js/jquery.dataTables.min.js"></script>
<script>
function exportLogs(format) {
    // Set the export format in the hidden field
    document.getElementById('exportFormat').value = format;
    
    // Show the date range modal
    var dateRangeModal = new bootstrap.Modal(document.getElementById('dateRangeModal'));
    dateRangeModal.show();
    
    // Handle the export confirmation
    document.getElementById('confirmExport').onclick = function() {
        const form = document.getElementById('exportForm');
        const formData = new FormData(form);
        const searchParams = new URLSearchParams(formData);
        
        // Add any existing filters from the current page
        const currentUrl = new URL(window.location.href);
        if (currentUrl.searchParams.has('emp_id')) {
            searchParams.set('emp_id', currentUrl.searchParams.get('emp_id'));
        }
        
        // Redirect to the export page
        window.location.href = 'func/export_logs.php?' + searchParams.toString();
        
        // Close the modal
        dateRangeModal.hide();
    };
}
</script>
</body>
</html>
