<?php
require_once 'database.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['user_type'] !== 'Admin') {
    header("Location: unauthorized.php");
    exit;
}

// Add handling for direct inventory requests
$successMessage = "";
if (isset($_POST['request_inventory_item'])) {
    $item_id = $_POST['item_id'];
    $item_name = $_POST['item_name'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    
    // API endpoint for submitting requests
    $apiUrl = "https://logistic1.paradisehoteltomasmorato.com/sub-modules/logistic1/warehouse/request.php";

    // Prepare the request data
    $requestData = array(
        'pickup_location' => 'Housekeeping',
        'delivery_location' => 'Housekeeping',
        'requester_name' => $_SESSION['username'],
        'requester_email' => 'paradisehotelmaintenance@gmail.com',
        'contact_number' => '09123456789', // Default contact number 
        'item_type' => 'general',
        'items' => [
            [
                'category' => $category,
                'item_name' => $item_name,
                'sku' => '',
                'quantity' => $quantity
            ]
        ]
    );

    // Initialize cURL session
    $ch = curl_init($apiUrl);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

    // Execute cURL request
    $response = curl_exec($ch);
    
    // Check for errors
    if ($response === false) {
        $successMessage = "Error submitting request: " . curl_error($ch);
    } else {
        $result = json_decode($response, true);
        if (isset($result['status']) && $result['status'] === 'success') {
            $successMessage = "Stock request for {$item_name} submitted successfully!";
        } else {
            $successMessage = "Error: " . ($result['message'] ?? 'Unknown error occurred');
        }
    }
    
    // Close cURL session
    curl_close($ch);
}

// Fetch an admin's emp_id
$adminQuery = "SELECT emp_id FROM login_accounts WHERE user_type = 'admin' LIMIT 1";
$adminResult = $conn->query($adminQuery);
$admin = $adminResult->fetch_assoc();
$emp_id = $admin['emp_id']; // Use this emp_id for notifications

// Fetch inventory items from local database
$sql = "SELECT * FROM inventory";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory</title>
    <!-- Replace the problematic Font Awesome kit with CDN version -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/css/bootstrap.min.css">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/inventory.css"> 
    <link rel="icon" href="img/logo.webp">
</head>
<body>
<?php include 'index.php'; ?>
<div class="container">
    
    <div class="p-4 title-heading card">
        <div class="d-flex justify-content-between align-items-center">
            <h3>Housekeeping Inventory</h3>
            <button class="btn btn-success" onclick="showExportModal()">
                <i class="fas fa-file-export"></i> Export
            </button>
        </div>
    </div>
    
    <?php if (!empty($successMessage)): ?>
    <div class="alert alert-success alert-dismissible fade show" id="success-message">
        <strong>Success!</strong> <?php echo $successMessage; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <div class="row text-center mb-4 pt-4 m-0"> 
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card">
                <div class="underline"></div>
                <div class="card-body">
                    <h5 class="card-title">Total Categories</h5>
                    <h3 class="card-text"><i class="fas fa-tags"></i> <span id="totalSupplies">0</span></h3>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card">
                <div class="underline"></div>
                <div class="card-body">
                    <h5 class="card-title">Available Stock</h5>
                    <h3 class="card-text"><i class="fas fa-cubes"></i> <span id="availableStock">0</span></h3>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card">
                <div class="underline"></div>
                <div class="card-body">
                    <h5 class="card-title">Low Stock Items</h5>
                    <h3 class="card-text"><i class="fas fa-exclamation-triangle"></i> <span id="lowStockItems">0</span></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modified: Two tables side by side -->
    <div class="row m-0 mb-4">
        <!-- Local Inventory Table -->
        <div class="col-lg-7 mb-4">
            <div class="card centered-div">
                <div class="card-body p-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="mb-0"><i class="fas fa-boxes"></i> Inventory</h3>
                        <div class="input-group mb-3">
                            <input type="text" id="localSearchInput" placeholder="Search local inventory..." class="form-control small-input" onkeyup="filterLocalTable()">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                        </div>
                    </div>
                    <table class="table table-hover border table-bordered" id="localInventoryTable">
                        <thead class="striky-top">
                            <tr class="bg-dark text-light">
                                <th scope="col">ID</th>
                                <th scope="col">Category</th>
                                <th scope="col">Item Name</th>
                                <th scope="col">SKU</th>
                                <th scope="col">Quantity</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Local inventory data will be loaded here -->
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-center mt-3">
                        <nav aria-label="Local inventory pagination">
                            <ul class="pagination pagination-sm" id="localInventoryPagination">
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- NEW: Inventory Usage Table - Keeping pagination but removing search -->
        <div class="col-lg-5 mb-4">
            <div class="card centered-div">
                <div class="card-body p-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="mb-0"><i class="fas fa-history"></i> Usage History</h3>
                    </div>
                    <table class="table table-hover border table-bordered" id="inventoryUsageTable">
                        <thead class="striky-top">
                            <tr class="bg-dark text-light">
                                <th scope="col">ID</th>
                                <th scope="col">Task ID</th>
                                <th scope="col">Item</th>
                                <th scope="col">Qty</th>
                                <th scope="col">Used By</th>
                                <th scope="col">Date</th>
                                <th scope="col">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Inventory usage data will be loaded here -->
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-center mt-3">
                        <nav aria-label="Inventory usage pagination">
                            <ul class="pagination pagination-sm" id="usagePagination">
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Request Modal -->
<div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="requestModalLabel">Request Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" id="request_item_id" name="item_id">
                    <input type="hidden" id="request_item_name" name="item_name">
                    <input type="hidden" id="request_category" name="category">
                    
                    <div class="mb-3">
                        <label for="requestItemName" class="form-label">Item Name:</label>
                        <input type="text" class="form-control" id="requestItemName" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="requestItemCategory" class="form-label">Category:</label>
                        <input type="text" class="form-control" id="requestItemCategory" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="requestQuantity" class="form-label">Request Quantity:</label>
                        <input type="number" class="form-control" id="requestQuantity" name="quantity" min="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="request_inventory_item" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">Export Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">What would you like to export?</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="exportType" id="exportTypeInventory" value="inventory" checked>
                        <label class="form-check-label" for="exportTypeInventory">Current Inventory</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="exportType" id="exportTypeUsage" value="usage">
                        <label class="form-check-label" for="exportTypeUsage">Usage History</label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Export Format</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="exportFormat" id="exportFormatExcel" value="excel" checked>
                        <label class="form-check-label" for="exportFormatExcel">Excel (.xls)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="exportFormat" id="exportFormatPDF" value="pdf">
                        <label class="form-check-label" for="exportFormatPDF">PDF</label>
                    </div>
                </div>
                
                <!-- Date range section (only visible for usage history) -->
                <div id="dateRangeSection" style="display: none;">
                    <hr>
                    <h6 class="mb-3">Date Range</h6>
                    
                    <div class="mb-3">
                        <label for="startDate" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="startDate">
                    </div>
                    
                    <div class="mb-3">
                        <label for="endDate" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="endDate">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="exportData()">Export</button>
            </div>
        </div>
    </div>
</div>

<!-- JS and Bootstrap dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
function filterLocalTable() {
    const input = document.getElementById('localSearchInput');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('localInventoryTable');
    const tr = table.getElementsByTagName('tr');
    let noResult = true;

    for (let i = 1; i < tr.length; i++) {
        const td = tr[i].getElementsByTagName('td');
        let found = false;

        for (let j = 1; j < td.length - 1; j++) { // Skip ID and action columns
            if (td[j]) {
                const txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toLowerCase().indexOf(filter) > -1) {
                    found = true;
                    noResult = false;
                    break;
                }
            }
        }
        
        tr[i].style.display = found ? '' : 'none';
    }

    // Handle no results case
    const localNoResultsRow = document.getElementById('localNoResultsRow');
    if (localNoResultsRow) {
        localNoResultsRow.style.display = noResult ? '' : 'none';
    }
}

// Add pagination variables for local inventory
let currentLocalPage = 1;
const localItemsPerPage = 8;

function updatePagination(paginationId, totalItems, currentPage, callback) {
    const totalPages = Math.ceil(totalItems / localItemsPerPage);
    const paginationContainer = document.getElementById(paginationId);
    paginationContainer.innerHTML = '';

    // Add "Previous" button
    const prevBtn = document.createElement('li');
    prevBtn.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prevBtn.innerHTML = `<a class="page-link" href="#" tabindex="-1">&laquo;</a>`;
    if (currentPage !== 1) {
        prevBtn.onclick = () => callback(currentPage - 1);
    }
    paginationContainer.appendChild(prevBtn);

    // Add page numbers
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = document.createElement('li');
        pageBtn.className = `page-item ${i === currentPage ? 'active' : ''}`;
        pageBtn.innerHTML = `<a class="page-link" href="#">${i}</a>`;
        pageBtn.onclick = () => callback(i);
        paginationContainer.appendChild(pageBtn);
    }

    // Add "Next" button
    const nextBtn = document.createElement('li');
    nextBtn.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    nextBtn.innerHTML = `<a class="page-link" href="#">&raquo;</a>`;
    if (currentPage !== totalPages) {
        nextBtn.onclick = () => callback(currentPage + 1);
    }
    paginationContainer.appendChild(nextBtn);
}

// Function to populate local inventory table
async function populateLocalInventoryTable() {
    try {
        const response = await fetch('fetch_local_inventory.php');
        const data = await response.json();
        
        const tableBody = document.querySelector("#localInventoryTable tbody");
        tableBody.innerHTML = '';
        
        if (!data || data.length === 0) {
            tableBody.innerHTML = '<tr id="localNoResultsRow"><td colspan="6" class="text-center">No inventory items found.</td></tr>';
            return;
        }
        
        // Add "No results" row (hidden by default)
        const noResultsRow = document.createElement('tr');
        noResultsRow.id = 'localNoResultsRow';
        noResultsRow.style.display = 'none';
        noResultsRow.innerHTML = '<td colspan="6" class="text-center">No results found.</td>';
        tableBody.appendChild(noResultsRow);
        
        const startIndex = (currentLocalPage - 1) * localItemsPerPage;
        const endIndex = startIndex + localItemsPerPage;
        const itemsToShow = data.slice(startIndex, endIndex);
        
        itemsToShow.forEach(item => {
            const row = document.createElement('tr');
            
            // Add stock level styling
            let stockStyle = '';
            let stockLabel = '';
            if (parseInt(item.quantity) === 0) {
                stockStyle = 'color: red;';
                stockLabel = ' (Out of Stock)';
            } else if (parseInt(item.quantity) < 10) {
                stockStyle = 'color: orange;';
                stockLabel = ' (Low)';
            }
            
            row.innerHTML = `
                <td>${item.id}</td>
                <td>${item.category}</td>
                <td>${item.item_name}</td>
                <td>${item.sku}</td>
                <td style="${stockStyle}">${item.quantity}${stockLabel}</td>
                <td>
                    <button class='btn btn-success btn-sm' title="Request stock" onclick="requestStock(${item.id}, '${item.item_name}', '${item.category}')">
                        <i class='fas fa-shopping-cart'></i>
                    </button>
                </td>
            `;
            
            tableBody.appendChild(row);
        });

        // Update pagination
        updatePagination('localInventoryPagination', data.length, currentLocalPage, (page) => {
            currentLocalPage = page;
            populateLocalInventoryTable();
        });
    } catch (error) {
        console.error("Error fetching local inventory:", error);
        const tableBody = document.querySelector("#localInventoryTable tbody");
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Error loading inventory data.</td></tr>';
    }
}

// Function to request stock
function requestStock(id, name, category) {
    // Set values in the modal
    document.getElementById('request_item_id').value = id;
    document.getElementById('request_item_name').value = name;
    document.getElementById('request_category').value = category;
    document.getElementById('requestItemName').value = name;
    document.getElementById('requestItemCategory').value = category;
    
    // Calculate suggested request quantity
    const suggestedQuantity = 20; // Default quantity
    document.getElementById('requestQuantity').value = suggestedQuantity;
    
    // Show the modal using jQuery for Bootstrap 4
    $('#requestModal').modal('show');
}

// Add pagination variables for inventory usage
let currentUsagePage = 1;
const usageItemsPerPage = 8;

// Function to populate inventory usage table with pagination
async function populateInventoryUsageTable() {
    try {
        const response = await fetch('fetch_inventory_usage.php');
        const data = await response.json();
        
        const tableBody = document.querySelector("#inventoryUsageTable tbody");
        tableBody.innerHTML = '';
        
        if (!data || data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center">No usage history found.</td></tr>';
            return;
        }
        
        // Paginate the data - 8 items per page
        const startIndex = (currentUsagePage - 1) * usageItemsPerPage;
        const endIndex = startIndex + usageItemsPerPage;
        const itemsToShow = data.slice(startIndex, endIndex);
        
        itemsToShow.forEach(item => {
            const row = document.createElement('tr');
            
            // Format the date for better readability
            const usedDate = new Date(item.used_at);
            const formattedDate = usedDate.toLocaleDateString() + ' ' + 
                                usedDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            // Truncate notes if too long
            const notes = item.notes ? 
                (item.notes.length > 30 ? item.notes.substring(0, 30) + '...' : item.notes) : 
                '-';
            
            row.innerHTML = `
                <td>${item.id}</td>
                <td>${item.task_id || '-'}</td>
                <td>${item.item_name}</td>
                <td>${item.quantity}</td>
                <td>${item.used_by}</td>
                <td>${formattedDate}</td>
                <td title="${item.notes}">${notes}</td>
            `;
            
            tableBody.appendChild(row);
        });
        
        // Update pagination
        updatePagination('usagePagination', data.length, currentUsagePage, (page) => {
            currentUsagePage = page;
            populateInventoryUsageTable();
        });
    } catch (error) {
        console.error("Error fetching inventory usage:", error);
        const tableBody = document.querySelector("#inventoryUsageTable tbody");
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center">Error loading usage data.</td></tr>';
    }
}

// Initialize when document is ready
$(document).ready(async function() {
    try {
        // Load local inventory and update dashboard with it
        try {
            const response = await fetch('fetch_local_inventory.php');
            const localInventoryData = await response.json();
            updateDashboard(localInventoryData);
            populateLocalInventoryTable();
        } catch (error) {
            console.error("Error fetching local inventory:", error);
        }
        
        // Load inventory usage history
        try {
            populateInventoryUsageTable();
        } catch (error) {
            console.error("Error fetching inventory usage:", error);
        }
        
        // Refresh data every 30 seconds with error handling
        setInterval(async () => {
            try {
                const response = await fetch('fetch_local_inventory.php');
                const localInventoryData = await response.json();
                updateDashboard(localInventoryData);
                populateLocalInventoryTable();
                populateInventoryUsageTable(); // Refresh usage table as well
            } catch (error) {
                console.error("Failed to refresh data:", error);
            }
        }, 30000);
    } catch (error) {
        console.error("Error during initialization:", error);
    }
});

// Update this function to work with the local inventory data format
async function updateDashboard(data) {
    if (!data || data.length === 0) return;
    
    // Get unique categories count
    const uniqueCategories = [...new Set(data.map(item => item.category))].length;
    
    // Calculate total available stock across all items
    const totalStock = data.reduce((sum, item) => 
        sum + (parseInt(item.quantity) || 0), 0
    );
    
    // Count items with low stock (less than 10)
    const lowStockCount = data.filter(item => 
        parseInt(item.quantity) < 10
    ).length;

    // Update dashboard cards
    document.getElementById('totalSupplies').textContent = uniqueCategories;
    document.getElementById('availableStock').textContent = totalStock;
    document.getElementById('lowStockItems').textContent = lowStockCount;
}

// Set a timeout to hide the success message
setTimeout(function() {
    const successMessage = document.getElementById('success-message');
    if (successMessage) {
        successMessage.style.display = 'none';
    }
}, 5000); // Hide after 5 seconds

// Export functionality
function showExportModal() {
    // Set default date range (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
    
    document.getElementById('startDate').value = thirtyDaysAgo.toISOString().split('T')[0];
    document.getElementById('endDate').value = today.toISOString().split('T')[0];
    
    // Show/hide date range section based on initial selection
    toggleDateRangeVisibility();
    
    // Add change event listener for export type
    document.querySelectorAll('input[name="exportType"]').forEach(radio => {
        radio.addEventListener('change', toggleDateRangeVisibility);
    });
    
    // Open the modal using jQuery for Bootstrap 4
    $('#exportModal').modal('show');
}

function toggleDateRangeVisibility() {
    const exportType = document.querySelector('input[name="exportType"]:checked').value;
    const dateRangeSection = document.getElementById('dateRangeSection');
    
    if (exportType === 'usage') {
        dateRangeSection.style.display = 'block';
    } else {
        dateRangeSection.style.display = 'none';
    }
}

function exportData() {
    const exportType = document.querySelector('input[name="exportType"]:checked').value;
    const exportFormat = document.querySelector('input[name="exportFormat"]:checked').value;
    
    let url = '';
    
    if (exportType === 'inventory') {
        // Export current inventory
        url = `export_inventory.php?format=${exportFormat}`;
    } else {
        // Export usage history with date range
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        
        // Validate dates
        if (!startDate || !endDate) {
            alert('Please select both start and end dates for usage history export.');
            return;
        }
        
        if (new Date(startDate) > new Date(endDate)) {
            alert('Start date cannot be after end date.');
            return;
        }
        
        url = `export_usage.php?format=${exportFormat}&start=${startDate}&end=${endDate}`;
    }
    
    // Open in new window/tab
    window.open(url, '_blank');
    
    // Close the modal using jQuery for Bootstrap 4
    $('#exportModal').modal('hide');
}

// Handle potential script.js errors
document.addEventListener('DOMContentLoaded', function() {
    // Handle potential missing elements that script.js might be trying to access
    const elementsToCheck = [
        'searchForm', 'searchInput', 'notificationBell', 'sideMenu', 'toggleSidebar'
    ];
    
    elementsToCheck.forEach(id => {
        const elem = document.getElementById(id);
        if (!elem) {
            console.log(`Element with ID '${id}' not found, creating placeholder to prevent errors`);
            // Create a hidden placeholder to prevent null reference errors
            const placeholder = document.createElement('div');
            placeholder.id = id;
            placeholder.style.display = 'none';
            document.body.appendChild(placeholder);
        }
    });
});

// Fix modal close buttons for Bootstrap 4
$(document).ready(function() {
    // Make sure close buttons work properly
    $('.modal .btn-close, .modal .close, .modal .btn-secondary[data-bs-dismiss="modal"]').on('click', function() {
        $(this).closest('.modal').modal('hide');
    });
});
</script>
</body>
</html>
