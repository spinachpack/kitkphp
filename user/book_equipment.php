<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$error = '';
$success = '';

// Get equipment ID
$equipment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get equipment details
$sql = "SELECT e.*, c.name as category_name FROM equipment e 
        LEFT JOIN categories c ON e.category_id = c.id 
        WHERE e.id = ? AND e.status = 'available'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $equipment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('browse_equipment.php');
}

$equipment = $result->fetch_assoc();

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = clean($_POST['start_date']);
    $end_date = clean($_POST['end_date']);
    $start_time = clean($_POST['start_time']);
    $end_time = clean($_POST['end_time']);
    $purpose = clean($_POST['purpose']);
    $user_id = $_SESSION['user_id'];
    
    // Validation
    if (empty($start_date) || empty($end_date) || empty($start_time) || empty($end_time) || empty($purpose)) {
        $error = "All fields are required";
    } elseif (strtotime($start_date) < strtotime(date('Y-m-d'))) {
        $error = "Start date cannot be in the past";
    } elseif (strtotime($end_date) < strtotime($start_date)) {
        $error = "End date must be after start date";
    } elseif ($start_date === $end_date && strtotime($end_time) <= strtotime($start_time)) {
        $error = "End time must be after start time";
    } else {
        // Check for conflicts - if there's an approved or pending reservation during this time
        $conflict_sql = "SELECT COUNT(*) as conflicts FROM reservations 
                        WHERE equipment_id = ? 
                        AND status IN ('pending', 'approved')
                        AND (
                            (start_date <= ? AND end_date >= ?)
                            OR (start_date <= ? AND end_date >= ?)
                            OR (start_date >= ? AND end_date <= ?)
                        )";
        $stmt = $conn->prepare($conflict_sql);
        $stmt->bind_param("issssss", $equipment_id, $start_date, $start_date, $end_date, $end_date, $start_date, $end_date);
        $stmt->execute();
        $conflicts = $stmt->get_result()->fetch_assoc()['conflicts'];
        
        if ($conflicts > 0) {
            $error = "This equipment is already booked for the selected dates. Please choose different dates.";
        } elseif ($equipment['available_quantity'] < 1) {
            $error = "This equipment is currently unavailable";
        } else {
            // Insert reservation
            $insert_sql = "INSERT INTO reservations (user_id, equipment_id, start_date, end_date, start_time, end_time, purpose, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("iisssss", $user_id, $equipment_id, $start_date, $end_date, $start_time, $end_time, $purpose);
            
            if ($stmt->execute()) {
                // Create notification
                $message = "Your booking request for " . $equipment['name'] . " has been submitted and is pending approval.";
                $notif_sql = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'booking')";
                $stmt = $conn->prepare($notif_sql);
                $stmt->bind_param("is", $user_id, $message);
                $stmt->execute();
                
                $success = "Booking request submitted successfully! You will be notified once it's approved.";
            } else {
                $error = "Failed to submit booking request. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Equipment - KitKeeper</title>
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
        .equipment-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 10px;
        }
        .equipment-placeholder {
            width: 100%;
            height: 300px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="profile-section">
            <img src="<?php echo BASE_URL . PROFILE_UPLOAD_DIR . $_SESSION['profile_picture']; ?>" class="profile-img" alt="Profile">
            <h5 class="mt-3 mb-0"><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></h5>
            <small class="text-white-50"><?php echo $_SESSION['id_number']; ?></small>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>
            <a class="nav-link active" href="browse_equipment.php">
                <i class="fas fa-box me-2"></i> Browse Equipment
            </a>
            <a class="nav-link" href="my_bookings.php">
                <i class="fas fa-calendar-check me-2"></i> My Bookings
            </a>
            <a class="nav-link" href="notifications.php">
                <i class="fas fa-bell me-2"></i> Notifications
            </a>
            <a class="nav-link" href="profile.php">
                <i class="fas fa-user me-2"></i> My Profile
            </a>
            <a class="nav-link" href="../logout.php">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="mb-4">
            <a href="browse_equipment.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Equipment
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <div class="mt-2">
                    <a href="my_bookings.php" class="btn btn-sm btn-success">View My Bookings</a>
                    <a href="browse_equipment.php" class="btn btn-sm btn-outline-success">Browse More Equipment</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($equipment['image'])): ?>
                            <img src="<?php echo BASE_URL . EQUIPMENT_UPLOAD_DIR . $equipment['image']; ?>" class="equipment-image mb-3" alt="<?php echo htmlspecialchars($equipment['name']); ?>">
                        <?php else: ?>
                            <div class="equipment-placeholder mb-3">
                                <i class="fas fa-box fa-5x text-white"></i>
                            </div>
                        <?php endif; ?>
                        
                        <span class="badge bg-primary mb-2"><?php echo htmlspecialchars($equipment['category_name']); ?></span>
                        <h3><?php echo htmlspecialchars($equipment['name']); ?></h3>
                        <p class="text-muted"><?php echo htmlspecialchars($equipment['description']); ?></p>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <strong>Total Quantity:</strong>
                            <span><?php echo $equipment['quantity']; ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <strong>Available:</strong>
                            <span class="text-success">
                                <i class="fas fa-check-circle"></i> <?php echo $equipment['available_quantity']; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Book This Equipment</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" name="start_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">End Date</label>
                                    <input type="date" name="end_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Start Time</label>
                                    <input type="time" name="start_time" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">End Time</label>
                                    <input type="time" name="end_time" class="form-control" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Purpose of Use</label>
                                <textarea name="purpose" class="form-control" rows="4" placeholder="Please describe why you need this equipment..." required></textarea>
                                <small class="text-muted">Provide detailed information about your intended use</small>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                <strong>Note:</strong> Your booking request will be reviewed by an administrator. You will receive a notification once it's approved or rejected.
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-paper-plane"></i> Submit Booking Request
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>