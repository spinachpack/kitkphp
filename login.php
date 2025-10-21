<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_or_email = clean($_POST['id_or_email']);
    $password = $_POST['password'];
    
    if (empty($id_or_email) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        // Check if input is email or ID number
        $sql = "SELECT * FROM users WHERE (id_number = ? OR email = ?) AND status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $id_or_email, $id_or_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['id_number'] = $user['id_number'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['profile_picture'] = $user['profile_picture'];
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    redirect('admin/dashboard.php');
                } 
                elseif ($user['role'] === 'department') {
                    redirect('department/dashboard.php');
                }
                else {
                    redirect('user/dashboard.php');
                }
            } else {
                $error = "Invalid credentials";
            }
        } else {
            $error = "Invalid credentials";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KitKeeper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0d3b66 0%, #1e5a96 50%, #4a9dd6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 100%;
            padding: 40px;
            margin: 20px;
        }
        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-section h2 {
            color: #1e5a96;
            font-weight: bold;
            margin-top: 15px;
        }
        .logo-section p {
            color: #666;
        }
        .form-control:focus {
            border-color: #1e5a96;
            box-shadow: 0 0 0 0.2rem rgba(30, 90, 150, 0.25);
        }
        .btn-primary {
            background: #1e5a96;
            border: none;
            padding: 12px;
            font-size: 1.1rem;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background: #0d3b66;
            transform: translateY(-2px);
        }
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }
        .input-wrapper {
            position: relative;
        }
        .input-wrapper input {
            padding-left: 45px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-section">
            <i class="fas fa-box fa-3x" style="color: #1e5a96;"></i>
            <h2>KitKeeper</h2>
            <p class="text-muted">WEB PORTAL</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3 input-wrapper">
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="id_or_email" class="form-control" placeholder="ID Number or Email" required>
            </div>

            <div class="mb-3 input-wrapper">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="remember">
                <label class="form-check-label" for="remember">
                    Remember Me
                </label>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div class="text-center">
            <a href="forgot_password.php" class="d-block mb-2 text-decoration-none">Forgot Your Password?</a>
            <a href="register.php" class="d-block mb-2 text-decoration-none">Register</a>
            <a href="index.php" class="text-muted text-decoration-none">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>