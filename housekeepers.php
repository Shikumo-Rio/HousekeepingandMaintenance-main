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
            <h3>Housekeeping Staff</h3>
        </div>
        <div class="housekeeper-btn">
            <button class="btn" data-bs-toggle="modal" data-bs-target="#requestEmployeeModal">
                <i class="fa-solid fa-plus"></i>Request Employee
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
    </div>

    <!-- Updated Modal with Role Selection -->
    <div class="modal fade" id="requestEmployeeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Request New Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="" method="POST">
                        <div class="form-floating mb-3">
                            <select name="role" class="form-control rounded-3" id="role" required>
                                <option value="housekeeper">Housekeeper</option>
                                <option value="room_attendant">Room Attendant</option>
                                <option value="linen_attendant">Linen Attendant</option>
                            </select>
                            <label for="role">Employee Role</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="number" name="quantity" class="form-control rounded-3" id="quantity" required>
                            <label for="quantity">Quantity</label>
                        </div>
                        <div class="form-floating mb-3">
                            <textarea name="reason" class="form-control rounded-3" id="reason" style="height: 100px;" required></textarea>
                            <label for="reason">Reason</label>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" name="requestEmployee" class="btn btn-success btn-sm rounded-pill px-4 py-2">Submit</button>
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
