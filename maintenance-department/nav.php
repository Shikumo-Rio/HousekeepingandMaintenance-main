<?php
require_once '../database.php';

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php"); // Redirect to login if not logged in
    exit;
}

if ($_SESSION['user_type'] !== 'Maintenance') {
    // Redirect to unauthorized access page or admin dashboard
    header("Location: ../unauthorized.php"); // You can create this page
    exit;
}

// Get user details from database
$username = $_SESSION['username'];
$sql = "SELECT emp_id, username FROM login_accounts WHERE username = ? AND user_type = 'Maintenance'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsive Sidebar with Bootstrap 5</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <!-- Navbar -->
    <div class="navbar fixed-top d-flex justify-content-between align-items-center p-2 bg-light shadow-sm">
        <div class="hamburger-logo-wrapper" id="hamburgerLogoWrapper" onclick="toggleSidebar()">
            <div class="hamburger">&#9776;</div> <!-- Hamburger icon -->
            <!-- Replace the text with an image -->
            <img src="logo.webp" alt="Logo" class="logo" style="width: 40px; height: auto;"> <!-- Logo next to Hamburger -->
        </div>
        <div class="notification">
            <i class="bi bi-bell" style="font-size: 24px;"></i>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="profile">
            <img src="../img/profile.jpg" alt="Profile Picture" class="profile-img">
            <div class="mt-2"><?php echo htmlspecialchars($user['username']); ?></div>
            <div class="text-secondary">Employee ID: <?php echo htmlspecialchars($user['emp_id']); ?></div>
        </div>
        <ul class="nav flex-column">
        <li class="nav-item">
          <a class="nav-link text-black" href="maintenance.php">
            <i class="fas fa-tools"></i> Maintenance Requests
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-black" href="#">
            <i class="fas fa-file-alt"></i> Requests
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-black" href="#">
            <i class="fas fa-cogs"></i> Settings
          </a>
        </li>
        <li class="nav-item">
        <a class="nav-link text-black" href="../logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </li>
      </ul>
    </div> 

    <!-- Overlay -->
    <div class="overlay" id="overlay"></div>
    <!-- Content -->
    <div id="content"></div>


    <!-- JavaScript -->
    <script>
        // Toggle the sidebar and apply overlay effects
        function toggleSidebar() {
            var sidebar = document.getElementById("sidebar");
            var content = document.getElementById("content");
            var overlay = document.getElementById("overlay");
            var body = document.body;
            var hamburgerLogoWrapper = document.getElementById("hamburgerLogoWrapper");

            // Toggle the sidebar visibility
            sidebar.classList.toggle("active");
            content.classList.toggle("active");
            hamburgerLogoWrapper.classList.toggle("active"); // Move the hamburger and logo

            // Add dimming effect on the content by adding the dimmed class
            content.classList.toggle("active");

            // Show/hide the overlay when the sidebar is active only on mobile
            if (window.innerWidth < 769) { // Check for mobile view
                overlay.classList.toggle("active");
            }

            // Prevent body from scrolling when sidebar is open
            if (sidebar.classList.contains("active")) {
                body.classList.add("no-scroll");
            } else {
                body.classList.remove("no-scroll");
            }
        }

        // Hide the hamburger when the modal is opened and show it when closed
        var hamburgerWrapper = document.getElementById("hamburgerLogoWrapper");

        var modals = document.querySelectorAll('.modal');
        modals.forEach(function(modal) {
            modal.addEventListener('shown.bs.modal', function () {
                hamburgerWrapper.style.display = "none"; // Hide hamburger
            });

            modal.addEventListener('hidden.bs.modal', function () {
                hamburgerWrapper.style.display = "flex"; // Show hamburger
            });
        });

        // Close the sidebar if the user clicks outside of it (on the overlay)
        document.getElementById("overlay").addEventListener("click", toggleSidebar);
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
