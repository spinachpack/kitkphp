<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_number = clean($_POST['id_number']);
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = clean($_POST['first_name']);
    $last_name = clean($_POST['last_name']);
    $department = clean($_POST['department']);
    
    // Validation
    if (empty($id_number) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Check if ID or email already exists
        $check_sql = "SELECT id FROM users WHERE id_number = ? OR email = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ss", $id_number, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "ID Number or Email already registered";
        } else {
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_sql = "INSERT INTO users (id_number, email, password, first_name, last_name, department, role) VALUES (?, ?, ?, ?, ?, ?, 'user')";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ssssss", $id_number, $email, $hashed_password, $first_name, $last_name, $department);
            
            if ($stmt->execute()) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Registration failed. Please try again.";
            }
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
    <title>Register - KitKeeper</title>
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
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
            max-width: 600px;
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
            margin-bottom: 0;
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
        .input-group-text {
            background: #f8f9fa;
            border-right: none;
        }
        .form-control {
            border-left: none;
        }
        .form-control:focus + .input-group-text {
            border-color: #1e5a96;
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="logo-section">
            <i class="fas fa-box fa-3x" style="color: #1e5a96;"></i>
            <h2>KitKeeper</h2>
            <p class="text-muted">Create Your Account</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">First Name</label>
                    <input type="text" name="first_name" class="form-control" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-control" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">ID Number</label>
                <input type="text" name="id_number" class="form-control" placeholder="e.g., 22222222" value="<?php echo isset($_POST['id_number']) ? htmlspecialchars($_POST['id_number']) : ''; ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="your.email@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Department</label>
                <select name="department" class="form-select" required>
                    <option value="">Select Department</option>
                    <option value="Computer Science" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                    <option value="Engineering" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Engineering') ? 'selected' : ''; ?>>Engineering</option>
                    <option value="Business Administration" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Business Administration') ? 'selected' : ''; ?>>Business Administration</option>
                    <option value="Education" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Education') ? 'selected' : ''; ?>>Education</option>
                    <option value="Arts and Sciences" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Arts and Sciences') ? 'selected' : ''; ?>>Arts and Sciences</option>
                    <option value="Medical Technology" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Medical Technology') ? 'selected' : ''; ?>>Medical Technology</option>
                    <option value="Nursing" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Nursing') ? 'selected' : ''; ?>>Nursing</option>
                    <option value="Architecture" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Architecture') ? 'selected' : ''; ?>>Architecture</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" minlength="6" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" minlength="6" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="fas fa-user-plus"></i> Register
            </button>
        </form>

        <div class="text-center">
            <p class="mb-2">Already have an account? <a href="login.php" class="text-decoration-none">Login</a></p>
            <a href="index.php" class="text-muted text-decoration-none">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>