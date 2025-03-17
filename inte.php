<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Warehouse Items & Request (Integration UI)</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- DataTables CSS (optional) -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
  <style>
    /* I-position ang submit button sa modal header sa kanan */
    .modal-header .btn-submit {
      position: absolute;
      right: 1rem;
      top: 1rem;
    }
    /* I-adjust ang positioning ng Add Request button */
    .header-actions {
      display: flex;
      justify-content: flex-end;
      align-items: center;
    }
    .header-actions button {
      margin-left: 1rem;
    }
  </style>
</head>
<body>
  <div class="container my-4">
    <div class="d-flex align-items-center mb-4">
      <!-- Page Tabs for Items -->
      <ul class="nav nav-tabs flex-grow-1" id="itemTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="hotel-tab" data-bs-toggle="tab" data-bs-target="#hotel" type="button" role="tab">Hotel</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="restaurant-tab" data-bs-toggle="tab" data-bs-target="#restaurant" type="button" role="tab">Restaurant</button>
        </li>
      </ul>
      <!-- Add Request button sa kanan ng page tabs -->
      <div class="header-actions">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#requestModal">
          Add Request
        </button>
      </div>
    </div>
    
    <!-- Tab Content -->
    <div class="tab-content" id="itemTabsContent">
      <!-- Hotel Items Table -->
      <div class="tab-pane fade show active" id="hotel" role="tabpanel">
        <table id="hotelTable" class="table table-striped table-bordered mt-3" style="width:100%">
          <thead>
            <tr>
              <th>ID</th>
              <th>Category</th>
              <th>Item Name</th>
              <th>SKU</th>
              <th>Quantity</th>
              <th>Expiration Date</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <!-- Restaurant Items Table -->
      <div class="tab-pane fade" id="restaurant" role="tabpanel">
        <table id="restaurantTable" class="table table-striped table-bordered mt-3" style="width:100%">
          <thead>
            <tr>
              <th>ID</th>
              <th>Category</th>
              <th>Item Name</th>
              <th>SKU</th>
              <th>Quantity</th>
              <th>Expiration Date</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
    
  </div>
  
  <!-- Request Modal -->
  <div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <!-- Modal Header with Submit Button -->
        <div class="modal-header position-relative">
          <h5 class="modal-title" id="requestModalLabel">Request Form</h5>
          <button id="submitRequestBtn" type="button" class="btn btn-primary btn-submit">Submit Request</button>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
        <!-- Modal Body -->
        <div class="modal-body">
          <!-- Requester Information Section -->
          <div class="mb-4">
            <h6>Requester Information</h6>
            <div class="row">
              <div class="col-md-3 mb-2">
                <label for="pickup_location" class="form-label">Department</label>
                <input type="text" class="form-control" id="pickup_location" placeholder="Enter Department" required>
              </div>
              <div class="col-md-3 mb-2">
                <label for="delivery_location" class="form-label">Delivery Location</label>
                <input type="text" class="form-control" id="delivery_location" placeholder="Enter delivery location" required>
              </div>
              <div class="col-md-3 mb-2">
                <label for="requester_name" class="form-label">Requester Name</label>
                <input type="text" class="form-control" id="requester_name" placeholder="Enter your name" required>
              </div>
              <div class="col-md-3 mb-2">
                <label for="requester_email" class="form-label">Requester Email</label>
                <input type="email" class="form-control" id="requester_email" placeholder="Enter your email" required>
              </div>
              <div class="col-md-3 mb-2">
                <label for="contact_number" class="form-label">Contact Number</label>
                <input type="tel" class="form-control" id="contact_number" placeholder="Enter contact number" required>
              </div>
            </div>
          </div>
          
          <!-- Item Request Details Section -->
          <div class="mb-3">
            <h6>Item Request Details</h6>
            <div class="table-responsive">
              <table id="itemsTable" class="table table-striped">
                <thead>
                  <tr>
                    <th>Category</th>
                    <th>Item Name</th>
                    <th>SKU</th>
                    <th>Quantity</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>
                      <input type="text" class="form-control" name="category[]" placeholder="Enter category" required>
                    </td>
                    <td>
                      <input type="text" class="form-control" name="item[]" placeholder="Enter item name" required>
                    </td>
                    <td>
                      <input type="text" class="form-control" name="sku[]" placeholder="Enter SKU" required>
                    </td>
                    <td>
                      <input type="number" class="form-control" name="quantity[]" min="1" placeholder="Enter quantity" required>
                    </td>
                    <td>
                      <button type="button" class="btn btn-success" onclick="addRow()">Add</button>
                      <button type="button" class="btn btn-danger" onclick="removeRow(this)">Remove</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <!-- End of Modal Body -->
      </div>
    </div>
  </div>
  
  <!-- Bootstrap JS and dependencies -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- jQuery and DataTables JS (optional) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
  
  <script>
    // Ituro ang tamang endpoint (table.php) na may API parameters kung kinakailangan.
    const apiEndpoint = "https://logistic1.paradisehoteltomasmorato.com/sub-modules/logistic1/warehouse/table.php?api=1&api_key=20054d820a3ba1bae07591397d8cacdf";

    // Function para kunin ang data mula sa endpoint
    async function fetchData() {
      try {
        const response = await fetch(apiEndpoint);
        const data = await response.json();
        if (data.items2 && data.item_batches) {
          return data;
        } else {
          console.error("Hindi kompleto ang response:", data);
          return { items2: [], item_batches: [] };
        }
      } catch (error) {
        console.error("Error fetching data:", error);
        return { items2: [], item_batches: [] };
      }
    }

    // Function para gumawa ng mapping ng item_batches base sa item_id
    function mapBatches(batches) {
      const map = {};
      batches.forEach(batch => {
        // Kung may maraming batch para sa isang item, piliin ang unang nahanap na may expiration_date
        if (!map[batch.item_id] && batch.expiration_date) {
          map[batch.item_id] = batch.expiration_date;
        }
      });
      return map;
    }

    // Function para i-populate ang tables batay sa type (hotel o restaurant)
    function populateTables(data) {
      const items = data.items2;
      const batchesMap = mapBatches(data.item_batches);

      // I-clear ang laman ng table bodies
      document.querySelector("#hotelTable tbody").innerHTML = '';
      document.querySelector("#restaurantTable tbody").innerHTML = '';

      // I-loop ang mga items at i-filter base sa type
      items.forEach(item => {
        // Kunin ang expiration date; kung wala, gamitin ang expiration date mula sa batchesMap
        let expirationDate = item.expiration_date;
        if (!expirationDate && batchesMap[item.id]) {
          expirationDate = batchesMap[item.id];
        }
        // Kapag wala pa rin, ipakita ang "no expiration"
        expirationDate = expirationDate ? expirationDate : "no expiration";
        
        const row = `
          <tr>
            <td>${item.id}</td>
            <td>${item.category}</td>
            <td>${item.item_name}</td>
            <td>${item.sku}</td>
            <td>${item.quantity}</td>
            <td>${expirationDate}</td>
          </tr>
        `;
        if (item.type === 'hotel') {
          document.querySelector("#hotelTable tbody").insertAdjacentHTML('beforeend', row);
        } else if (item.type === 'restaurant') {
          document.querySelector("#restaurantTable tbody").insertAdjacentHTML('beforeend', row);
        }
      });
    }

    // Loader function para tawagin ang fetchData at populateTables
    async function loadData() {
      const data = await fetchData();
      populateTables(data);
    }
    loadData();

    // Functions para sa pag-add at remove ng row sa item request table...
    function addRow() {
      const tableBody = document.querySelector("#itemsTable tbody");
      const lastRow = tableBody.rows[tableBody.rows.length - 1];
      const newRow = lastRow.cloneNode(true);
      newRow.querySelectorAll('input').forEach(input => {
        input.value = '';
      });
      tableBody.appendChild(newRow);
    }

    function removeRow(button) {
      const row = button.closest('tr');
      const tableBody = row.parentNode;
      if (tableBody.rows.length > 1) {
        tableBody.removeChild(row);
      } else {
        alert("At least one item is required.");
      }
    }

    // Submission handler para sa request form...
    document.getElementById("submitRequestBtn").addEventListener("click", async function() {
      const pickup_location = document.getElementById("pickup_location").value.trim();
      const delivery_location = document.getElementById("delivery_location").value.trim();
      const requester_name = document.getElementById("requester_name").value.trim();
      const requester_email = document.getElementById("requester_email").value.trim();
      const contact_number = document.getElementById("contact_number").value.trim();
      const item_type = 'general'; // Adjust kung kinakailangan

      const items = [];
      const rows = document.querySelectorAll("#itemsTable tbody tr");
      rows.forEach(row => {
        const category = row.querySelector("input[name='category[]']").value.trim();
        const item_name = row.querySelector("input[name='item[]']").value.trim();
        const sku = row.querySelector("input[name='sku[]']").value.trim();
        const quantity = parseInt(row.querySelector("input[name='quantity[]']").value.trim()) || 0;
        if (category && item_name && sku && quantity > 0) {
          items.push({ category, item_name, sku, quantity });
        }
      });

      if (!pickup_location || !delivery_location || !requester_name || !requester_email || !contact_number) {
        alert("Kumpletohin ang lahat ng requester information.");
        return;
      }

      const payload = {
        pickup_location,
        delivery_location,
        requester_name,
        requester_email,
        contact_number,
        item_type,
        items
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
          window.location.reload();
        } else {
          alert("Error: " + result.message);
        }
      } catch (error) {
        console.error("Error submitting request:", error);
        alert("Error submitting request. Check console for details.");
      }
    });
  </script>
  
</body>
</html>
<?php
ob_end_flush();
?>
