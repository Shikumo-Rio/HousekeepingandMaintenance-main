document.addEventListener("DOMContentLoaded", function() {
    // Apply saved theme on page load
    applyTheme();
    
    // Theme toggle functionality
    const themeToggle = document.querySelector(".theme-toggle");
    
    if (themeToggle) {
        themeToggle.addEventListener("click", function(e) {
            e.preventDefault();
            toggleTheme();
        });
    }
    
    // Apply saved sidebar state
    applySidebarState();
});

// Function to toggle between light and dark themes
function toggleTheme() {
    const htmlElement = document.querySelector("html");
    const currentTheme = htmlElement.getAttribute("data-bs-theme");
    const newTheme = (currentTheme === "dark") ? "light" : "dark";
    
    // Set the new theme
    htmlElement.setAttribute("data-bs-theme", newTheme);
    
    // Save theme preference to localStorage
    localStorage.setItem("preferredTheme", newTheme);
}

// Function to apply the saved theme on page load
function applyTheme() {
    const htmlElement = document.querySelector("html");
    const savedTheme = localStorage.getItem("preferredTheme");
    
    // If there's a saved theme preference, apply it
    if (savedTheme) {
        htmlElement.setAttribute("data-bs-theme", savedTheme);
    }
}

// Function to apply saved sidebar state
function applySidebarState() {
    const sidebar = document.getElementById('sidebar');
    
    if (!sidebar) return;
    
    // Load saved sidebar state
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (sidebarCollapsed) {
        sidebar.classList.add('collapsed');
    }
}

// Function to reinitialize or refresh the dropdowns
function refreshDropdowns() {
    // Bootstrap Dropdown re-initialization
    const dropdownElements = document.querySelectorAll('.dropdown-toggle');
    dropdownElements.forEach(dropdown => {
        new bootstrap.Dropdown(dropdown);
    });
}

function filterTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('inventoryTable');
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) { // Start from 1 to skip the header row
        const td = tr[i].getElementsByTagName('td');
        let found = false;

        for (let j = 1; j < td.length - 1; j++) { // Check item name and category
            if (td[j]) {
                const txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toLowerCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        tr[i].style.display = found ? '' : 'none'; // Show or hide the row
    }
}




