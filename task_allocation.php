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
            <h3>Task Allocation</h3>
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
        <h3>Logs </h3>
    </div>
    <div class="container"> <!-- Added container for margin -->
    <?php
echo "<div class='card border-0 shadow-sm mb-3'>
        <div class='card-body p-2'>
        <h5 class='card-title mb-4 mt-4 m-2'>All Task Logs</h5>";

// Query to fetch all task logs
$logsQuery = "SELECT log_id, task_id, emp_id, action, change_details, log_time FROM task_logs";
$logsResult = $conn->query($logsQuery);

if ($logsResult->num_rows > 0) {
    echo "<div class='table-responsive'>
            <table class='table table-hover'>
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
} else {
    echo "<p class='mb-0'>No task logs found.</p>";
}

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
    
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/bootstrap.min.js"></script>
     <script src="js/script.js"></script>
     <script type="text/javascript" src="../js/jquery.dataTables.min.js"></script>
</body>
</html>
