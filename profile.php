<?php
require_once 'database.php';
// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Fetch the logged-in user's data from the database
$username = $_SESSION['username'];

$query = "SELECT * FROM login_accounts WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// If user data is not found, redirect to an error page
if (!$user) {
    header("Location: error.php");
    exit;
}

// Handle password change form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate the current password
    if (password_verify($current_password, $user['password'])) {
        // Check if new password and confirmation match
        if ($new_password === $confirm_password) {
            // Hash the new password and update the database
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = ? WHERE username = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ss", $hashed_password, $username);
            $update_stmt->execute();
            $success_message = "Password updated successfully!";
        } else {
            $error = "New password and confirmation do not match.";
        }
    } else {
        $error = "Current password is incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="icon" href="img/logo.webp">
    <style>
        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
        }
        .opacity-low {
            font-size: 0.9em;
            color: white(0, 0, 0, 0.6);
        }
    </style>
</head>
<body>
<?php include 'index.php'; ?>
<div class="container mt-5">
    <h2>User Profile</h2>
    <div class="row">
        <div class="col-md-4">
            <!-- Display User's Profile Picture -->
            <img src="image/profile.jpg" alt="Profile Picture" class="profile-pic">
        </div>
        <div class="col-md-8">
            <!-- Display User's Details -->
            <h4><?php echo htmlspecialchars($user['username']); ?></h4>
            <p class="opacity-low">User Type: <?php echo htmlspecialchars($user['user_type']); ?></p>
        </div>
    </div>

    <hr>

    <!-- Change Password Form -->
    <h4>Change Password</h4>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php elseif (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <form method="POST" action="profile.php">
        <div class="form-group">
            <label for="current_password">Current Password:</label>
            <input type="password" class="form-control" id="current_password" name="current_password" required>
        </div>
        <div class="form-group">
            <label for="new_password">New Password:</label>
            <input type="password" class="form-control" id="new_password" name="new_password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit" name="change_password" class="btn btn-success">Change Password</button>
    </form>
</div>
</body>
</html>
