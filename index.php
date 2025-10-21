<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KitKeeper - Equipment Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        .navbar {
            background: #1e5a96;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .hero-section {
            padding: 80px 0;
            background: white;
        }
        .btn-primary {
            background: #1e5a96;
            border: none;
        }
        .btn-primary:hover {
            background: #0d3b66;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-box"></i> KitKeeper
            </a>
            <div>
                <a href="login.php" class="btn btn-outline-light me-2">Login</a>
                <a href="register.php" class="btn btn-light">Register</a>
            </div>
        </div>
    </nav>

    <div class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 mb-4">KitKeeper</h1>
            <p class="lead mb-4">School Equipment Booking System</p>
            <p class="text-muted mb-4">Reserve sports equipment, audio-visual devices, and other school resources</p>
            <a href="register.php" class="btn btn-primary btn-lg me-2">Get Started</a>
            <a href="login.php" class="btn btn-outline-primary btn-lg">Sign In</a>
        </div>
    </div>

    <footer class="text-center py-4 bg-white border-top mt-5">
        <p class="text-muted mb-0">&copy; 2025 KitKeeper - University of Cebu Banilad</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>