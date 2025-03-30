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

// Handle the request for necessities
$successMessage = ""; // Initialize a variable for the success message
$requestItems = []; // Initialize array for requested items

// Check if we have a request type parameter
$requestType = isset($_GET['request_type']) ? $_GET['request_type'] : '';

// Fetch local inventory if request type is specified
if ($requestType === 'low_stock' || $requestType === 'out_of_stock') {
    $sql = "SELECT * FROM inventory";
    if ($requestType === 'low_stock') {
        $sql .= " WHERE quantity > 0 AND quantity < 10";
    } else if ($requestType === 'out_of_stock') {
        $sql .= " WHERE quantity = 0";
    }
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $requestItems[] = $row;
        }
    }
}

// Handle bulk request submission
if (isset($_POST['bulk_request_submit'])) {
    $requestSuccess = false;
    $requestErrors = [];
    
    if (isset($_POST['request_items']) && !empty($_POST['request_items'])) {
        $requestedItems = $_POST['request_items'];
        $quantities = $_POST['quantities'];
        
        // API endpoint for submitting requests
        $apiUrl = "https://logistic1.paradisehoteltomasmorato.com/sub-modules/logistic1/warehouse/request.php";
        
        // Create array for request items
        $itemsToRequest = [];
        foreach ($requestedItems as $index => $itemId) {
            // Get item details from the database
            $itemSql = "SELECT category, item_name FROM inventory WHERE id = ?";
            $stmt = $conn->prepare($itemSql);
            $stmt->bind_param("i", $itemId);
            $stmt->execute();
            $itemResult = $stmt->get_result();
            
            if ($row = $itemResult->fetch_assoc()) {
                $itemsToRequest[] = [
                    'category' => $row['category'],
                    'item_name' => $row['item_name'],
                    'sku' => '',
                    'quantity' => $quantities[$index]
                ];
            }
        }
        
        if (!empty($itemsToRequest)) {
            // Prepare the request data
            $requestData = array(
                'pickup_location' => 'Housekeeping',
                'delivery_location' => 'Tomas Morato',
                'requester_name' => $_SESSION['username'],
                'requester_email' => 'paradisehotelmaintenance@gmail.com',
                'contact_number' => '09123456789', // Default contact number
                'item_type' => 'general',
                'items' => $itemsToRequest
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
                $requestErrors[] = "Error submitting request: " . curl_error($ch);
            } else {
                $result = json_decode($response, true);
                if (isset($result['status']) && $result['status'] === 'success') {
                    $requestSuccess = true;
                    $successMessage = "Bulk stock request submitted successfully!";
                } else {
                    $requestErrors[] = "Error: " . ($result['message'] ?? 'Unknown error occurred');
                }
            }
            
            // Close cURL session
            curl_close($ch);
        } else {
            $requestErrors[] = "No valid items to request";
        }
    } else {
        $requestErrors[] = "Please select at least one item to request";
    }
    
    if (!$requestSuccess && !empty($requestErrors)) {
        $successMessage = implode("<br>", $requestErrors);
    }
}

// Handle single item request
if (isset($_POST['request_item'])) {
    $item_id = $_POST['item_id'];
    $quantity = $_POST['quantity'];

    // API endpoint for submitting requests
    $apiUrl = "https://logistic1.paradisehoteltomasmorato.com/sub-modules/logistic1/warehouse/view_api.php";

    // Prepare the request data
    $requestData = array(
        'action' => 'create_request',
        'item_id' => $item_id,
        'quantity' => $quantity,
        'requester_name' => $_SESSION['username'], // Use logged in username
        'department' => 'Housekeeping', // Set your department
        'contact_number' => '', // Add contact number if available
        'requester_email' => 'paradisehotelmaintenance@gmail.com' // Add email if available
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
        if (isset($result['success']) && $result['success']) {
            $successMessage = "Stock request submitted successfully!";
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Inventory</title>
    <!-- Replace the problematic Font Awesome kit with CDN version -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/css/bootstrap.min.css">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/inventory.css"> 
    <link rel="icon" href="img/logo.webp">
    <style>
        .item-checkbox {
            width: 20px;
            height: 20px;
        }
    </style>
</head>
<body>
<?php include 'index.php'; ?>
<div class="container">
    
    <div class="p-4 mt-4 title-heading card">
        <h3>Request Inventory</h3>
        <?php if ($requestType === 'low_stock'): ?>
            <div class="text-warning">Requesting Low Stock Items</div>
        <?php elseif ($requestType === 'out_of_stock'): ?>
            <div class="text-danger">Requesting Out-of-Stock Items</div>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($successMessage)):?>
        <div class="alert alert-success alert-dismissible fade show" id="success-message">
            <?php echo $successMessage; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($requestItems)): ?>
    <div class="card mb-4">
        <div class="card-body">
            <h4><i class="fas fa-clipboard-list"></i> Items to Request</h4>
            <p>The following items need to be requested from the warehouse. Check the items you want to request:</p>
            
            <form method="post" action="">
                <div class="table-responsive">
                    <table class="table table-hover border table-bordered">
                        <thead class="bg-dark text-light">
                            <tr>
                                <th><input type="checkbox" id="selectAll" class="item-checkbox"></th>
                                <th>Item Name</th>
                                <th>Current Quantity</th>
                                <th>Category</th>
                                <th>Request Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($requestItems as $item): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="request_items[]" value="<?php echo $item['id']; ?>" class="item-checkbox">
                                </td>
                                <td><?php echo $item['item_name']; ?></td>
                                <td class="<?php echo $item['quantity'] == 0 ? 'text-danger' : 'text-warning'; ?>">
                                    <?php echo $item['quantity']; ?>
                                    <?php echo $item['quantity'] == 0 ? ' (Out of Stock)' : ' (Low)'; ?>
                                </td>
                                <td><?php echo $item['category']; ?></td>
                                <td>
                                    <input type="number" name="quantities[]" value="20" min="1" class="form-control form-control-sm">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-end mt-3">
                    <button type="button" class="btn btn-primary me-2" id="findInWarehouseBtn">
                        <i class="fas fa-search"></i> Find All in Warehouse
                    </button>
                    <button type="submit" name="bulk_request_submit" class="btn btn-success">
                        <i class="fas fa-paper-plane"></i> Submit Bulk Request
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="row text-center mb-4 pt-4 m-0"> 
        <div class="col-lg-6 col-md-6 mb-4">
            <div class="card">
                <div class="underline"></div>
                <div class="card-body">
                    <h5 class="card-title">Warehouse Items</h5>
                    <h3 class="card-text"><span id="totalWarehouseItems">0</span></h3>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 mb-4">
            <div class="card">
                <div class="underline"></div>
                <div class="card-body">
                    <h5 class="card-title">Pending Requests</h5>
                    <h3 class="card-text"><span id="pendingRequests">0</span></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Warehouse and Requested Stocks Tables Side by Side -->
    <div class="row m-0">
        <div class="col-lg-7 mb-4">
            <div class="card centered-div">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0"><i class="fas fa-warehouse"></i> Warehouse</h3>
                        <div class="input-group mt-2">
                            <input type="text" id="searchInput" placeholder="Search items..." class="form-control small-input" onkeyup="filterTable()">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                        </div>
                    </div>
                    <div class="warehouse-table">
                        <table class="table table-hover border table-bordered" id="inventoryTable">
                            <thead class="striky-top">
                                <tr class="bg-dark text-light">
                                    <th scope="col">ID</th>
                                    <th scope="col">Item Name</th>
                                    <th scope="col">Available Stock</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center mt-3">
                        <nav aria-label="Inventory pagination">
                            <ul class="pagination pagination-sm" id="inventoryPagination">
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Requested Stocks Table -->
        <div class="col-lg-5 mx-auto">
            <div class="card centered-div">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mt-2"><i class="fas fa-box-open"></i> Requested Stocks</h3>
                    </div>
                    <div class="warehouse-table">
                        <table class="table table-hover border table-bordered" id="requestedStocksTable">
                            <thead class="striky-top">
                                <tr class="bg-dark text-light">
                                    <th scope="col">ID</th>
                                    <th scope="col">Item Name</th>
                                    <th scope="col">Requested Quantity</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">Status</th> <!-- Added Status column -->
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center mt-3">
                        <nav aria-label="Requested stocks pagination">
                            <ul class="pagination pagination-sm" id="requestedPagination">
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JS and Bootstrap dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
   function filterTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('inventoryTable');
    const tr = table.getElementsByTagName('tr');
    let noResult = true; // Track if any row is shown

    for (let i = 1; i < tr.length; i++) { // Start from 1 to skip the header row
        const td = tr[i].getElementsByTagName('td');
        let found = false;

        for (let j = 1; j < td.length - 1; j++) { // Check item name and category
            if (td[j]) {
                const txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toLowerCase().indexOf(filter) > -1) {
                    found = true;
                    noResult = false; // Found a match
                    break;
                }
            }
        }
        
        tr[i].style.display = found ? '' : 'none'; // Show or hide the row
    }

    // Handle no results case
    const noResultsRow = document.getElementById('noResultsRow');
    if (noResult) {
        noResultsRow.style.display = ''; // Show the "No results" row
    } else {
        noResultsRow.style.display = 'none'; // Hide it if results are found
    }
}

setTimeout(function() {
    var successMessage = document.getElementById('success-message');
    if (successMessage) {
        successMessage.style.display = 'none';
    }
}, 2000);

const apiEndpoint = "https://logistic1.paradisehoteltomasmorato.com/sub-modules/logistic1/warehouse/table.php?api=1&api_key=20054d820a3ba1bae07591397d8cacdf";

async function fetchData() {
    try {
        const response = await fetch(apiEndpoint);
        
        // Check if response is valid before parsing JSON
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        // Check content type to ensure it's JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.warn('Response is not JSON, attempting to parse anyway');
        }
        
        try {
            const data = await response.json();
            return data;
        } catch (parseError) {
            console.error("Error parsing JSON:", parseError);
            // Return empty data structure instead of letting the error propagate
            return { items2: [], item_batches: [] };
        }
    } catch (error) {
        console.error("Error fetching data:", error);
        return { items2: [], item_batches: [] };
    }
}

function mapBatches(batches) {
    const map = {};
    batches.forEach(batch => {
        if (!map[batch.item_id] && batch.expiration_date) {
            map[batch.item_id] = batch.expiration_date;
        }
    });
    return map;
}

// Add pagination variables
let currentInventoryPage = 1;
let currentRequestedPage = 1;
const itemsPerPage = 8;

async function populateInventoryTable(data) {
    if (!data.items2) return;
    
    // Filter for items with "hotel" type
    const hotelItems = data.items2.filter(item => 
        item.type && item.type.toLowerCase() === 'hotel'
    );
    
    const tableBody = document.querySelector("#inventoryTable tbody");
    tableBody.innerHTML = '';
    
    if (hotelItems.length === 0) {
        tableBody.innerHTML = '<tr id="noResultsRow"><td colspan="5" class="text-center">No hotel items found.</td></tr>';
        return;
    }
    
    // Add "No results" row (hidden by default)
    const noResultsRow = document.createElement('tr');
    noResultsRow.id = 'noResultsRow';
    noResultsRow.style.display = 'none';
    noResultsRow.innerHTML = '<td colspan="5" class="text-center">No results found.</td>';
    tableBody.appendChild(noResultsRow);
    
    const startIndex = (currentInventoryPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const itemsToShow = hotelItems.slice(startIndex, endIndex);
    
    itemsToShow.forEach(item => {
        const row = document.createElement('tr');
        
        // Add stock level styling
        let stockStyle = '';
        let stockLabel = '';
        if (item.quantity == 0) {
            stockStyle = 'color: red;';
            stockLabel = ' (Out of Stock)';
        } else if (item.quantity < 10) {
            stockStyle = 'color: orange;';
            stockLabel = ' (Low)';
        }
        
        row.innerHTML = `
            <td>${item.id}</td>
            <td>${item.item_name}</td>
            <td style="${stockStyle}">${item.quantity}${stockLabel}</td>
            <td>${item.category}</td>
            <td>
                <button class='btn btn-dark btn-sm' data-toggle='modal' data-target='#requestModal${item.id}'>
                    <i class='fas fa-hand-paper'></i>
                </button>
            </td>
        `;
        
        tableBody.appendChild(row);
        
        // Create request modal for each item
        createRequestModal(item);
    });

    // Update pagination
    updatePagination('inventoryPagination', hotelItems.length, currentInventoryPage, (page) => {
        currentInventoryPage = page;
        populateInventoryTable(data);
    });

    // Update the dashboard card
    document.getElementById('totalWarehouseItems').textContent = hotelItems.length;
}

function createRequestModal(item) {
    const modalHtml = `
        <div class='modal fade' id='requestModal${item.id}' tabindex='-1' role='dialog' aria-labelledby='requestModalLabel' aria-hidden='true'>
            <div class='modal-dialog modal-lg modal-dialog-centered' role='document'>
                <div class='modal-content'>
                    <div class='modal-header'>
                        <h5 class='modal-title' id='requestModalLabel'>Request ${item.item_name}</h5>
                        <button type='button' class='btn-close' data-dismiss='modal' aria-label='Close'></button>
                    </div>
                    <div class='modal-body'>
                        <div class="mb-4">
                            <h6>Requester Information</h6>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label" style="font-size: 12px;">Department</label>
                                    <input type="text" class="form-control" style="font-size: 12px;" id="pickup_location${item.id}" value="Housekeeping" required>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label" style="font-size: 12px;">Delivery Location</label>
                                    <input type="text" class="form-control" style="font-size: 12px;"  id="delivery_location${item.id}" value="Housekeeping" required>
                                </div>
                                <div class="col-md-4 mb-2" style="font-size: 12px;">
                                    <label class="form-label">Requester Name</label>
                                    <input type="text" class="form-control" style="font-size: 12px;" id="requester_name${item.id}" value="<?php echo $_SESSION['username']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label" style="font-size: 12px;">Email</label>
                                    <input type="email" class="form-control" style="font-size: 12px;" id="requester_email${item.id}" required>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label" style="font-size: 12px;">Contact Number</label>
                                    <input type="tel" class="form-control" style="font-size: 12px;" id="contact_number${item.id}" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <h6>Item Request Details</h6>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label" style="font-size: 12px;">Category</label>
                                    <input type="text" class="form-control" style="font-size: 12px;" id="category${item.id}" value="${item.category}" readonly>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label" style="font-size: 12px;">Quantity</label>
                                    <input type="number" class="form-control" style="font-size: 12px;" id="quantity${item.id}" min="1" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary px-2 rounded-3" style="font-size: 12px;" data-bs-dismiss="modal">
                            <i class="bx bx-x-circle me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success px-2 rounded-3" style="font-size: 12px;">
                            <i class="bx bx-send me-1"></i> Submit
                        </button>
                    </div>
                </div>
            </div>
        </div>`;
        
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

async function submitRequest(itemId) {
    const pickup_location = document.getElementById(`pickup_location${itemId}`).value.trim();
    const delivery_location = document.getElementById(`delivery_location${itemId}`).value.trim();
    const requester_name = document.getElementById(`requester_name${itemId}`).value.trim();
    const requester_email = document.getElementById(`requester_email${itemId}`).value.trim();
    const contact_number = document.getElementById(`contact_number${itemId}`).value.trim();
    const quantity = parseInt(document.getElementById(`quantity${itemId}`).value.trim()) || 0;
    const category = document.getElementById(`category${itemId}`).value.trim();

    if (!pickup_location || !delivery_location || !requester_name || !requester_email || !contact_number || quantity <= 0) {
        alert("Please complete all required fields.");
        return;
    }

    const payload = {
        pickup_location,
        delivery_location,
        requester_name,
        requester_email,
        contact_number,
        item_type: 'general',
        items: [{
            category: category,
            item_name: document.querySelector(`#requestModal${itemId} .modal-title`).textContent.replace('Request ', ''),
            sku: '',
            quantity: quantity
        }]
    };

    try {
        const response = await fetch("https://logistic1.paradisehoteltomasmorato.com/sub-modules/logistic1/warehouse/request.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(payload)
        });
        
        const result = await response.json();
        if (result.status === "success") {
            alert("Request created successfully!");
            $(`#requestModal${itemId}`).modal('hide');
            // Refresh the requested stocks table
            fetchRequestsData().then(data => {
                populateRequestedStocksTable(data);
            });
        } else {
            alert("Error: " + (result.message || "Unknown error occurred"));
        }
    } catch (error) {
        console.error("Error submitting request:", error);
        alert("Error submitting request. Please try again.");
    }
}

const requestsApiEndpoint = "https://logistic1.paradisehoteltomasmorato.com/sub-modules/logistic1/warehouse/view_api.php";
let fetchedRequestsData = null;

async function fetchRequestsData() {
    try {
        const response = await fetch(requestsApiEndpoint);
        
        // Check if response is valid before parsing JSON
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        // Check content type to ensure it's JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.warn('Response may not be JSON, attempting to parse anyway');
        }
        
        try {
            const data = await response.json();
            fetchedRequestsData = data;
            return data;
        } catch (parseError) {
            console.error("Error parsing JSON response:", parseError);
            // Return empty data structure instead of letting the error propagate
            return { requests: [], request_items: [], reservations: [] };
        }
    } catch (error) {
        console.error("Error fetching requests data:", error);
        return { requests: [], request_items: [], reservations: [] };
    }
}

function populateRequestedStocksTable(data) {
    const tableBody = document.querySelector("#requestedStocksTable tbody");
    tableBody.innerHTML = '';

    if (!data.requests || !data.request_items) {
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center">No requested stocks found.</td></tr>';
        return;
    }

    // Filter requests for the Housekeeping department
    const filteredRequests = data.requests.filter(request => 
        request.pickup_location && request.pickup_location.trim().toLowerCase() === "housekeeping"
    );

    if (filteredRequests.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center">No requested stocks found for Housekeeping.</td></tr>';
        return;
    }

    // Get all request items for these requests
    let allItems = [];
    filteredRequests.forEach(request => {
        const requestItems = data.request_items.filter(item => item.request_id == request.id);
        requestItems.forEach(item => {
            // Merge request status with item data
            allItems.push({
                ...item,
                requestStatus: request.status || 'Pending',
                requestId: request.id
            });
        });
    });

    // Check for reservations and update status if needed
    if (data.reservations) {
        allItems = allItems.map(item => {
            const request = filteredRequests.find(r => r.id == item.requestId);
            
            // Default to the request status 
            item.displayStatus = request.status || 'Pending';
            
            // If approved, check for more detailed status from reservations
            if (request && request.status && request.status.trim().toLowerCase() === "approved") {
                const matchingReservation = data.reservations.find(reservation => 
                    reservation.contact_number && request.requester_email && 
                    reservation.contact_number.trim().toLowerCase() === request.requester_email.trim().toLowerCase()
                );
                
                if (matchingReservation && matchingReservation.status) {
                    const resStatus = Number(matchingReservation.status);
                    const validStatusCodes = [1, 2, 7, 9, 8, 4];
                    
                    if (validStatusCodes.includes(resStatus)) {
                        switch (resStatus) {
                            case 1: item.displayStatus = "Pending"; break;
                            case 2: item.displayStatus = "In Progress"; break;
                            case 7: item.displayStatus = "In-Transit"; break;
                            case 9: item.displayStatus = "Completed"; break;
                            case 8: item.displayStatus = "Delayed"; break;
                            case 4: item.displayStatus = "Cancelled"; break;
                            default: item.displayStatus = "Approved";
                        }
                    }
                }
            }
            
            return item;
        });
    }

    // Count pending requests for the dashboard card
    const pendingCount = allItems.filter(item => 
        item.displayStatus.toLowerCase() === 'pending' || 
        item.displayStatus.toLowerCase() === 'in progress'
    ).length;
    document.getElementById('pendingRequests').textContent = pendingCount;

    // Sort items by id (newest first)
    allItems.sort((a, b) => b.id - a.id);

    // Paginate
    const startIndex = (currentRequestedPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const itemsToShow = allItems.slice(startIndex, endIndex);
    
    itemsToShow.forEach(item => {
        const row = document.createElement('tr');
        
        // Get the appropriate status to display
        const displayStatus = item.displayStatus || item.requestStatus || 'Pending';
        let statusClass = 'badge bg-secondary'; // Default
        
        // Determine badge color based on status text
        const statusLower = displayStatus.trim().toLowerCase();
        if (statusLower === 'approved' || statusLower === 'completed') {
            statusClass = 'badge bg-success';
        } else if (statusLower === 'pending') {
            statusClass = 'badge bg-primary';
        } else if (statusLower === 'in progress' || statusLower === 'in-transit' || statusLower === 'in-progress') {
            statusClass = 'badge bg-warning text-dark';
        } else if (statusLower === 'rejected' || statusLower === 'cancelled' || statusLower === 'delayed') {
            statusClass = 'badge bg-danger';
        }

        row.innerHTML = `
            <td>${item.id}</td>
            <td>${item.item_name}</td>
            <td>${item.quantity}</td>
            <td>${item.category || 'Housekeeping'}</td>
            <td><span class="${statusClass}">${displayStatus}</span></td>
        `;
        tableBody.appendChild(row);
    });

    // Update pagination
    updatePagination('requestedPagination', allItems.length, currentRequestedPage, (page) => {
        currentRequestedPage = page;
        populateRequestedStocksTable(data);
    });
}

function updatePagination(paginationId, totalItems, currentPage, callback) {
    const totalPages = Math.ceil(totalItems / itemsPerPage);
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

// Initialize both tables when document is ready
$(document).ready(async function() {
    try {
        const inventoryData = await fetchData();
        populateInventoryTable(inventoryData);

        // Add error handling for requests data
        try {
            const requestsData = await fetchRequestsData();
            populateRequestedStocksTable(requestsData);
        } catch (error) {
            console.error("Failed to load requested stocks:", error);
            document.querySelector("#requestedStocksTable tbody").innerHTML = 
                '<tr><td colspan="5" class="text-center">Failed to load requested stocks data.</td></tr>';
        }
        
        // Refresh data every 30 seconds with error handling
        setInterval(async () => {
            try {
                const newRequestsData = await fetchRequestsData();
                populateRequestedStocksTable(newRequestsData);
            } catch (error) {
                console.error("Failed to refresh requested stocks:", error);
            }
        }, 30000);
    } catch (error) {
        console.error("Error during initialization:", error);
    }
});

// Prevent script.js errors by checking if elements exist before adding event listeners
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

// Add handler for "Select All" checkbox
$(document).ready(function() {
    $('#selectAll').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.item-checkbox').prop('checked', isChecked);
    });
    
    // Find all items in warehouse
    $('#findInWarehouseBtn').on('click', function() {
        // Get all item names
        const itemNames = [];
        $('input[name="request_items[]"]:checked').each(function() {
            const row = $(this).closest('tr');
            const itemName = row.find('td:nth-child(2)').text().trim();
            itemNames.push(itemName);
        });
        
        if (itemNames.length === 0) {
            alert('Please select at least one item to find in the warehouse.');
            return;
        }
        
        // Search for all items one by one
        itemNames.forEach((name, index) => {
            setTimeout(() => {
                const searchInput = $('#searchInput');
                if (searchInput.length) {
                    searchInput.val(name);
                    filterTable(); // Trigger the search
                    
                    // Highlight the search box
                    searchInput.focus();
                    searchInput.css('background-color', '#ffffcc');
                    setTimeout(() => {
                        searchInput.css('background-color', '');
                    }, 1000);
                }
            }, index * 1500); // Stagger searches by 1.5 seconds
        });
        
        // Scroll to the warehouse table
        $('html, body').animate({
            scrollTop: $('#inventoryTable').offset().top - 100
        }, 500);
    });
    
    // Hide success message after 5 seconds
    setTimeout(function() {
        const successMessage = document.getElementById('success-message');
        if (successMessage) {
            successMessage.style.display = 'none';
        }
    }, 5000);
});

// Enhanced filter function to highlight results that match local inventory items
function filterTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('inventoryTable');
    const tr = table.getElementsByTagName('tr');
    let noResult = true; // Track if any row is shown

    for (let i = 1; i < tr.length; i++) { // Start from 1 to skip the header row
        const td = tr[i].getElementsByTagName('td');
        let found = false;

        for (let j = 1; j < td.length - 1; j++) { // Check item name and category
            if (td[j]) {
                const txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toLowerCase().indexOf(filter) > -1) {
                    found = true;
                    noResult = false; // Found a match
                    break;
                }
            }
        }
        
        tr[i].style.display = found ? '' : 'none'; // Show or hide the row
    }

    // Handle no results case
    const noResultsRow = document.getElementById('noResultsRow');
    if (noResult) {
        noResultsRow.style.display = ''; // Show the "No results" row
    } else {
        noResultsRow.style.display = 'none'; // Hide it if results are found
    }
}
</script>
</body>
</html>
