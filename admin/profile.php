<?php
require_once '../config.php';

if (!isLoggedIn() || !canManageEquipment()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle profile picture upload
if (isset($_POST['update_picture'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
            $upload_path = '../' . PROFILE_UPLOAD_DIR . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                if ($_SESSION['profile_picture'] != 'default-avatar.png') {
                    $old_file = '../' . PROFILE_UPLOAD_DIR . $_SESSION['profile_picture'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                
                $sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $new_filename, $user_id);
                $stmt->execute();
                
                $_SESSION['profile_picture'] = $new_filename;
                $success = "Profile picture updated successfully!";
            } else {
                $error = "Failed to upload file.";
            }
        } else {
            $error = "Invalid file type. Only JPG, JPEG, PNG, and GIF allowed.";
        }
    }
}

// Handle profile information update
if (isset($_POST['update_profile'])) {
    $first_name = clean($_POST['first_name']);
    $last_name = clean($_POST['last_name']);
    $email = clean($_POST['email']);
    $department = clean($_POST['department']);
    
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Email already in use.";
        } else {
            $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, department = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $first_name, $last_name, $email, $department, $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['email'] = $email;
                $success = "Profile updated successfully!";
            } else {
                $error = "Failed to update profile.";
            }
        }
    }
}

$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - KitKeeper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #1e5a96;
        }
        body {
            background: #f5f7fa;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, var(--primary-blue), #0d3b66);
            color: white;
            position: fixed;
            width: 250px;
        }
        .sidebar .profile-section {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        .sidebar .profile-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid white;
            object-fit: cover;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 15px 25px;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: white;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .profile-picture-section {
            text-align: center;
            padding: 30px;
        }
        .profile-picture-large {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid var(--primary-blue);
            object-fit: cover;
        }
        .upload-btn-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        .upload-btn {
            border: 2px solid var(--primary-blue);
            color: var(--primary-blue);
            background-color: white;
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
        }
        .upload-btn-wrapper input[type=file] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                min-height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="profile-section">
            <img src="<?php echo BASE_URL . PROFILE_UPLOAD_DIR . $_SESSION['profile_picture']; ?>" class="profile-img" alt="Profile">
            <h5 class="mt-3 mb-0"><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></h5>
            <small class="text-white-50"><?php echo isAdmin() ? 'Administrator' : 'Department Staff'; ?></small>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
            <a class="nav-link" href="manage_equipment.php">
                <i class="fas fa-box me-2"></i> Manage Equipment
            </a>
            <a class="nav-link" href="manage_bookings.php">
                <i class="fas fa-calendar-check me-2"></i> Manage Bookings
            </a>
            <?php if (isAdmin()): ?>
            <a class="nav-link" href="manage_users.php">
                <i class="fas fa-users me-2"></i> Manage Users
            </a>
            <a class="nav-link" href="reports.php">
                <i class="fas fa-chart-bar me-2"></i> Reports
            </a>
            <?php endif; ?>
            <a class="nav-link active" href="profile.php">
                <i class="fas fa-user me-2"></i> My Profile
            </a>
            <a class="nav-link" href="change_password.php">
                <i class="fas fa-key me-2"></i> Change Password
            </a>
            <a class="nav-link" href="../logout.php">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="mb-4">
            <h4>My Profile</h4>
            <p class="text-muted">Manage your profile information</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-4 col-md-12 mb-4">
                <div class="card">
                    <div class="card-body profile-picture-section">
                        <img src="<?php echo BASE_URL . PROFILE_UPLOAD_DIR . $user['profile_picture']; ?>" class="profile-picture-large mb-3" id="profilePreview" alt="Profile">
                        <h5><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h5>
                        <p class="text-muted mb-3"><?php echo $user['id_number']; ?></p>
                        
                        <form method="POST" enctype="multipart/form-data" id="pictureForm">
                            <div class="upload-btn-wrapper">
                                <button class="upload-btn" type="button">
                                    <i class="fas fa-camera me-2"></i> Change Picture
                                </button>
                                <input type="file" name="profile_picture" accept="image/*" onchange="previewImage(this); document.getElementById('pictureForm').submit();">
                            </div>
                            <input type="hidden" name="update_picture" value="1">
                        </form>
                        <small class="text-muted d-block mt-2">JPG, JPEG, PNG, or GIF (Max 5MB)</small>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 col-md-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">First Name</label>
                                    <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">ID Number</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['id_number']); ?>" disabled>
                                <small class="text-muted">ID number cannot be changed</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Department</label>
                                <select name="department" class="form-select" required>
                                    <option value="">Select Department</option>
                                    <?php
                                    $departments = ['Computer Science', 'Engineering', 'Business Administration', 'Education', 'Arts and Sciences', 'Medical Technology', 'Nursing', 'Architecture', 'Administration', 'Sports Department'];
                                    foreach ($departments as $dept) {
                                        $selected = ($user['department'] == $dept) ? 'selected' : '';
                                        echo "<option value='$dept' $selected>$dept</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>