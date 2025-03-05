<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="chatbotlogin.css">
    <title>Checking</title>
</head>
<body class="bg-light d-flex justify-content-center align-items-center" style="height: 100vh;">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-10 col-sm-8 col-md-6 col-lg-2">
                <div class="card shadow-lg p-4">
                    <div class="card-body">
                        <!-- Logo Image -->
                        <img src="../img/logo.webp" alt="Logo" class="img-fluid mb-3 mx-auto d-block" style="max-width: 60px;">
                        <h5 class="card-title text-center mb-4">Enter your name to verify</h5>
                        <form action="verify.php" method="POST">
                            <div class="mb-3">
                                <input type="text" class="form-control" placeholder="Ex. Reorio" name="uname" required>
                            </div>
                            <button type="submit" class="w-100">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS (Optional for interactivity like modals) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
