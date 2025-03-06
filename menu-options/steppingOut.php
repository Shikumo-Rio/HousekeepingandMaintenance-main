<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stepping Out</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>
<body>
    <div class="menu-container container p-2">
        <!-- Header Section -->
        <div class="menu-header d-flex align-items-center justify-content-between py-2 p-3">
            <div class="d-flex align-items-center">
                <img src="../img/logo.webp" alt="User Icon" class="rounded-circle me-2" width="40" height="40">
                <div>
                    <h5 class="mb-0">Paradise Hotel</h5>
                </div>
            </div>
        </div>
        <h5 class="mt-4 m-2 mb-4 fw-semibold">Stepping Out</h5>
        <p class="text-center text-muted mb-4">Let us know if you need any services while you're away.</p>

        <!-- Housekeeping Request -->
        <div class="card p-4 mb-3 shadow-sm">
            <h5 class="fw-semibold mb-3"><i class="fas fa-broom me-2"></i> Housekeeping While Away</h5>
            <p>Would you like our housekeeping team to clean your room while you're out? You can request fresh towels, bed making, or a full room cleaning.</p>
            <button class="btn btn-outline-success w-100">Request Housekeeping</button>
        </div>

        <!-- Maintenance Issue Reporting -->
        <div class="card p-4 mb-3 shadow-sm">
            <h5 class="fw-semibold mb-3"><i class="fas fa-tools me-2"></i> Report Maintenance Issue</h5>
            <p>If you noticed any maintenance issues in your room (e.g., air conditioning not working, a leaking faucet), let us know. We can address it while you're out.</p>
            <button class="btn btn-outline-success w-100">Report an Issue</button>
        </div>

        <!-- Securing Belongings -->
        <div class="card p-4 mb-3 shadow-sm">
            <h5 class="fw-semibold mb-3"><i class="fas fa-lock me-2"></i> Secure Your Belongings</h5>
            <p>Before stepping out, ensure that valuable items are safely stored. If you need assistance with securing items, our front desk is happy to help.</p>
        </div>

        <!-- Notify Front Desk -->
        <div class="card p-4 mb-3 shadow-sm">
            <h5 class="fw-semibold mb-3"><i class="fas fa-concierge-bell me-2"></i> Notify Front Desk</h5>
            <p>If you’ll be away for an extended period, you may inform the front desk for security and service purposes.</p>
            <button class="btn btn-outline-success w-100">Notify Front Desk</button>
        </div>

        <!-- Return Reminder -->
        <div class="text-center mt-4">
            <p class="text-muted">Enjoy your time outside! Let us know if you need anything upon your return.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
