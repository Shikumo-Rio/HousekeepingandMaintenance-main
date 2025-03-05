<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="img/logo.webp">
    <style>
        .warning-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            text-align: center;
        }
        .warning-text {
            font-size: 1.5rem;
            color: red; /* Change to your preferred color */
            margin-top: 20px;
        }
        .logo {
            max-width: 150px; /* Adjust size as needed */
        }
    </style>
</head>
<body>
    <div class="warning-container">
        <img src="img/logo.webp" alt="Logo" class="logo">
        <div class="warning-text">
            I'm sorry you're not allowed to access this if you're not logged in with right usertype
        </div>
        <button class="btn btn-secondary mt-3" onclick="window.history.back();">Go Back</button>
    </div>
</body>
</html>
