<?php
session_start();
require_once("../database.php");

if (!isset($_SESSION['verified']) || !isset($_SESSION['uname'])) {
    header("Location: ../index.html");
    exit();
}

// Function to generate a unique order code
function generateOrderCode() {
    // Use current timestamp and a random number for uniqueness
    $timestamp = date('YmdHis');
    $random = mt_rand(1000, 9999);
    return 'ORD-' . $timestamp . '-' . $random;
}

// Handle food order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_food_order'])) {
    $response = array('success' => false);
    
    try {
        // Get customer data
        $customer_name = $_SESSION['uname'];
        $order_code = generateOrderCode();
        $status = "pending";
        $total_price = 0;
        
        // Check if items data exists and decode JSON
        if (!isset($_POST['items'])) {
            throw new Exception("No items data received");
        }
        
        $items = json_decode($_POST['items'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON data: " . json_last_error_msg());
        }
        
        if (empty($items) || !is_array($items)) {
            throw new Exception("No valid items in order");
        }
        
        // Process each ordered item
        foreach ($items as $item) {
            // Validate required item fields
            if (!isset($item['name']) || !isset($item['quantity']) || !isset($item['price'])) {
                throw new Exception("Missing required item data");
            }
            
            $food_item = $item['name'];
            $quantity = intval($item['quantity']);
            $price = floatval($item['price']);
            $item_total = $price * $quantity;
            $total_price += $item_total;
            
            // Insert each food order item
            $stmt = $conn->prepare("INSERT INTO foodorders (code, customer_name, food_item, quantity, totalprice, status, created_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, NOW())");
            
            if (!$stmt) {
                throw new Exception("Database prepare error: " . $conn->error);
            }
            
            $stmt->bind_param("sssids", $order_code, $customer_name, $food_item, $quantity, $item_total, $status);
            
            if (!$stmt->execute()) {
                throw new Exception("Database execution error: " . $stmt->error);
            }
        }
        
        $response['success'] = true;
        $response['order_code'] = $order_code;
        $response['total'] = $total_price;
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
        error_log("Food order error: " . $e->getMessage());
    }
    
    // Set proper content type header
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Handle amenities request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uname = $_SESSION['uname'];
    $room = $_SESSION['room_number'];
    $status = "pending";
    
    // Check if this is a food order
    if (isset($_POST['order_type']) && $_POST['order_type'] == 'food') {
        $request = "Order Meal";
        unset($_POST['order_type']); // Remove from details
    } else {
        $request = "Request Amenities";
    }
    
    // Build details string from form data
    $details = [];
    foreach ($_POST as $item => $quantity) {
        if ($quantity > 0) {
            $details[] = "$item: $quantity";
        }
    }
    $detailsStr = implode(", ", $details);
    
    // Insert into database
    $stmt = $conn->prepare("INSERT INTO customer_messages (uname, request, details, room, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $uname, $request, $detailsStr, $room, $status);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Room Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>
<body>
    <div class="menu-container container p-0 m-0">
        <!-- Header Section -->
        <div class="menu-header d-flex align-items-center justify-content-between py-2 p-3">
            <div class="d-flex align-items-center">
                <img src="../img/logo.webp" alt="User Icon" class="rounded-circle me-2" width="40" height="40">
                <div>
                    <h5 class="mb-0 fw-semibold">Paradise Hotel</h5>
                </div>
            </div>
        </div>
        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs" id="serviceTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="request-services-tab" data-bs-toggle="tab" data-bs-target="#request-services" type="button" role="tab" aria-controls="request-services" aria-selected="true">
                    Request Services
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="order-food-tab" data-bs-toggle="tab" data-bs-target="#order-food" type="button" role="tab" aria-controls="order-food" aria-selected="false">
                    Order Food
                </button>
            </li>
        </ul>
        <!-- Tab Content -->
        <div class="tab-content p-3" id="serviceTabsContent">
            <!-- Request Services Content -->
            <div class="tab-pane fade show active" id="request-services" role="tabpanel" aria-labelledby="request-services-tab">
                <!-- Back Button -->
                <div class="d-flex align-items-center mb-4">
                    <a href="services.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                </div>
                <h5 class="mb-4 mt-4 fw-semibold">Request Amenities</h5>
                <!-- Amenities Quantity Selection -->
                <div class="amenities-request mt-3">
                    <form id="amenities-form">
                        <div class="list-group border-0">
                            <!-- Towel -->
                            <div class="d-flex justify-content-between align-items-center list-group-item">
                                <span class="fw-semibold">Towel</span>
                                <div class="quantity-selector">
                                    <button type="button" class="btn btn-outline-success minus-btn">−</button>
                                    <input type="number" class="form-control text-center quantity-input" id="towel" name="towel" min="0" value="0">
                                    <button type="button" class="btn btn-outline-success plus-btn">+</button>
                                </div>
                            </div>
                            <!-- Pillow -->
                            <div class="d-flex justify-content-between align-items-center list-group-item">
                                <span class="fw-semibold">Pillow</span>
                                <div class="quantity-selector">
                                    <button type="button" class="btn btn-outline-success minus-btn">−</button>
                                    <input type="number" class="form-control text-center quantity-input" id="pillow" name="pillow" min="0" value="0">
                                    <button type="button" class="btn btn-outline-success plus-btn">+</button>
                                </div>
                            </div>
                            <!-- Shampoo -->
                            <div class="d-flex justify-content-between align-items-center list-group-item">
                                <span class="fw-semibold">Shampoo</span>
                                <div class="quantity-selector">
                                    <button type="button" class="btn btn-outline-success minus-btn">−</button>
                                    <input type="number" class="form-control text-center quantity-input" id="shampoo" name="shampoo" min="0" value="0">
                                    <button type="button" class="btn btn-outline-success plus-btn">+</button>
                                </div>
                            </div>
                            <!-- Shower Gel -->
                            <div class="d-flex justify-content-between align-items-center list-group-item">
                                <span class="fw-semibold">Shower Gel</span>
                                <div class="quantity-selector">
                                    <button type="button" class="btn btn-outline-success minus-btn">−</button>
                                    <input type="number" class="form-control text-center quantity-input" id="shower-gel" name="shower_gel" min="0" value="0">
                                    <button type="button" class="btn btn-outline-success plus-btn">+</button>
                                </div>
                            </div>
                            <!-- Water -->
                            <div class="d-flex justify-content-between align-items-center list-group-item">
                                <span class="fw-semibold">Water</span>
                                <div class="quantity-selector">
                                    <button type="button" class="btn btn-outline-success minus-btn">−</button>
                                    <input type="number" class="form-control text-center quantity-input" id="water" name="water" min="0" value="0">
                                    <button type="button" class="btn btn-outline-success plus-btn">+</button>
                                </div>
                            </div>
                            <!-- Blanket -->
                            <div class="d-flex justify-content-between align-items-center list-group-item">
                                <span class="fw-semibold">Blanket</span>
                                <div class="quantity-selector">
                                    <button type="button" class="btn btn-outline-success minus-btn">−</button>
                                    <input type="number" class="form-control text-center quantity-input" id="blanket" name="blanket" min="0" value="0">
                                    <button type="button" class="btn btn-outline-success plus-btn">+</button>
                                </div>
                            </div>
                            <!-- Submit Button Inside List Group -->
                            <div class="list-group-item text-center">
                                <button type="submit" class="btn btn-success w-100 fw-bold">Request Amenities</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Order Meal Tab -->
            <div class="tab-pane fade" id="order-food" role="tabpanel" aria-labelledby="order-food-tab">
                <h5 class="mb-4 mt-4 fw-semibold">Order Meal</h5>
                <!-- Scrollable Meal Category Navigation -->
                <div class="nav-container">
                    <ul class="nav nav-pills flex-nowrap overflow-auto" id="meal-category-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="drinks-tab" data-bs-toggle="pill" data-bs-target="#drinks" type="button" role="tab" aria-controls="drinks" aria-selected="true">
                                Drinks
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="korean-food-tab" data-bs-toggle="pill" data-bs-target="#korean-food" type="button" role="tab" aria-controls="korean-food" aria-selected="false">
                                Korean Food
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="korean-rice-tab" data-bs-toggle="pill" data-bs-target="#korean-rice" type="button" role="tab" aria-controls="korean-rice" aria-selected="false">
                                Korean Rice Meal
                            </button>
                        </li>
                    </ul>
                </div>
                <!-- Meal Category Content -->
                <div class="tab-content" id="meal-category-content">
                    <!-- Drinks Tab -->
                    <div class="tab-pane fade show active" id="drinks" role="tabpanel" aria-labelledby="drinks-tab">
                        <div class="container">
                            <h6 class="mt-3 mb-2">Regular Drinks</h6>
                            <div class="list-group-order mt-2" id="drinks-container">
                                <!-- Regular drinks will be loaded here -->
                                <div class="text-center p-3 loading-spinner">
                                    <div class="spinner-border text-success" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <h6 class="mt-4 mb-2">Alcoholic Beverages</h6>
                            <div class="list-group-order mt-2" id="alcohol-container">
                                <!-- Alcoholic drinks will be loaded here -->
                                <div class="text-center p-3 loading-spinner">
                                    <div class="spinner-border text-success" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Korean Food Tab -->
                    <div class="tab-pane fade" id="korean-food" role="tabpanel" aria-labelledby="korean-food-tab">
                        <div class="container">
                            <div class="list-group-order mt-2" id="korean-food-container">
                                <!-- Korean food items will be loaded here -->
                                <div class="text-center p-3 loading-spinner">
                                    <div class="spinner-border text-success" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Korean Rice Meal Tab -->
                    <div class="tab-pane fade" id="korean-rice" role="tabpanel" aria-labelledby="korean-rice-tab">
                        <div class="container">
                            <div class="list-group-order mt-2" id="korean-rice-container">
                                <!-- Korean rice meal items will be loaded here -->
                                <div class="text-center p-3 loading-spinner">
                                    <div class="spinner-border text-success" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Submit Order Button -->
                <div class="mt-4 mb-4 p-3">
                    <button type="button" id="submit-food-order" class="btn btn-success w-100 fw-bold">Place Food Order</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        // Shared quantity selector functionality for all inputs
        function updateAnyQuantity(input, isIncrement) {
            let value = parseInt(input.value, 10) || 0;
            if (isIncrement) {
                // Set directly to 1 if it was 0, otherwise increment
                input.value = value === 0 ? 1 : value + 1;
            } else {
                input.value = value > 0 ? value - 1 : 0;
            }
        }

        // Quantity selector functionality for amenities
        function updateQuantity(button, isIncrement) {
            let input = button.closest(".list-group-item").querySelector(".quantity-input");
            if (input) {
                updateAnyQuantity(input, isIncrement);
            }
        }

        // Function to update quantity for food order items - use same logic as amenities
        function updateOrderQuantity(button, isIncrement) {
            let input = button.closest(".list-group-item-order").querySelector(".quantity-input-order");
            if (input) {
                updateAnyQuantity(input, isIncrement);
            }
        }

        // Add event listeners for the amenities buttons
        document.querySelectorAll(".plus-btn").forEach(button => {
            button.addEventListener("click", function () {
                if (this.closest(".list-group-item")) {
                    updateQuantity(this, true);
                }
            });
        });

        document.querySelectorAll(".minus-btn").forEach(button => {
            button.addEventListener("click", function () {
                if (this.closest(".list-group-item")) {
                    updateQuantity(this, false);
                }
            });
        });

        document.querySelectorAll(".quantity-input").forEach(input => {
            input.addEventListener("input", function () {
                if (isNaN(this.value) || this.value < 0) {
                    this.value = 0;
                }
            });
        });

        // Handle amenities form submission
        document.getElementById('amenities-form').addEventListener('submit', function(e) {
            e.preventDefault();
            // Collect all amenities quantities
            const formData = new FormData(this);
            
            document.querySelectorAll('.quantity-input').forEach(input => {
                if (parseInt(input.value) > 0) {
                    formData.append(input.name, input.value);
                }
            });
            
            // Send request
            fetch('req-room-service.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Amenities requested successfully!');
                    // Reset all quantity inputs
                    document.querySelectorAll('.quantity-input').forEach(input => {
                        input.value = '0';
                    });
                } else {
                    alert('Error submitting request');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error submitting request');
            });
        });

        // Attach global delegates for order quantity buttons (will work for dynamically added items)
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('plus-btn') && e.target.closest('.list-group-item-order')) {
                updateOrderQuantity(e.target, true);
            } else if (e.target.classList.contains('minus-btn') && e.target.closest('.list-group-item-order')) {
                updateOrderQuantity(e.target, false);
            }
        });

        // Function to fetch products from the API
        function fetchProducts() {
            console.log('Fetching products...');
            
            const apiUrl = 'https://core2.paradisehoteltomasmorato.com/integ/orlist.php?api_key=f5230f2a7c74ff308dbd06998debf755b9fa279d5beba28e9f0a86fd79e42383';
            console.log('API URL:', apiUrl);
            
            fetch(apiUrl)
                .then(response => {
                    console.log('API Response Status:', response.status);
                    return response.json();
                })
                .then(data => {
                    // Hide all loading spinners
                    document.querySelectorAll('.loading-spinner').forEach(spinner => {
                        spinner.style.display = 'none';
                    });
                    
                    // Log the data to see its structure
                    console.log('API response:', data);
                    
                    // Process the data - handle different possible response structures
                    displayProducts(data);
                })
                .catch(error => {
                    console.error('Error fetching products:', error);
                    // Show error message instead of spinners
                    document.querySelectorAll('.loading-spinner').forEach(spinner => {
                        spinner.innerHTML = '<p class="text-danger">Failed to load products. Please try again.</p>';
                    });
                });
        }

        // Call fetchProducts immediately to start loading data
        fetchProducts();

        // Function to display products by category
        function displayProducts(response) {
            // Check if response is valid and extract products array
            let products = [];
            // Handle different possible response structures
            if (Array.isArray(response)) {
                products = response;
            } else if (response && typeof response === 'object') {
                // Check if there's a data property or other property containing the array
                if (response.data && Array.isArray(response.data)) {
                    products = response.data;
                } else if (response.products && Array.isArray(response.products)) {
                    products = response.products;
                } else if (response.items && Array.isArray(response.items)) {
                    products = response.items;
                } else if (response.result && Array.isArray(response.result)) {
                    products = response.result;
                } else {
                    // Try to find an array property in the response
                    for (const key in response) {
                        if (Array.isArray(response[key])) {
                            products = response[key];
                            break;
                        }
                    }
                    // If still no array found, convert object to array if possible
                    if (products.length === 0) {
                        try {
                            products = Object.values(response);
                        } catch (e) {
                            console.error('Could not convert response to array:', e);
                        }
                    }
                }
            }
            console.log('Processed products:', products);
            if (!Array.isArray(products) || products.length === 0) {
                // Show error if no valid products found
                document.querySelectorAll('.loading-spinner').forEach(spinner => {
                    spinner.innerHTML = '<p class="text-danger">No products available. Please try again later.</p>';
                });
                return;
            }
            
            // Filter products by category based on prod_desc 
            const drinks = products.filter(product => 
                product.prod_desc && 
                product.prod_desc.toLowerCase().includes('drink') && 
                !product.prod_desc.toLowerCase().includes('alcohol'));
            const alcohols = products.filter(product => 
                product.prod_desc && 
                product.prod_desc.toLowerCase().includes('alcohol'));
            const koreanFood = products.filter(product => 
                product.prod_desc && 
                (product.prod_desc.toLowerCase().includes('korean food') || 
                product.prod_desc.toLowerCase().includes('korean delicacy')));
            const koreanRiceMeal = products.filter(product => 
                product.prod_desc && 
                (product.prod_desc.toLowerCase().includes('korean dish rice') || 
                product.prod_desc.toLowerCase().includes('dupbap')));
            
            // Display each category in the appropriate container
            displayCategoryItems('drinks-container', drinks);
            displayCategoryItems('alcohol-container', alcohols);
            displayCategoryItems('korean-food-container', koreanFood);
            displayCategoryItems('korean-rice-container', koreanRiceMeal);
        }

        // Function to display items within a category
        function displayCategoryItems(containerId, items) {
            const container = document.getElementById(containerId);
            if (!container) return;
            if (items.length === 0) {
                container.innerHTML = '<p class="text-muted text-center">No items available in this category</p>';
                return;
            }
            
            let html = '';
            items.forEach(item => {
                // Extract the filename from the prod_img field
                let imgFileName = item.prod_img;
                if (imgFileName && imgFileName.includes('/')) {
                    // If prod_img contains a path, extract just the filename
                    imgFileName = imgFileName.split('/').pop();
                }
                
                html += `
                <div class="list-group-item-order d-flex align-items-center border-0 shadow-sm p-1 mb-2">
                    <img src="../img/products/${imgFileName}" alt="${item.prod_name}" 
                         class="rounded me-3" style="width: 80px; height: 80px; object-fit: cover;"
                         onerror="this.src='../img/default-food.jpg'">
                    <div class="flex-grow-1">
                        <h6 class="fw-bold mb-1">${item.prod_name}</h6>
                        <p class="text-muted small mb-2">${item.prod_desc}</p>
                        <p class="fw-bold text-success mb-0">₱${item.prod_price}</p>
                    </div>
                    <div class="quantity-selector-order">
                        <button class="btn btn-outline-success minus-btn">−</button>
                        <input type="number" class="form-control text-center quantity-input-order" 
                               id="product-${item.prod_id}" name="${item.prod_name}" 
                               data-product-id="${item.prod_id}" 
                               data-product-price="${item.prod_price}"
                               min="0" value="0">
                        <button class="btn btn-outline-success plus-btn">+</button>
                    </div>
                </div>`;
            });
            container.innerHTML = html;
            
            // REMOVE THE EVENT LISTENERS HERE - we'll rely on delegate handlers instead
            // No need to attach direct event listeners since we have document-level delegates
        }

        // Add event listener for food order submission
        document.getElementById('submit-food-order').addEventListener('click', function() {
            // Collect order data
            let hasItems = false;
            let orderItems = [];
            document.querySelectorAll('.quantity-input-order').forEach(input => {
                const quantity = parseInt(input.value) || 0;
                if (quantity > 0) {
                    const productName = input.name;
                    const productId = input.getAttribute('data-product-id');
                    const productPrice = parseFloat(input.getAttribute('data-product-price')) || 0;
                    
                    orderItems.push({
                        id: productId,
                        name: productName,
                        quantity: quantity,
                        price: productPrice
                    });
                    hasItems = true;
                }
            });
            if (!hasItems) {
                alert('Please select at least one item to order.');
                return;
            }
            // Calculate order total
            const orderTotal = orderItems.reduce((total, item) => {
                return total + (item.price * item.quantity);
            }, 0);
            // Confirm order
            if (confirm(`Your order total is ₱${orderTotal.toFixed(2)}. Proceed with order?`)) {
                // Send food order to server
                const formData = new FormData();
                formData.append('process_food_order', '1');
                formData.append('items', JSON.stringify(orderItems));
                // Disable button and show loading spinner
                const submitBtn = document.getElementById('submit-food-order');
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
                fetch('req-room-service.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Server returned ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response:', data);
                    if (data.success) {
                        alert(`Your food order has been placed successfully!\nOrder #: ${data.order_code}\nTotal: ₱${data.total.toFixed(2)}`);
                        // Reset all quantity inputs
                        document.querySelectorAll('.quantity-input-order').forEach(input => {
                            input.value = '0';
                        });
                    } else {
                        const errorMsg = data.error ? data.error : 'Unknown error';
                        console.error('Order submission error:', errorMsg);
                        alert('Error submitting food order: ' + errorMsg);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error submitting food order: ' + error.message);
                })
                .finally(() => {
                    // Restore button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                });
            }
        });
    });
    </script>
</body>
</html>