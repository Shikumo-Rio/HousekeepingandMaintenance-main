<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Requests Overview</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
  <style>
    .header-actions {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      margin-bottom: 1rem;
    }
    .action-icon {
      cursor: pointer;
      margin-right: 0.5rem;
    }
    .action-icon.disabled {
      pointer-events: none;
      opacity: 0.5;
    }
  </style>
</head>
<body>
  <div class="container my-4">
    <h2 class="mb-4">Requests</h2>
    
    <!-- Table for Requests -->
    <table id="requestsTable" class="table table-striped table-bordered" style="width:100%">
      <thead>
        <tr>
          <th>ID</th>
          <th>Department</th>
          <th>Delivery Location</th>
          <th>Requester Name</th>
          <th>Requester Email</th>
          <th>Contact Number</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
  
  <!-- Modal for Request Details -->
  <div class="modal fade" id="viewRequestModal" tabindex="-1" aria-labelledby="viewRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewRequestModalLabel">Request Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="requestInfo" class="mb-4"></div>
          <h6>Request Items</h6>
          <table id="requestItemsTable" class="table table-striped table-bordered">
            <thead>
              <tr>
                <th>ID</th>
                <th>Category</th>
                <th>Item Name</th>
                <th>SKU</th>
                <th>Quantity</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
  
  <script>
    const apiEndpoint = "https://logistic1.paradisehoteltomasmorato.com/sub-modules/logistic1/warehouse/view_api.php";
    let fetchedData = null;
    
    // I-fetch ang data mula sa API
    async function fetchData() {
      try {
        const response = await fetch(apiEndpoint);
        const data = await response.json();
        if (data.requests && data.request_items && data.reservations) {
          fetchedData = data;
          populateRequestsTable(data.requests);
        } else {
          console.error("Incomplete response:", data);
        }
      } catch (error) {
        console.error("Error fetching data:", error);
      }
    }
    
    // I-populate ang requests table gamit ang fetched data at i-filter base sa department (pickup_location)
    function populateRequestsTable(requests) {
      const tbody = document.querySelector("#requestsTable tbody");
      tbody.innerHTML = "";
      
      // Filter para ipakita lang yung requests na may pickup_location na "housekeeping"
      // (Palitan ito ng "restaurant" kung kinakailangan)
      const filteredRequests = requests.filter(request => {
        return request.pickup_location.trim().toLowerCase() === "housekeeping";
      });
      
      filteredRequests.forEach(request => {
        // Gamitin ang requester_email para i-match sa reservation.contact_number (kung saan naka-store ang email)
        const matchingReservation = fetchedData.reservations.find(reservation => {
          return reservation.contact_number.trim().toLowerCase() === request.requester_email.trim().toLowerCase();
        });
        
        let statusText = "";
        let badgeClass = "";
        
        // Kung ang request.status ay "approved", i-check natin ang reservation status
        if (request.status.trim().toLowerCase() === "approved") {
          if (matchingReservation) {
            let resStatus = matchingReservation.status;
            let numericStatus = Number(resStatus);
            // Listahan ng mga valid numeric status codes para sa continuous flow
            const validStatusCodes = [1, 2, 7, 9, 8, 4];
            
            if (!validStatusCodes.includes(numericStatus)) {
              // Kung hindi valid ang status code, ipakita pa rin ang "Approved" (green)
              statusText = "Approved";
              badgeClass = "badge bg-success";
            } else {
              switch (numericStatus) {
                case 1:
                  statusText = "Pending";
                  // I-map ang "Pending" sa blue (primary)
                  badgeClass = "badge bg-primary";
                  break;
                case 2:
                  statusText = "In Progress";
                  badgeClass = "badge bg-warning";
                  break;
                case 7:
                  statusText = "In-Transit";
                  badgeClass = "badge bg-primary";
                  break;
                case 9:
                  statusText = "Completed";
                  badgeClass = "badge bg-success";
                  break;
                case 8:
                  statusText = "Delayed (Under Maintenance)";
                  badgeClass = "badge bg-danger";
                  break;
                case 4:
                  statusText = "Cancelled";
                  badgeClass = "badge bg-danger";
                  break;
                default:
                  statusText = "Unknown";
                  badgeClass = "badge bg-secondary";
                  break;
              }
            }
          } else {
            // Walang reservation record, ipakita pa rin ang "Approved" (green)
            statusText = "Approved";
            badgeClass = "badge bg-success";
          }
        } else {
          // Kung hindi approved, gamitin ang status na nasa requests table (hal. Rejected)
          statusText = request.status;
          if (statusText.trim().toLowerCase() === "rejected") {
            badgeClass = "badge bg-danger";
          } else if (statusText.trim().toLowerCase() === "pending") {
            // Map pending sa blue (primary)
            badgeClass = "badge bg-primary";
          } else {
            badgeClass = "badge bg-secondary";
          }
        }
        
        const eyeIcon = `<i class="bi bi-eye action-icon text-primary" onclick="viewRequest(${request.id})" title="View"></i>`;
        const editIcon = `<i class="bi bi-pencil action-icon text-warning" onclick="editRequest(${request.id})" title="Edit"></i>`;
        const actionHTML = eyeIcon + " " + editIcon;
        
        const row = `
          <tr>
            <td>${request.id}</td>
            <td>${request.pickup_location}</td>
            <td>${request.delivery_location}</td>
            <td>${request.requester_name}</td>
            <td>${request.requester_email}</td>
            <td>${request.contact_number}</td>
            <td><span class="${badgeClass}">${statusText}</span></td>
            <td>${actionHTML}</td>
          </tr>
        `;
        tbody.insertAdjacentHTML("beforeend", row);
      });
    }
    
    // Ipakita ang detalye ng request sa modal
    function viewRequest(requestId) {
      const request = fetchedData.requests.find(r => r.id == requestId);
      if (!request) return;
      
      const requestInfoHTML = `
        <p><strong>ID:</strong> ${request.id}</p>
        <p><strong>Department:</strong> ${request.pickup_location}</p>
        <p><strong>Delivery Location:</strong> ${request.delivery_location}</p>
        <p><strong>Requester Name:</strong> ${request.requester_name}</p>
        <p><strong>Requester Email:</strong> ${request.requester_email}</p>
        <p><strong>Contact Number:</strong> ${request.contact_number}</p>
      `;
      document.getElementById("requestInfo").innerHTML = requestInfoHTML;
      
      const items = fetchedData.request_items.filter(item => item.request_id == requestId);
      const itemsTbody = document.querySelector("#requestItemsTable tbody");
      itemsTbody.innerHTML = "";
      if (items.length > 0) {
        items.forEach(item => {
          const itemRow = `
            <tr>
              <td>${item.id}</td>
              <td>${item.category}</td>
              <td>${item.item_name}</td>
              <td>${item.sku}</td>
              <td>${item.quantity}</td>
            </tr>
          `;
          itemsTbody.insertAdjacentHTML("beforeend", itemRow);
        });
      } else {
        itemsTbody.innerHTML = `<tr><td colspan="5" class="text-center">No items found.</td></tr>`;
      }
      
      const viewModal = new bootstrap.Modal(document.getElementById("viewRequestModal"));
      viewModal.show();
    }
    
    // Halimbawa ng function para sa pag-edit
    function editRequest(requestId) {
      alert("Edit request " + requestId);
    }
    
    // I-load ang data at mag-poll kada 10 segundo para sa automatic updates
    fetchData();
    setInterval(fetchData, 10000);
  </script>
</body>
</html>
