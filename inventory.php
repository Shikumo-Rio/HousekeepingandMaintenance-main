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
        'requester_email' => '' // Add email if available
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

// Check stock levels and add notifications for low or zero stock
$stockCheckSQL = "SELECT id, item_name, available_stock FROM inventory WHERE available_stock < 10";
$stockResult = $conn->query($stockCheckSQL);

while ($stockRow = $stockResult->fetch_assoc()) {
    if ($stockRow['available_stock'] == 0) {
        addStockNotification($conn, $stockRow['item_name'], $emp_id, 'out');
    } elseif ($stockRow['available_stock'] < 10) {
        addStockNotification($conn, $stockRow['item_name'], $emp_id, 'low');
    }
}
    

// Fetch inventory items
$sql = "SELECT * FROM inventory";
$result = $conn->query($sql);

// Remove the old requested stocks query
// $requested_sql = "SELECT * FROM requested_stocks";
// $requested_result = $conn->query($requested_sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/css/bootstrap.min.css">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <script src="https://kit.fontawesome.com/ae360af17e.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/inventory.css"> 
    <link rel="icon" href="img/logo.webp">
</head>
<body>
<?php include 'index.php'; ?>
<div class="container">
    
    <div class="p-4 title-heading card">
        <h3>Inventory</h3>
    </div>
    <?php if (!empty($successMessage)):?>
                <div class="alert alert-success">
                    <?php echo $successMessage; ?>
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
    <div class="row text-center mb-4 pt-4 m-0"> 
    <!-- Total Requested Card -->
    

    <!-- Inventory and Requested Stocks Tables Side by Side -->
    <div class="row m-0">
        <div class="col-lg-7 mb-4">
            <div class="card centered-div">
                <div class="card-body p-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="mb-0"><i class="fas fa-box"></i> Inventory</h3>
                        <div class="input-group mb-3">
                            <input type="text" id="searchInput" placeholder="Search items..." class="form-control small-input" onkeyup="filterTable()">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                        </div>
                    </div>
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
                <div class="card-body p-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="mb-4"><i class="fas fa-box-open"></i> Requested Stocks</h3>
                    </div>
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
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
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
                const data = await response.json();
                return data;
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
            
            const tableBody = document.querySelector("#inventoryTable tbody");
            tableBody.innerHTML = '';
            
            const startIndex = (currentInventoryPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const itemsToShow = data.items2.slice(startIndex, endIndex);
            
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
            updatePagination('inventoryPagination', data.items2.length, currentInventoryPage, (page) => {
                currentInventoryPage = page;
                populateInventoryTable(data);
            });
        }

        function createRequestModal(item) {
            const modalHtml = `
                <div class='modal fade' id='requestModal${item.id}' tabindex='-1' role='dialog' aria-labelledby='requestModalLabel' aria-hidden='true'>
                    <div class='modal-dialog modal-lg' role='document'>
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
                                            <label class="form-label">Department</label>
                                            <input type="text" class="form-control" id="pickup_location${item.id}" value="Housekeeping" required>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Delivery Location</label>
                                            <input type="text" class="form-control" id="delivery_location${item.id}" value="Housekeeping" required>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Requester Name</label>
                                            <input type="text" class="form-control" id="requester_name${item.id}" value="<?php echo $_SESSION['username']; ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" id="requester_email${item.id}" required>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Contact Number</label>
                                            <input type="tel" class="form-control" id="contact_number${item.id}" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <h6>Item Request Details</h6>
                                    <div class="form-group">
                                        <label for="quantity">Quantity</label>
                                        <input type="number" class="form-control" id="quantity${item.id}" min="1" required>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>
                                <button type='button' class='btn btn-success' onclick="submitRequest(${item.id})">Submit Request</button>
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
                    category: 'Housekeeping',
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
                    const requestsData = await fetchRequestsData();
                    populateRequestedStocksTable(requestsData);
                } else {
                    alert("Error: " + (result.message || "Unknown error occurred"));
                }
            } catch (error) {
                console.error("Error submitting request:", error);
                alert("Error submitting request. Please try again.");
            }
        }

        const requestsApiEndpoint = "https://logistic1.paradisehoteltomasmorato.com/sub-modules/logistic1/warehouse/view_api.php";

        async function fetchRequestsData() {
            try {
                const response = await fetch(requestsApiEndpoint);
                const data = await response.json();
                return data;
            } catch (error) {
                console.error("Error fetching requests data:", error);
                return { requests: [], request_items: [] };
            }
        }

        function populateRequestedStocksTable(data) {
            const tableBody = document.querySelector("#requestedStocksTable tbody");
            tableBody.innerHTML = '';

            if (data.request_items && data.request_items.length > 0) {
                const startIndex = (currentRequestedPage - 1) * itemsPerPage;
                const endIndex = startIndex + itemsPerPage;
                const itemsToShow = data.request_items.slice(startIndex, endIndex);
                
                itemsToShow.forEach(item => {
                    const row = document.createElement('tr');
                    let statusClass = 'badge bg-secondary'; // Default to Pending
                    if (item.status === 'Approved') {
                        statusClass = 'badge bg-success';
                    } else if (item.status === 'Rejected') {
                        statusClass = 'badge bg-danger';
                    }
                    row.innerHTML = `
                        <td>${item.id}</td>
                        <td>${item.item_name}</td>
                        <td>${item.quantity}</td>
                        <td>${item.category}</td>
                        <td><span class="${statusClass}">${item.status || 'Pending'}</span></td>
                    `;
                    tableBody.appendChild(row);
                });

                // Update pagination
                updatePagination('requestedPagination', data.request_items.length, currentRequestedPage, (page) => {
                    currentRequestedPage = page;
                    populateRequestedStocksTable(data);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center">No requested stocks found.</td></tr>';
            }
        }

        // Add pagination update function
        function updatePagination(paginationId, totalItems, currentPage, onPageChange) {
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            const pagination = document.getElementById(paginationId);
            pagination.innerHTML = '';

            // Only show pagination if there's more than one page
            if (totalPages <= 1) return;

            // Previous button
            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
            prevLi.innerHTML = `<a class="page-link" href="#" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span></a>`;
            prevLi.onclick = () => currentPage > 1 && onPageChange(currentPage - 1);
            pagination.appendChild(prevLi);

            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${currentPage === i ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                li.onclick = () => onPageChange(i);
                pagination.appendChild(li);
            }

            // Next button
            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
            nextLi.innerHTML = `<a class="page-link" href="#" aria-label="Next">
                <span aria-hidden="true">&raquo;</span></a>`;
            nextLi.onclick = () => currentPage < totalPages && onPageChange(currentPage + 1);
            pagination.appendChild(nextLi);
        }

        // Initialize both tables when document is ready
        $(document).ready(async function() {
            const inventoryData = await fetchData();
            updateDashboard(inventoryData);
            populateInventoryTable(inventoryData);

            const requestsData = await fetchRequestsData();
            populateRequestedStocksTable(requestsData);
        });

        async function updateDashboard(data) {
            if (!data.items2) return;
            
            // Get unique categories count
            const uniqueCategories = [...new Set(data.items2.map(item => item.category))].length;
            
            // Calculate total available stock across all items
            const totalStock = data.items2.reduce((sum, item) => 
                sum + (parseInt(item.quantity) || 0), 0
            );
            
            // Count items with low stock
            const lowStockCount = data.items2.filter(item => 
                parseInt(item.quantity) < 10
            ).length;

            // Update dashboard cards
            document.getElementById('totalSupplies').textContent = uniqueCategories;
            document.getElementById('availableStock').textContent = totalStock;
            document.getElementById('lowStockItems').textContent = lowStockCount;
        }

</script>
</body>
</html>
