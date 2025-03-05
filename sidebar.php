<?
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

?>

<style>
    .sidebar-link {
    text-decoration: none; /* Remove underline */
}

.sidebar-link:hover,
.sidebar-link:focus {
    text-decoration: none; /* No underline on hover or focus */
}

/* Prevent underline on collapsed links */
.sidebar-item a.collapsed {
    text-decoration: none; /* Remove underline from collapsed links */
}

/* Remove underline for active links */
.sidebar-link.active {
    text-decoration: none; /* Remove underline for active links */
}
</style>
<aside id="sidebar" class="js-sidebar">
    <!-- Content For Sidebar -->
    <div class="h-100">
        <div class="sidebar-logo">
          <img src="img/logo.webp" style="width: 75px;">
          <a href="#">Paradise Hotel</a>
        </div>
        <ul class="sidebar-nav">
            <li class="sidebar-header">Admin Elements</li>
            <li class="sidebar-item">
                <a href="dashboard.php" class="sidebar-link">
                    <i class="fa-solid fa-list pe-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="sidebar-item">
                <a href="roomservice.php" class="sidebar-link">
                    <i class="fas fa-concierge-bell pe-2"></i> <!-- Added a bell icon for Room Service -->
                    Room Service
                </a>
            </li>
            <li class="sidebar-item">
                <a href="task_allocation.php" class="sidebar-link">
                    <i class="fas fa-tasks pe-2"></i> <!-- Added a tasks icon for Task Allocation -->
                    Task Allocation
                </a>
            </li>
            <li class="sidebar-item">
                <a href="housekeepers.php" class="sidebar-link">
                    <i class="fa-solid fa-people-group pe-1 "></i><!-- Bootstrap person-fill icon -->
                    Housekeepers
                </a>
            </li>
            <li class="sidebar-item">
                <a href="#" class="sidebar-link collapsed" data-bs-target="#posts" data-bs-toggle="collapse" aria-expanded="false">
                    <i class="fa fa-wrench pe-2" aria-hidden="true"></i>
                    Maintenance
                </a>
                <ul id="posts" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                    <li class="sidebar-item">
                        <a href="maintenance.php" class="sidebar-link">Requests</a>
                    </li>
                    <li class="sidebar-item">
                        <a href="maintenance_requests.php" class="sidebar-link">All request</a>
                    </li>
                </ul>
            </li>
            <li class="sidebar-item">
                <a href="#" class="sidebar-link collapsed" data-bs-target="#lostFound" data-bs-toggle="collapse" aria-expanded="false">
                    <i class="fa fa-box pe-2" aria-hidden="true"></i>
                    Lost and Found
                </a>
                <ul id="lostFound" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                    <li class="sidebar-item">
                        <a href="lostfoundItems.php#lost" class="sidebar-link" data-target="#lost">Lost Items</a>
                    </li>
                    <li class="sidebar-item">
                        <a href="lostfoundItems.php#found" class="sidebar-link" data-target="#found">Found Items</a>
                    </li>
                    <li class="sidebar-item">
                        <a href="lostfoundItems.php#claimed" class="sidebar-link" data-target="#claimed">Claim/Return</a>
                    </li>
                </ul>
            </li>     
            <li class="sidebar-item">
                <a href="inventory.php" class="sidebar-link">
                <i class="fa fa-warehouse pe-2" aria-hidden="true"></i>
                     Inventory
                </a>
            </li>
            <!-- <li class="sidebar-header">Settings</li>
            <li class="sidebar-item">
                <a href="#" class="sidebar-link collapsed" data-bs-target="#multi" data-bs-toggle="collapse" aria-expanded="false">
                    <i class="fa-solid fa-share-nodes pe-2"></i>
                    Multi Dropdown
                </a>
                <ul id="multi" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                    <li class="sidebar-item">
                        <a href="#" class="sidebar-link collapsed" data-bs-target="#level-1" data-bs-toggle="collapse" aria-expanded="false">Level 1</a>
                        <ul id="level-1" class="sidebar-dropdown list-unstyled collapse">
                            <li class="sidebar-item">
                                <a href="#" class="sidebar-link">Level 1.1</a>
                            </li>
                            <li class="sidebar-item">
                                <a href="#" class="sidebar-link">Level 1.2</a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </li> -->
        </ul>
    </div>
</aside>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Select all sidebar links with data-target
    const sidebarLinks = document.querySelectorAll('.sidebar-link[data-target]');
    
    // Loop through each link and add a click event listener
    sidebarLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            // Get the target tab from the data-target attribute
            const targetTab = e.target.getAttribute('data-target');
            
            // Find the corresponding nav-tab link and trigger a click on it
            const navTab = document.querySelector(`.nav-link[data-tab="${targetTab}"]`);
            if (navTab) {
                navTab.click(); // Programmatically click the tab
            }
        });
    });
});
</script>