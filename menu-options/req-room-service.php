<?php
session_start();
require_once("../database.php");

if (!isset($_SESSION['verified']) || !isset($_SESSION['uname'])) {
    header("Location: ../index.html");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uname = $_SESSION['uname'];
    $room = $_SESSION['room_number'];
    $request = "Request Amenities";
    $status = "Pending";
    
    // Build details string from amenities
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
                                    <input type="number" class="form-control text-center quantity-input" id="water" name="blanket" min="0" value="0">
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
                            <button class="nav-link active" id="fastfood-tab" data-bs-toggle="pill" data-bs-target="#fastfood" type="button" role="tab" aria-controls="fastfood" aria-selected="true">
                                Fast Food
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="korean-tab" data-bs-toggle="pill" data-bs-target="#korean" type="button" role="tab" aria-controls="korean" aria-selected="false">
                                Korean
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="japanese-tab" data-bs-toggle="pill" data-bs-target="#japanese" type="button" role="tab" aria-controls="japanese" aria-selected="false">
                                Japanese
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="filipino-tab" data-bs-toggle="pill" data-bs-target="#filipino" type="button" role="tab" aria-controls="filipino" aria-selected="false">
                                Filipino
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Meal Category Content -->
                <div class="tab-content" id="meal-category-content">
                    <div class="tab-pane fade show active" id="fastfood" role="tabpanel" aria-labelledby="fastfood-tab">
                        <div class="container">
                            <div class="list-group-order mt-2">
                                <!-- Burger -->
                                <div class="list-group-item-order d-flex align-items-center border-0 shadow-sm p-1">
                                    <img src="img/burger.jpg" alt="Burger" class="rounded me-3" style="width: 80px; height: 80px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold mb-1">Cheeseburger</h6>
                                        <p class="text-muted small mb-2">Juicy grilled beef patty with melted cheese.</p>
                                    </div>
                                    <!-- Quantity Selector -->
                                    <div class="quantity-selector-order">
                                        <button class="btn btn-outline-success minus-btn">−</button>
                                        <input type="number" class="form-control text-center quantity-input-order" id="burger" name="burger" min="0" value="0">
                                        <button class="btn btn-outline-success plus-btn">+</button>
                                    </div>
                                </div>

                                <!-- Fries -->
                                <div class="list-group-item-order d-flex align-items-center border-0 shadow-sm p-1">
                                    <img src="img/fries.jpg" alt="Fries" class="rounded me-3" style="width: 80px; height: 80px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold mb-1">Crispy Fries</h6>
                                        <p class="text-muted small mb-2">Golden, crispy fries served hot.</p>
                                    </div>
                                    <!-- Quantity Selector -->
                                    <div class="quantity-selector-order">
                                        <button class="btn btn-outline-success minus-btn">−</button>
                                        <input type="number" class="form-control text-center quantity-input-order" id="fries" name="fries" min="0" value="0">
                                        <button class="btn btn-outline-success plus-btn">+</button>
                                    </div>
                                </div>

                                <!-- Pizza -->
                                <div class="list-group-item-order d-flex align-items-center border-0 shadow-sm p-1">
                                    <img src="img/pizza.jpg" alt="Pizza" class="rounded me-3" style="width: 80px; height: 80px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold mb-1">Pepperoni Pizza</h6>
                                        <p class="text-muted small mb-2">Delicious cheesy pizza topped with pepperoni.</p>
                                    </div>
                                    <!-- Quantity Selector -->
                                    <div class="quantity-selector-order">
                                        <button class="btn btn-outline-success minus-btn">−</button>
                                        <input type="number" class="form-control text-center quantity-input-order" id="pizza" name="pizza" min="0" value="0">
                                        <button class="btn btn-outline-success plus-btn">+</button>
                                    </div>
                                </div>
                            </div>  
                        </div>
                    </div>

                    <div class="tab-pane fade" id="korean" role="tabpanel" aria-labelledby="korean-tab">
                        <h5 class="fw-bold text-center">Korean Menu</h5>
                        <p class="text-center">Kimchi, Bibimbap, Samgyeopsal, and more.</p>
                    </div>
                    <div class="tab-pane fade" id="japanese" role="tabpanel" aria-labelledby="japanese-tab">
                        <h5 class="fw-bold text-center">Japanese Menu</h5>
                        <p class="text-center">Sushi, Ramen, Tempura, and more.</p>
                    </div>
                    <div class="tab-pane fade" id="filipino" role="tabpanel" aria-labelledby="filipino-tab">
                        <h5 class="fw-bold text-center">Filipino Menu</h5>
                        <p class="text-center">Adobo, Sinigang, Lechon, and more.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


    <script>
    document.addEventListener("DOMContentLoaded", function () {
        function updateQuantity(button, isIncrement) {
            let input = button.closest(".list-group-item").querySelector(".quantity-input");
            if (input) {
                let value = parseInt(input.value, 10) || 0;
                if (isIncrement) {
                    input.value = value + 1;
                } else {
                    input.value = value > 0 ? value - 1 : 0;
                }
            }
        }

        document.querySelectorAll(".plus-btn").forEach(button => {
            button.addEventListener("click", function () {
                updateQuantity(this, true);
            });
        });

        document.querySelectorAll(".minus-btn").forEach(button => {
            button.addEventListener("click", function () {
                updateQuantity(this, false);
            });
        });

        document.querySelectorAll(".quantity-input").forEach(input => {
            input.addEventListener("input", function () {
                if (isNaN(this.value) || this.value < 0) {
                    this.value = 0;
                }
            });
        });
    });



    document.addEventListener("DOMContentLoaded", function () {
        function updateQuantity(button, isIncrement) {
            let input = button.closest(".list-group-item-order").querySelector(".quantity-input-order");
            if (input) {
                let value = parseInt(input.value, 10) || 0;
                if (isIncrement) {
                    input.value = value + 1;
                } else {
                    input.value = value > 0 ? value - 1 : 0;
                }
            }
        }

        document.querySelectorAll(".plus-btn").forEach(button => {
            button.addEventListener("click", function () {
                updateQuantity(this, true);
            });
        });

        document.querySelectorAll(".minus-btn").forEach(button => {
            button.addEventListener("click", function () {
                updateQuantity(this, false);
            });
        });

        document.querySelectorAll(".quantity-input-order").forEach(input => {
            input.addEventListener("input", function () {
                if (isNaN(this.value) || this.value < 0) {
                    this.value = 0;
                }
            });
        });
    });

    document.getElementById('amenities-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Collect all amenities quantities
        const formData = new FormData(this);
        const amenitiesWithQuantity = {};
        
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
    </script>

</body>
</html>