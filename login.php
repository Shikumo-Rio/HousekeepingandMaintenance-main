<?php
session_start(); // Start the session

// Redirect logged-in users to their respective dashboards
if (isset($_SESSION['username'])) {
    // Check user type to redirect accordingly
    if ($_SESSION['user_type'] == 'Employee') {
        header("Location: /housekeepingandmaintenance-main/housekeepers/index.php"); // Redirect to employee dashboard
    } elseif ($_SESSION['user_type'] == 'Admin') {
        header("Location: dashboard.php"); // Redirect to admin dashboard
    } elseif ($_SESSION['user_type'] == 'maintenance') {
        header("Location: /housekeepingandmaintenance-main/maintenance-department/maintenance.php"); // Redirect to maintenance dashboard
    } elseif ($_SESSION['user_type'] == 'maintenance-staff') {
        header("Location: /housekeepingandmaintenance-main/maintenance-staff/staff.php"); // Redirect to maintenance dashboard
    }
    exit;
}

$error = ''; // Initialize error variable

if (isset($_POST['login'])) {
    require_once 'database.php'; // Ensure this includes your MySQLi connection setup

    $username = $_POST['username'];
    $password = $_POST['password'];

    // Fetch user details from login_accounts table
    $stmt = $conn->prepare("SELECT * FROM login_accounts WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Store session variables
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['emp_id'] = $user['emp_id']; // Store emp_id for later reference

        // Update is_online and last_activity in login_accounts
        $update_stmt = $conn->prepare("UPDATE login_accounts SET is_online = 1, last_activity = NOW() WHERE username = ?");
        $update_stmt->bind_param("s", $username);
        $update_stmt->execute();

        // Set employee status to active in employee table
        $emp_id = $user['emp_id'];
        $update_employee_status = $conn->prepare("UPDATE employee SET status = 'active' WHERE emp_id = ?");
        $update_employee_status->bind_param("i", $emp_id);
        $update_employee_status->execute();

        $logQuery = "INSERT INTO login_logs (emp_id) VALUES (?)";
        $log_stmt = $conn->prepare($logQuery);
        $log_stmt->bind_param("i", $emp_id);
        $log_stmt->execute();

        $notificationQuery = "INSERT INTO notifications (emp_id, message) VALUES (?, '$emp_id have successfully logged in.')";
        $notification_stmt = $conn->prepare($notificationQuery);
        $notification_stmt->bind_param("i", $emp_id);
        $notification_stmt->execute();


        // Redirect based on user type
        if ($user['user_type'] == 'Employee') {
            header("Location: /housekeepingandmaintenance-main/housekeepers/index.php");
        } elseif ($user['user_type'] == 'Admin') {
            header("Location: dashboard.php");
        } elseif ($user['user_type'] == 'Maintenance') {
            header("Location: /housekeepingandmaintenance-main/maintenance-department/maintenance.php");
        } elseif ($user['user_type'] == 'maintenance-staff') {
            header("Location: /housekeepingandmaintenance-main/maintenance-staff/staff.php");
        }
        exit;
    } else {
        $error = "Invalid username or password.";
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paradise Hotel Tomas Morato</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" type="image/webp" sizes="32x32" href="img/logo.webp">
    <link rel="icon" type="image/webp" sizes="16x16" href="img/logo.webp">
    <link rel="stylesheet" href="styles.css">
    <style>
        body,
        html {
            height: 100%;
            background-position: center; 
            background-repeat: no-repeat; 
            background-size: cover;
            background-image: url(img/bgpd.jpg);
        }

        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .form-container {
            width: 348px;
            padding: 20px;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5));
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        .logo {
            display: block;
            margin: 0 auto 23px;
            width: 150px;
        }

        .form-control {
            border-radius: 25px;
            border: 1px solid #28a745;
        }

        .form-control:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
        }

        .btn-green {
            background-color: #28a745;
            border-color: #28a745;
            border-radius: 25px;

        }

        .btn-green:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        @media (max-width: 576px) {
            .form-container {
                padding: 15px;
            }

            .logo {
                width: 150px;
            }
        }

        .input-group .form-control {
            border-radius: 20px;
        }
        .input-group .input-group-text {
            border-radius: 20px;
        }
    </style>

    </style>
</head>
<body>

    <div class="login-container">
        <div class="form-container">
            <img src="img/logo.webp" alt="Paradise Logo" class="logo">
            <h3 class="text-center text-light mb-4">Welcome Admin</h3>
            <form method="POST" action="login.php">
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-key"></i></span> 
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>
                <button type="submit" name="login" class="btn btn-green text-light w-100">LOGIN</button>
            </form>

            <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-m modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="errorModalLabel">Error</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <?php if (!empty($error)): ?>
                                <?php echo $error; ?>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
<!-- Password toggle functionality -->
<script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');

    togglePassword.addEventListener('click', function (e) {
        // Toggle the type of the password field between text and password
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);

        // Toggle the eye icon
        this.querySelector('i').classList.toggle('bi-eye-slash');
    });
</script>
        </div>
    </div>

    <!-- Bootstrap JS and Icons JS for modal functionality and icon toggle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <?php if (!empty($error)): ?>
    <script>
        // Show the error modal
        var errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
        errorModal.show();
    </script>
    <?php endif; ?>
</body>
</html>
