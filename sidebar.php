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
    /* Sidebar base styling */
    #sidebar {
        position: sticky;
        top: 0;
        height: 100vh;
        z-index: 1000;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease-in-out;
        background-color: #f8f9fa; /* Lighter background for light mode */
        overflow-y: auto;
        width: 250px; /* Default expanded width */
    }
    
    /* Collapsed sidebar styling - Increased width */
    #sidebar.collapsed {
        width: 100px; /* Further increased from 90px for better icon visibility */
        min-width: 100px; /* Prevent further shrinking */
        overflow-x: hidden;
        margin-left: 0 !important; /* Ensure it's never hidden */
        display: block !important; /* Force display */
        visibility: visible !important; /* Ensure visibility */
        opacity: 1 !important; /* Ensure opacity */
    }
    
    /* Adjust main content when sidebar is collapsed */
    .wrapper {
        display: flex;
    }
    
    .main {
        flex: 1;
        transition: all 0.3s ease-in-out;
        margin-left: 0; /* Let the sidebar width control the spacing */
    }
    
    /* Show/hide text in sidebar based on collapsed state */
    #sidebar .sidebar-text {
        opacity: 1;
        transition: opacity 0.3s ease;
        white-space: nowrap;
    }
    
    #sidebar.collapsed .sidebar-text {
        opacity: 0;
        display: none;
        width: 0;
    }
    
    /* Adjust sidebar logo in collapsed state */
    #sidebar.collapsed .sidebar-logo a {
        display: none;
    }
    
    #sidebar.collapsed .sidebar-logo {
        justify-content: center;
        padding: 1rem 0;
    }
    
    #sidebar.collapsed .sidebar-logo img {
        margin-right: 0;
    }
    
    /* Center icons in collapsed mode - Enhanced for better visibility */
    #sidebar.collapsed .sidebar-link {
        justify-content: center;
        padding: 1rem 0.5rem;
        margin: 0.5rem auto;
        width: 85%;
        border-radius: 6px;
        text-align: center;
        display: flex;
        align-items: center;
    }
    
    #sidebar.collapsed .sidebar-link i {
        font-size: 1.5rem; /* Larger icons for better visibility */
        margin: 0 auto;     /* Center icons horizontally */
        padding: 0;
        display: block !important; /* Force icons to display */
        visibility: visible !important;
        opacity: 1 !important;
        width: auto; /* Remove fixed width that might cause issues */
    }
    
    /* Fix padding in collapsed mode */
    #sidebar.collapsed .sidebar-item {
        padding: 0;
        display: block;
    }
    
    /* Make sure parent links still show icons in collapsed state */
    #sidebar.collapsed .sidebar-link.collapsed i {
        display: block !important;
        visibility: visible !important;
    }
    
    /* Improve header spacing in collapsed mode */
    #sidebar.collapsed .sidebar-header {
        text-align: center;
        padding: 1rem 0;
        margin-bottom: 0.5rem;
    }
    
    /* Hide dropdown carets and adjust spacing in collapsed mode */
    #sidebar.collapsed .fa-caret-down {
        display: none;
    }
    
    /* Show tooltips on hover in collapsed mode */
    #sidebar.collapsed .sidebar-link {
        position: relative;
    }
    
    #sidebar.collapsed .sidebar-link:hover::after {
        content: attr(data-title);
        position: absolute;
        left: 100px; /* Adjust to match new width */
        top: 50%;
        transform: translateY(-50%);
        padding: 0.75rem 1rem;
        font-weight: 500;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
        border-radius: 4px;
        z-index: 1050;
    }
    
    [data-bs-theme="dark"] #sidebar.collapsed .sidebar-link:hover::after {
        background: #212529;
        color: #ffffff;
    }
    
    /* Hide nested dropdowns in collapsed mode */
    #sidebar.collapsed .sidebar-dropdown {
        display: none !important;
    }
    
    /* Dark mode styles for sidebar */
    [data-bs-theme="dark"] #sidebar {
        background-color: #212529;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
    }
    
    /* Sidebar logo section - Light mode */
    .sidebar-logo {
        padding: 1.2rem 1rem;
        display: flex;
        align-items: center;
        background-color: #e9ecef; /* Slightly darker than sidebar background */
        border-bottom: 1px solid #dee2e6;
    }
    
    .sidebar-logo img {
        margin-right: 0.8rem;
    }
    
    .sidebar-logo a {
        font-size: 1.2rem;
        font-weight: 600;
        color: #000000 !important; /* Force black text for logo */
        text-decoration: none;
    }
    
    /* Dark mode overrides for logo section */
    [data-bs-theme="dark"] .sidebar-logo {
        background-color: #2c3034;
        border-bottom: 1px solid #343a40;
    }
    
    [data-bs-theme="dark"] .sidebar-logo a {
        color: #ffffff !important; /* Force white text for dark mode */
    }
    
    /* Sidebar navigation */
    .sidebar-nav {
        padding: 0;
        list-style: none;
        margin-top: 0.5rem;
    }
    
    /* Header text - Light mode */
    .sidebar-header {
        font-size: 0.9rem;
        padding: 1rem 1.5rem 0.5rem;
        color: #000000 !important; /* Force pure black for headers */
        font-weight: 600;
        text-transform: uppercase;
    }
    
    /* Dark mode overrides for header */
    [data-bs-theme="dark"] .sidebar-header {
        color: #ffffff !important; /* Force pure white for dark mode */
    }
    
    .sidebar-item {
        margin-bottom: 0.2rem;
    }
    
    /* Make sure all sidebar items have black text in light mode */
    #sidebar .sidebar-item, 
    #sidebar .sidebar-item a,
    #sidebar .sidebar-dropdown li,
    #sidebar .sidebar-dropdown a {
        color: #000000 !important; /* Force black text for all sidebar elements */
    }
    
    /* Ensure dark mode shows white text */
    [data-bs-theme="dark"] #sidebar .sidebar-item, 
    [data-bs-theme="dark"] #sidebar .sidebar-item a,
    [data-bs-theme="dark"] #sidebar .sidebar-dropdown li,
    [data-bs-theme="dark"] #sidebar .sidebar-dropdown a {
        color: #ffffff !important; /* Force white text for all elements in dark mode */
    }
    
    /* Link styling - Light mode */
    .sidebar-link {
        text-decoration: none;
        padding: 0.8rem 1.5rem;
        display: flex;
        align-items: center;
        color: #000000 !important; /* Force pure black text with !important */
        border-left: 3px solid transparent;
        transition: all 0.2s ease;
    }
    
    /* Light mode hover effect */
    .sidebar-link:hover,
    .sidebar-link:focus {
        text-decoration: none;
        background-color: #e8f5e9; /* Light green background */
        color: #2e7d32 !important; /* Dark green text */
        border-left-color: #2e7d32; /* Dark green border */
    }
    
    /* Light mode active state */
    .sidebar-link.active {
        text-decoration: none;
        background-color: #c8e6c9; /* Slightly darker green background */
        color: #2e7d32; /* Dark green text */
        border-left-color: #2e7d32; /* Dark green border */
        font-weight: 500; /* Make active link slightly bolder */
    }
    
    /* Dark mode overrides for links */
    [data-bs-theme="dark"] .sidebar-link {
        color: #ffffff !important; /* Force pure white for dark mode */
    }
    
    [data-bs-theme="dark"] .sidebar-link:hover,
    [data-bs-theme="dark"] .sidebar-link:focus {
        background-color: #2c3e2e; /* Dark green background for dark mode */
        color: #ffffff; /* Keep text white on hover */
        border-left-color: #81c784; /* Light green border for dark mode */
    }
    
    [data-bs-theme="dark"] .sidebar-link.active {
        background-color: #1b2e1e; /* Darker green background for dark mode */
        color: #ffffff; /* Keep text white when active */
        border-left-color: #81c784; /* Light green border for dark mode */
        font-weight: 500; /* Keep the bold weight for active items */
    }
    
    /* Dropdown items */
    .sidebar-dropdown {
        padding-left: 0;
        background-color: #f0f2f5; /* Slightly darker background for nested items */
    }
    
    /* Dark mode dropdown background */
    [data-bs-theme="dark"] .sidebar-dropdown {
        background-color: #1e2125;
    }
    
    /* For dropdown text specifically */
    .sidebar-dropdown .sidebar-link {
        color: #000000 !important; /* Force black for dropdown items */
    }
    
    [data-bs-theme="dark"] .sidebar-dropdown .sidebar-link {
        color: #ffffff !important; /* Force white for dropdown items in dark mode */
    }
    
    /* Icons color enhancement for light mode */
    .sidebar-link i {
        color: #000000 !important; /* Force black color for icons */
        width: 24px; /* Fixed width for better alignment */
    }
    
    .sidebar-link:hover i,
    .sidebar-link.active i {
        color: #2e7d32 !important; /* Force dark green for icons on hover/active */
    }
    
    [data-bs-theme="dark"] .sidebar-link i {
        color: #ffffff !important; /* Force white icons for dark mode */
    }
    
    [data-bs-theme="dark"] .sidebar-link:hover i,
    [data-bs-theme="dark"] .sidebar-link.active i {
        color: #a5d6a7 !important; /* Force light green for icons on hover/active in dark mode */
    }
    
    /* Sidebar scrollbar styling */
    #sidebar::-webkit-scrollbar {
        width: 5px;
    }
    
    #sidebar::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    #sidebar::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }
    
    #sidebar::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    
    /* Responsive behavior */
    @media (max-width: 768px) {
        #sidebar {
            margin-left: -250px; /* This hides the sidebar on small screens */
            position: fixed; /* Make it fixed position on mobile */
            height: 100%;
        }
        
        /* When collapsed or active, show the sidebar even on mobile */
        #sidebar.collapsed,
        #sidebar.active {
            margin-left: 0 !important; /* Force showing the sidebar */
            width: 100px; /* Maintain collapsed width */
        }
    }
</style>

<aside id="sidebar" class="js-sidebar">
    <!-- Content For Sidebar -->
    <div class="h-100">
        <div class="sidebar-logo">
          <img src="img/logo.webp" style="width: 75px;">
          <a href="#" class="sidebar-text">Paradise Hotel</a>
        </div>
        <ul class="sidebar-nav">
            <li class="sidebar-header">Admin <span class="sidebar-text">Elements</span></li>
            <li class="sidebar-item">
                <a href="dashboard.php" class="sidebar-link" data-title="Dashboard">
                    <i class="fa-solid fa-list pe-2"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="#" class="sidebar-link collapsed" data-bs-target="#housekeeping" data-bs-toggle="collapse" aria-expanded="false" data-title="Room Service">
                    <i class="fa-solid fa-bell-concierge pe-1" aria-hidden="true"></i>
                    <span class="sidebar-text">Room Service</span>
                </a>
                <ul id="housekeeping" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                    <li class="sidebar-item">
                        <a href="roomservice.php" class="sidebar-link ms-4" data-title="Housekeeping Panel">
                            <i class="fa-solid fa-clipboard-list pe-2" aria-hidden="true"></i>
                            <span class="sidebar-text">Housekeeping Panel</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="guest.php" class="sidebar-link ms-4" data-title="Guest Requests">
                            <i class="fas fa-bullhorn pe-2" aria-hidden="true"></i>
                            <span class="sidebar-text">Guest Requests</span>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="sidebar-item">
                <a href="task_allocation.php" class="sidebar-link" data-title="AI Task Allocation">
                    <i class="fas fa-tasks pe-2"></i>
                    <span class="sidebar-text">AI Task Allocation</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="housekeepers.php" class="sidebar-link" data-title="Housekeepers">
                    <i class="fa-solid fa-people-group pe-1"></i>
                    <span class="sidebar-text">Housekeepers</span>
                </a>
            </li>
            
            <li class="sidebar-item">
                <a href="#" class="sidebar-link collapsed" data-bs-target="#posts" data-bs-toggle="collapse" aria-expanded="false" data-title="Maintenance">
                    <i class="fa fa-wrench pe-2" aria-hidden="true"></i>
                    <span class="sidebar-text">Maintenance</span>
                </a>
                <ul id="posts" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                    <li class="sidebar-item">
                        <a href="maintenance.php" class="sidebar-link ms-4" data-title="Requests">
                            <i class="fas fa-hand-paper pe-2" aria-hidden="true"></i>
                            <span class="sidebar-text">Requests</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="maintenance_requests.php" class="sidebar-link ms-4" data-title="All request">
                            <i class="fas fa-list-alt pe-2" aria-hidden="true"></i>
                            <span class="sidebar-text">All request</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="sidebar-item">
                <a href="#" class="sidebar-link collapsed" data-bs-target="#lostFound" data-bs-toggle="collapse" aria-expanded="false" data-title="Lost and Found">
                    <i class="fa fa-box pe-2" aria-hidden="true"></i>
                    <span class="sidebar-text">Lost and Found</span>
                </a>
                <ul id="lostFound" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                    <li class="sidebar-item ms-4">
                        <a href="lostfoundItems.php#lost" class="sidebar-link" data-title="Lost Items">
                            <i class="fas fa-question-circle pe-2" aria-hidden="true"></i>
                            <span class="sidebar-text">Lost Items</span>
                        </a>
                    </li>
                    <li class="sidebar-item ms-4">
                        <a href="lostfoundItems.php#found" class="sidebar-link" data-title="Found Items">
                            <i class="fas fa-hand-holding pe-2" aria-hidden="true"></i>
                            <span class="sidebar-text">Found Items</span>
                        </a>
                    </li>
                    <li class="sidebar-item ms-4">
                        <a href="lostfoundItems.php#claimed" class="sidebar-link" data-title="Claim/Return">
                            <i class="fas fa-handshake pe-2" aria-hidden="true"></i>
                            <span class="sidebar-text">Claim/Return</span>
                        </a>
                    </li>
                </ul>
            </li>     
            <li class="sidebar-item">
                <a href="#" class="sidebar-link collapsed" data-bs-target="#inventory" data-bs-toggle="collapse" aria-expanded="false" data-title="Inventory">
                    <i class="fa fa-warehouse pe-2" aria-hidden="true"></i>
                    <span class="sidebar-text">Inventory</span>
                </a>
                <ul id="inventory" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                    <li class="sidebar-item ms-4">
                        <a href="inventory.php" class="sidebar-link" data-title="Housekeeping">
                            <i class="fas fa-house-user pe-2"></i>
                            <span class="sidebar-text">Housekeeping</span>
                        </a>
                    </li>
                    <li class="sidebar-item ms-4">
                        <a href="request_inventory.php" class="sidebar-link" data-title="Request Inventory">
                            <i class="fas fa-boxes pe-2"></i>
                            <span class="sidebar-text">Request Inventory</span>
                        </a>
                    </li>
                    <li class="sidebar-item ms-4">
                        <a href="laundry.php" class="sidebar-link" data-title="Laundry">
                            <i class="fas fa-tshirt pe-2"></i>
                            <span class="sidebar-text">Laundry</span>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Original tab handling code
    const sidebarLinks = document.querySelectorAll('.sidebar-link[data-target]');
    
    sidebarLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            const targetTab = e.target.getAttribute('data-target');
            const navTab = document.querySelector(`.nav-link[data-tab="${targetTab}"]`);
            if (navTab) {
                navTab.click();
            }
        });
    });
    
    const allSidebarLinks = document.querySelectorAll('.sidebar-link');
    allSidebarLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            allSidebarLinks.forEach(function(l) {
                l.classList.remove('active');
            });
            this.classList.add('active');
        });
    });
    
    const currentPath = window.location.pathname.split('/').pop();
    if (currentPath) {
        const currentLink = document.querySelector('.sidebar-link[href="' + currentPath + '"]');
        if (currentLink) {
            currentLink.classList.add('active');
            const parentDropdown = currentLink.closest('.sidebar-dropdown');
            if (parentDropdown) {
                const collapseItem = parentDropdown.previousElementSibling;
                if (collapseItem && collapseItem.classList.contains('collapsed')) {
                    collapseItem.click();
                }
            }
        }
    }
    
    // Updated sidebar toggle functionality - use the navbar button
    const sidebarToggleBtn = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const main = document.querySelector('.main');
    
    // Check if the sidebar toggle button exists (it should be in navbar.php)
    if (sidebarToggleBtn && sidebar) {
        // Load saved sidebar state
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        
        // Function to handle sidebar toggling
        function toggleSidebar(collapsed) {
            if (collapsed) {
                sidebar.classList.add('collapsed');
                // Make sure it's visible
                sidebar.style.display = 'block';
                sidebar.style.visibility = 'visible';
                sidebar.style.width = '100px';
            } else {
                sidebar.classList.remove('collapsed');
                sidebar.style.width = '250px';
            }
            localStorage.setItem('sidebarCollapsed', collapsed);
        }
        
        // Apply saved state on load
        toggleSidebar(sidebarCollapsed);
        
        // Add click event to toggle sidebar
        sidebarToggleBtn.addEventListener('click', function() {
            const isCollapsed = !sidebar.classList.contains('collapsed');
            toggleSidebar(isCollapsed);
        });
    }
});
</script>