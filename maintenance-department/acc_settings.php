<?php
require_once '../database.php'; // Make sure session is started

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php"); // Redirect to login if not logged in
    exit;
}

if ($_SESSION['user_type'] !== 'Maintenance') {
    // Redirect to unauthorized access page or admin dashboard
    header("Location: ../unauthorized.php"); // You can create this page
    exit;
}


$username = $_SESSION['username'];
$user_type = $_SESSION['user_type'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 col-sm-12">
                <!-- Profile Section -->
                <div class="text-center">
                    <!-- Use a default image if the profile picture doesn't exist -->
                    <img src="../image/profile.jpg" alt="Profile Picture" class="profile-img mt-4">
                    <h2 class="mt-3"><?php echo htmlspecialchars($username); ?></h2>
                    <p class="text-secondary"><?php echo htmlspecialchars($user_type); ?></p>
                </div>

                <!-- Change Password Form -->
                <div class="change-password">
                    <h4>Change Password</h4>
                    <form action="change_password.php" method="POST">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary float-end">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
