<?php

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
        /* Notification Bell */
        .nav-icon {
            color: #495057; /* Subtle color */
        }

        .nav-icon:hover {
            color: #007bff; /* Change color on hover */
        }

        /* Notification Count Badge */
        #notification-count {
            position: absolute;
            top: 0;
            right: 0;
            transform: translate(25%, -25%);
            font-size: 0.8rem;
            padding: 4px 8px;
            background-color: #ff5252;
            color: white;
            display: none; /* Initially hidden */
            border-radius: 50%;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
        }

        /* Notification Dropdown */
        .notification-dropdown {
            min-width: 320px; /* Set width for dropdown */
            border-radius: 12px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
            border: none;
            background: #f8f9fa; /* Light background */
            max-height: 400px;
            overflow-y: auto;
            position: relative;
        }

        .notification-dropdown p.dropdown-item {
            font-size: 0.8rem;
            color: #6c757d;
            margin: 0; 
            padding: 10px;
        }

        .notification-dropdown a.dropdown-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: background 0.3s ease;
            margin: 0;        }

        .notification-dropdown a.dropdown-item:hover {
            background-color: #e9ecef;
        }

        /* Notification Icon */
        .notification-icon {
            width: 35px;
            height: 35px;
            background-color: green;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            margin-right: 10px;
        }

        /* Notification Text */
        .notification-content {
            flex-grow: 1;
        }

        .notification-title {
            font-size: 0.7rem;
            font-weight: 600;
            color: #333;
        }

        .notification-time {
            font-size: 0.7rem;
            color: #6c757d;
        }

        /* Scrollbar customization */
        .notification-dropdown::-webkit-scrollbar {
            width: 8px;
        }

        .notification-dropdown::-webkit-scrollbar-thumb {
            background-color: #ced4da;
            border-radius: 10px;
        }

        /* Center the header text */
        .dropdown-header {
            text-align: center;
            font-weight: bold;   /* Make the header bold */
            font-size: 0.95rem;   /* Adjust the font size */
        }

    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css" integrity="sha512-5Hs3dF2AEPkpNAR7UiOHba+lRSJNeM2ECkwxUIxC1Q/FLycGTbNapWXB4tP889k5T5Ju8fs4b1P5z/iB4nMfSQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <nav class="navbar navbar-expand px-3 border-bottom">
        <button class="btn" id="sidebar-toggle" type="button">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="navbar-collapse navbar">
            <ul class="navbar-nav ms-auto">
                <!-- Notification Bell -->
                <li class="nav-item dropdown me-3">
                    <a href="#" class="nav-icon position-relative" id="notificationDropdown" data-bs-toggle="dropdown">
                        <i class="bi bi-bell" style="font-size: 1.5rem;"></i>
                        <span class="badge bg-danger rounded-circle" id="notification-count">0</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end p-3 notification-dropdown" aria-labelledby="notificationDropdown">
                        <div id="notification-list">
                            
                        </div>
                    </div>
                </li>
                
                <!-- User Profile Dropdown -->
                <li class="nav-item dropdown">
                    <a href="#" data-bs-toggle="dropdown" class="nav-icon pe-md-0">
                        <img src="image/profile.jpg" class="avatar img-fluid rounded" alt="">
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a href="profile.php" class="dropdown-item">Profile</a>
                        <a href="#" class="dropdown-item">Setting</a>
                        <a href="logout.php" class="dropdown-item">Logout</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

<script>
document.addEventListener('DOMContentLoaded', function () {
    fetch('notification.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(notifications => {
            console.log('Received notifications:', notifications); // Log notifications for debugging
            const notificationCount = notifications.length;
            const notificationCountElement = document.getElementById('notification-count');
            const notificationListElement = document.getElementById('notification-list');

            // Calculate relative time (e.g., 'Just now', '1 hour ago')
            function timeAgo(notificationTime) {
                const now = new Date();  // Current time
                const notificationDate = new Date(notificationTime);  // Parse notification time
                
                // Debugging: Log the times
                console.log('Now:', now);
                console.log('Notification Date:', notificationDate);

                // Check if the date is valid
                if (isNaN(notificationDate.getTime())) {
                    console.log('Invalid notification date:', notificationTime);  // Debugging line
                    return 'Invalid date';  // Handle invalid date
                }

                const diffInSeconds = Math.floor((now - notificationDate) / 1000);  // Time difference in seconds
                const minutes = Math.floor(diffInSeconds / 60);
                const hours = Math.floor(minutes / 60);
                const days = Math.floor(hours / 24);

                if (diffInSeconds < 60) return 'Just now';
                if (minutes < 60) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
                if (hours < 24) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
                return `${days} day${days > 1 ? 's' : ''} ago`;
            }

            notifications.forEach(notification => {
                console.log('Notification time (raw):', notification.created_at);
                console.log('Notification time (parsed):', new Date(notification.created_at));
            });


            if (notificationCount > 0) {
                notificationCountElement.textContent = notificationCount;
                notificationCountElement.style.display = 'inline-block';

                notificationListElement.innerHTML = '';

                // Add a header for the notifications
                const header = document.createElement('div');
                header.className = 'dropdown-header';
                header.textContent = 'Notifications';
                notificationListElement.appendChild(header);

                notifications.forEach(notification => {
                    const notificationItem = document.createElement('a');
                    notificationItem.href = notification.link; // Ensure this is correct
                    notificationItem.className = 'dropdown-item';

                    // Create notification icon
                    const notificationIcon = document.createElement('div');
                    notificationIcon.className = 'notification-icon';
                    notificationIcon.innerHTML = '<i class="bi bi-bell"></i>';

                    // Create notification content
                    const notificationContent = document.createElement('div');
                    notificationContent.className = 'notification-content';

                    const notificationTitle = document.createElement('div');
                    notificationTitle.className = 'notification-title';
                    notificationTitle.textContent = notification.message;

                    // Get the relative time using the correct timestamp
                    const notificationTimeText = timeAgo(notification.created_at); // Use created_at here

                    const notificationTime = document.createElement('div');
                    notificationTime.className = 'notification-time';
                    notificationTime.textContent = notificationTimeText;

                    notificationContent.appendChild(notificationTitle);
                    notificationContent.appendChild(notificationTime);

                    // Append icon and content to the notification item
                    notificationItem.appendChild(notificationIcon);
                    notificationItem.appendChild(notificationContent);

                    notificationListElement.appendChild(notificationItem);
                });
            } else {
                notificationListElement.innerHTML = '<p class="dropdown-item text-muted">No new notifications</p>';
                notificationCountElement.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error fetching notifications:', error);
        });
});

document.addEventListener('DOMContentLoaded', function () {
    const notificationDropdown = document.querySelector('.notification-dropdown');
    const dropdownHeader = document.querySelector('.dropdown-header');
    let lastScrollTop = 0;

    notificationDropdown.addEventListener('scroll', function () {
        const scrollTop = notificationDropdown.scrollTop;

        if (scrollTop > lastScrollTop) {
            // Scrolling down
            dropdownHeader.classList.add('hidden'); // Hide header
        } else {
            // Scrolling up
            dropdownHeader.classList.remove('hidden'); // Show header
        }

        lastScrollTop = scrollTop; // Update last scroll position
    });
});
</script>
