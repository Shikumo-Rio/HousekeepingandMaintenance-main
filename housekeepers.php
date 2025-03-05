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

// Fetch only employees from the database
$employee = [];
$query = "
    SELECT e.emp_id, e.name, e.status 
    FROM employee e
    INNER JOIN login_accounts l ON e.emp_id = l.emp_id
    WHERE l.user_type = 'employee'
";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $employee[] = $row; // Store each housekeeper in the array
    }
} else {
    echo "Error fetching employees: " . $conn->error;
}

// Adding a new housekeeper and creating a default login
if (isset($_POST['addHousekeeper'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $status = $conn->real_escape_string($_POST['status']);

    // Insert the new employee into the employee table
    $insertEmployee = "INSERT INTO employee (name, status) VALUES ('$name', '$status')";
    
    if ($conn->query($insertEmployee) === TRUE) {
        $emp_id = $conn->insert_id;  // Get the inserted emp_id

        // Insert a default login for the new employee into the login_accounts table
        $defaultPassword = password_hash('paradise', PASSWORD_BCRYPT);  // Hash the default password
        $insertLogin = "INSERT INTO login_accounts (username, password, user_type, emp_id, is_online) 
                        VALUES ('$emp_id', '$defaultPassword', 'employee', '$emp_id', 0)";
        
        if ($conn->query($insertLogin) === TRUE) {
            echo "<script>alert('Housekeeper and login account created successfully!');</script>";
        } else {
            echo "<script>alert('Error creating login account.');</script>";
        }
    } else {
        echo "<script>alert('Error adding housekeeper.');</script>";
    }
}

// Adding a new housekeeper with emp_id
if (isset($_POST['addHousekeeperDetails'])) {
    $emp_id = $conn->real_escape_string($_POST['emp_id']);
    $name = $conn->real_escape_string($_POST['name']);
    $status = $conn->real_escape_string($_POST['status']);

    // Insert the new employee into the employee table
    $insertEmployee = "INSERT INTO employee (emp_id, name, status) VALUES ('$emp_id', '$name', '$status')";
    
    if ($conn->query($insertEmployee) === TRUE) {
        // Insert a default login for the new employee into the login_accounts table
        $defaultPassword = password_hash('paradise', PASSWORD_BCRYPT);  // Hash the default password
        $insertLogin = "INSERT INTO login_accounts (username, password, user_type, emp_id, is_online) 
                        VALUES ('$emp_id', '$defaultPassword', 'Employee', '$emp_id', 0)";
        
        if ($conn->query($insertLogin) === TRUE) {
            echo "<script>alert('Housekeeper and login account created successfully!');</script>";
        } else {
            echo "<script>alert('Error creating login account.');</script>";
        }
    } else {
        echo "<script>alert('Error adding housekeeper.');</script>";
    }
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
        </div>
        <div class="row gx-6 mt-0 m-0">
            <?php foreach ($employee as $housekeeper) : ?>
                <div class="col-md-3 mb-0 mt-4">
                    <div class="card housekeeper-card shadow-sm">
                        <div class="card-body text-center">
                            <!-- Dropdown for options -->
                            <div class="dropdown position-absolute" style="top: 10px; right: 10px;">
                                <button class="btn btn-link text-muted" type="button" id="dropdownMenu<?= $housekeeper['emp_id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenu<?= $housekeeper['emp_id']; ?>">
                                    <li><span class="dropdown-item" onclick="editHousekeeper(<?= $housekeeper['emp_id']; ?>)">Edit</span></li>
                                    <li><span class="dropdown-item text-danger" onclick="deleteHousekeeper(<?= $housekeeper['emp_id']; ?>)">Delete</span></li>
                                </ul>
                            </div>

                            <!-- Icon Placeholder -->
                            <div class="icon">
                                <i class="fas fa-user"></i>
                            </div>

                            <h5 class="card-title"><?= htmlspecialchars($housekeeper['name']); ?></h5>
                            <p class="card-text">Status: <?= htmlspecialchars($housekeeper['status']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="modal fade" id="requestEmployeeModal" tabindex="-1" aria-labelledby="requestEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="requestEmployeeModalLabel">Request New Housekeeper</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="" method="POST">
                        <div class="form-floating mb-3">
                            <input type="number" name="quantity" class="form-control rounded-3" id="quantity" placeholder="Enter Quantity" required>
                            <label for="quantity">Quantity</label>
                        </div>
                        <div class="form-floating mb-3">
                            <textarea name="reason" class="form-control rounded-3" id="reason" placeholder="Enter a reason" style="height: 100px;" required></textarea>
                            <label for="reason">Reason</label>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" name="requestHousekeeperDetails" class="btn btn-success btn-sm rounded-pill px-4 py-2">Submit</button>
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
