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

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // Set up pending login session for verification
            $_SESSION['pending_login'] = true;
            $_SESSION['pending_username'] = $user['username'];
            $_SESSION['pending_user_id'] = $user['emp_id'];
            $_SESSION['pending_user_type'] = $user['user_type'];

            // Debug log to see what's happening
            file_put_contents(__DIR__ . '/login_debug.txt', date('[Y-m-d H:i:s] ') . "Redirecting to login controller for user: {$user['username']}\n", FILE_APPEND);

            // Redirect to the login controller instead of directly to verification
            header("Location: login_controller.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Invalid username or password.";
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
}

// If we have a verification error 
if (isset($_GET['face_error'])) {
    $error = "Either there is no registered face or the face verification failed. Please try again.";
}
if (isset($_GET['otp_error'])) {
    $error = "OTP verification failed. Please try again.";
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
    <link rel="manifest" href="/housekeepingandmaintenance-main/manifest.json">
    <meta name="theme-color" content="#007bff">
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/housekeepingandmaintenance-main/service-worker.js')
                .then(() => console.log('Service Worker Registered'))
                .catch((error) => console.error('Service Worker Registration Failed:', error));
        }
    </script>
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

        /* Ensure the install button is visible above the background */
        .install-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999; /* High z-index to ensure visibility */
        }

        #installButton {
            background-color: #007bff; /* Button background color */
            color: #fff; /* Button text color */
            border: none;
            border-radius: 25px;
            padding: 10px 20px;
            font-size: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        #installButton:hover {
            background-color: #0056b3; /* Darker blue on hover */
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="form-container">
            <img src="img/logo.webp" alt="Paradise Logo" class="logo">
            <h3 class="text-center text-light mb-4">Welcome</h3>
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
                <?php if (!empty($error)): ?>
                    <div class="text-danger mb-3 text-center"><?php echo $error; ?></div>
                <?php endif; ?>
                <button type="submit" name="login" class="btn btn-green text-light w-100">LOGIN</button>
                
                <!-- Install button -->
                <div class="mt-3 text-center">
                    <button id="installButton" class="btn btn-primary" style="display: none;">
                        <i class="bi bi-download"></i> Install App
                    </button>
                </div>
            </form>

            <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true" data-bs-backdrop="false" data-bs-keyboard="true">
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

        let deferredPrompt;

        // Listen for the beforeinstallprompt event
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('beforeinstallprompt event fired'); // Debug log
            // Prevent the mini-infobar from appearing
            e.preventDefault();
            // Save the event for later use
            deferredPrompt = e;
            // Show the install button
            const installButton = document.getElementById('installButton');
            installButton.style.display = 'block';
            console.log('Install button displayed'); // Debug log

            // Add click event to the install button
            installButton.addEventListener('click', () => {
                console.log('Install button clicked'); // Debug log
                // Show the install prompt
                deferredPrompt.prompt();
                // Wait for the user's response
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                    } else {
                        console.log('User dismissed the install prompt');
                    }
                    // Clear the deferred prompt
                    deferredPrompt = null;
                });
            });
        });

        // Hide the install button if the app is already installed
        window.addEventListener('appinstalled', () => {
            console.log('PWA installed'); // Debug log
            document.getElementById('installButton').style.display = 'none';
        });
    </script>

    <!-- Bootstrap JS and Icons JS -->
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
