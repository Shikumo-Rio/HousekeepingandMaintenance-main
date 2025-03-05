<?php
require_once '../database.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit;
}

if ($_SESSION['user_type'] !== 'Employee') {
    // Redirect to unauthorized access page or admin dashboard
    header("Location: ../unauthorized.php"); // You can create this page
    exit;
}

$username = $_SESSION['username'];
$sql = "SELECT user_type FROM login_accounts WHERE username = '$username'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

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
    <link rel="stylesheet" href="../css/housekeeper.css">
    
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="hamburger-logo-wrapper" id="hamburgerLogoWrapper" onclick="toggleSidebar()">
            <div class="hamburger">&#9776;</div> <!-- Hamburger icon -->
            <!-- Replace the text with an image -->
            <img src="../img/logo.webp" alt="Logo" class="logo" style="width: 40px; height: auto;"> <!-- Logo next to Hamburger -->
        </div>
        <div class="notification">
            <i class="bi bi-bell" style="font-size: 24px;"></i> <!-- Bell icon for notifications -->
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="profile">
            <!-- Replace the following line with your profile image URL -->
            <img src="../image/profile.jpg" alt="Profile Picture" class="profile-img mt-4">
            <div class="mt-2"><?php echo htmlspecialchars($username); ?></div>
            <div class="text-secondary"><?php echo htmlspecialchars($user['user_type']); ?></div>
            <hr>
        </div>
        <ul class="nav flex-column">
        <li class="nav-item">
          <a class="nav-link" href="index.php">
            <i class="fa-solid fa-house"></i></i> Home
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="rooms.php">
            <i class="fa-solid fa-bed"></i></i> Rooms
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="mytask.php">
            <i class="fa-solid fa-list-check"></i></i> My tasks
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="acc_settings.php">
            <i class="fas fa-cogs"></i> Settings
          </a>
        </li>
        <li class="nav-item">
        <a class="nav-link" href="../logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </li>
      </ul>
    </div>  

    <!-- Content -->
    <div id="content">

    </div>

    <!-- Overlay -->
    <div class="overlay" id="overlay"></div>

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

        function openNav() {
            document.getElementById("sidebar").style.width = "250px";
            document.getElementById("content").style.marginLeft = "250px";
            }

            function closeNav() {
            document.getElementById("sidebar").style.width = "0";
            document.getElementById("content").style.marginLeft= "0";
            }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
