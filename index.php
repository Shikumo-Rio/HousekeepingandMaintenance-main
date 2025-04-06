<?php
require_once 'database.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
if ($_SESSION['user_type'] !== 'Admin') {
    // Redirect to unauthorized access page or admin dashboard
    header("Location: unauthorized.php"); // You can create this page
    exit;
}

// Continue with the index page content if logged in
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://kit.fontawesome.com/ae360af17e.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="img/logo.webp">
    <link rel="manifest" href="/housekeepingandmaintenance-main/manifest.json">
    <meta name="theme-color" content="#007bff">
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/housekeepingandmaintenance-main/service-worker.js')
                .then(() => console.log('Service Worker Registered'))
                .catch((error) => console.error('Service Worker Registration Failed:', error));
        }
    </script>
</head>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="main">
            <?php include 'navbar.php';  ?>
            <!-- main content -->
            <div class="theme-toggle-wrapper" data-bs-toggle="tooltip" data-bs-placement="left" title="Toggle theme">
                <a href="#" class="theme-toggle">
                    <div class="theme-toggle-icon">
                        <i class="fa-solid fa-moon moon-icon"></i>
                        <i class="fa-solid fa-sun sun-icon"></i>
                    </div>
                </a>
            </div>
            
            <!-- Styling for enhanced theme toggle -->
            <style>
                .theme-toggle-wrapper {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    z-index: 1000;
                }
                
                .theme-toggle {
                    display: block;
                    width: 56px;
                    height: 56px;
                    border-radius: 50%;
                    text-decoration: none;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
                    overflow: hidden;
                    transition: all 0.3s ease;
                }
                
                /* Light mode toggle styling */
                html[data-bs-theme="light"] .theme-toggle {
                    background: linear-gradient(40deg, #ffd86f, #fc6262);
                }
                
                /* Dark mode toggle styling */
                html[data-bs-theme="dark"] .theme-toggle {
                    background: linear-gradient(40deg, #2b3244, #1a1e2d);
                }
                
                .theme-toggle-icon {
                    width: 100%;
                    height: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    position: relative;
                }
                
                .theme-toggle i {
                    position: absolute;
                    font-size: 1.5rem;
                    transition: all 0.3s ease;
                }
                
                /* Sun icon styling */
                .sun-icon {
                    color: #ff9500;
                    transform: translateY(0);
                    opacity: 1;
                }
                
                /* Moon icon styling */
                .moon-icon {
                    color: #c4d4ff;
                    transform: translateY(0);
                    opacity: 1;
                }
                
                /* Hide and animate sun icon in dark mode */
                html[data-bs-theme="dark"] .sun-icon {
                    transform: translateY(40px);
                    opacity: 0;
                }
                
                /* Hide and animate moon icon in light mode */
                html[data-bs-theme="light"] .moon-icon {
                    transform: translateY(-40px);
                    opacity: 0;
                }
                
                /* Hover effect */
                .theme-toggle:hover {
                    transform: scale(1.1);
                }
            </style>
            <!-- footer content -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>    
            <script src="js/script.js"></script>
            
            <script>
                // Initialize Bootstrap tooltips
                document.addEventListener('DOMContentLoaded', function() {
                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    });
                });
            </script>
</body>

</html>
