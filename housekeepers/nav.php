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
        <!-- Custom notification dropdown (not using Bootstrap) -->
        <div class="custom-dropdown">
            <div class="notification-icon" id="notificationIcon">
                <i class="bi bi-bell" style="font-size: 24px; cursor: pointer;"></i>
                <span class="notification-badge" id="notificationCount">0</span>
            </div>
            <div class="dropdown-content" id="notificationDropdown">
                <div class="dropdown-header">Notifications</div>
                <div class="dropdown-divider"></div>
                <div class="dropdown-items" id="notificationItems">
                    <div class="no-notifications" id="noNotifications">No new notifications</div>
                </div>
            </div>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
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

    <!-- Add custom styles for the dropdown -->
    <style>
        .custom-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .notification-icon {
            position: relative;
            display: inline-block;
            padding: 8px;
        }
        
        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            display: none;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #f9f9f9;
            min-width: 300px;
            max-height: 400px;
            overflow-y: auto;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1000;
            border-radius: 4px;
        }
        
        .dropdown-header {
            padding: 12px 16px;
            font-weight: bold;
            background-color: #f1f1f1;
            position: sticky;
            top: 0;
        }
        
        .dropdown-divider {
            height: 1px;
            background-color: #e5e5e5;
        }
        
        .dropdown-items {
            padding: 8px 0;
        }
        
        .dropdown-item {
            padding: 10px 16px;
            text-decoration: none;
            display: block;
            color: #333;
            cursor: pointer;
        }
        
        .dropdown-item:hover {
            background-color: #f1f1f1;
        }
        
        .no-notifications {
            padding: 16px;
            text-align: center;
            color: #6c757d;
        }
        
        .dropdown-item.unread {
            font-weight: bold;
            background-color: rgba(13, 110, 253, 0.05);
        }
        
        .notification-time {
            font-size: 0.8rem;
            color: #6c757d;
            display: block;
            margin-top: 4px;
        }
        
        /* Show the dropdown when active */
        .dropdown-content.show {
            display: block;
        }
    </style>

    <!-- Replace Bootstrap dropdown JavaScript with custom implementation -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const notificationIcon = document.getElementById('notificationIcon');
            const notificationDropdown = document.getElementById('notificationDropdown');
            const notificationCount = document.getElementById('notificationCount');
            const notificationItems = document.getElementById('notificationItems');
            const notificationSound = new Audio('../housekeepers/notification.mp3');
            
            // Toggle dropdown visibility when clicking the notification icon
            notificationIcon.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationDropdown.classList.toggle('show');
                
                // If opening the dropdown, load notifications
                if (notificationDropdown.classList.contains('show')) {
                    loadNotifications();
                }
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!notificationDropdown.contains(e.target) && 
                    !notificationIcon.contains(e.target)) {
                    notificationDropdown.classList.remove('show');
                }
            });
            
            // Load notifications function
            function loadNotifications() {
                console.log('Loading notifications...');
                
                fetch('get_notifications.php')
                    .then(response => response.json())
                    .then(data => {
                        console.log('Notification data:', data);
                        
                        // Process notifications
                        const notifications = data.notifications || [];
                        const unreadCount = data.unread_count || 0;
                        
                        // Update notification count badge
                        if (unreadCount > 0) {
                            notificationCount.textContent = unreadCount;
                            notificationCount.style.display = 'inline-block';
                        } else {
                            notificationCount.style.display = 'none';
                        }
                        
                        // Clear current notifications
                        notificationItems.innerHTML = '';
                        
                        // Add notifications or "no notifications" message
                        if (notifications.length > 0) {
                            notifications.forEach(notification => {
                                const item = document.createElement('a');
                                item.className = `dropdown-item ${notification.is_read ? '' : 'unread'}`;
                                item.href = notification.link || '#';
                                item.setAttribute('data-id', notification.id);
                                
                                item.innerHTML = `
                                    <div>
                                        <div>${notification.message}</div>
                                        <small class="notification-time">${formatDate(notification.created_at)}</small>
                                    </div>
                                `;
                                
                                // Add click event to mark as read
                                item.addEventListener('click', function(e) {
                                    if (this.href === '#' || this.href === window.location.href + '#') {
                                        e.preventDefault();
                                    }
                                    
                                    const id = this.getAttribute('data-id');
                                    markAsRead(id);
                                });
                                
                                notificationItems.appendChild(item);
                            });
                        } else {
                            const noItems = document.createElement('div');
                            noItems.className = 'no-notifications';
                            noItems.textContent = 'No new notifications';
                            notificationItems.appendChild(noItems);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching notifications:', error);
                        
                        // Show error message
                        notificationItems.innerHTML = 
                            '<div class="no-notifications" style="color: #dc3545;">Error loading notifications</div>';
                    });
            }
            
            // Format date for display
            function formatDate(dateString) {
                const date = new Date(dateString);
                if (isNaN(date.getTime())) {
                    return dateString; // Return as-is if invalid
                }
                
                const now = new Date();
                const diffMs = now - date;
                const diffSec = Math.floor(diffMs / 1000);
                const diffMin = Math.floor(diffSec / 60);
                const diffHour = Math.floor(diffMin / 60);
                const diffDay = Math.floor(diffHour / 24);
                
                if (diffSec < 60) return 'Just now';
                if (diffMin < 60) return `${diffMin} min ago`;
                if (diffHour < 24) return `${diffHour} h ago`;
                return `${diffDay} day(s) ago`;
            }
            
            // Mark notification as read
            function markAsRead(id) {
                console.log('Marking notification as read:', id);
                
                fetch(`mark_notification_read.php?id=${id}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Mark as read response:', data);
                    if (data.success) {
                        loadNotifications(); // Reload notifications
                    }
                })
                .catch(error => {
                    console.error('Error marking notification as read:', error);
                });
            }
            
            // WebSocket for real-time notifications
            try {
                const socket = new WebSocket('ws://localhost:8080/chat');
                
                socket.onopen = function() {
                    console.log('WebSocket connection established');
                };
                
                socket.onmessage = function(event) {
                    console.log('WebSocket message received:', event.data);
                    try {
                        const data = JSON.parse(event.data);
                        
                        if (data.type === 'notification' || 
                            (data.type === 'task_assignment' && 
                             data.assigned_to === '<?php echo $_SESSION['username']; ?>')) {
                            
                            // Play notification sound
                            notificationSound.play().catch(e => console.log('Sound error:', e));
                            
                            // Reload notifications
                            loadNotifications();
                        }
                    } catch (error) {
                        console.error('Error processing WebSocket message:', error);
                    }
                };
                
                socket.onerror = function(error) {
                    console.error('WebSocket error:', error);
                };
                
                socket.onclose = function(event) {
                    console.log('WebSocket connection closed:', event.code, event.reason);
                };
            } catch (error) {
                console.error('Error setting up WebSocket:', error);
            }
            
            // Initial load of notifications
            loadNotifications();
            
            // Set up periodic refresh - every 30 seconds
            setInterval(loadNotifications, 30000);

            // Expose markAsRead to window object to allow calls from HTML
            window.markAsRead = markAsRead;
        });
    </script>
</body>
</html>
