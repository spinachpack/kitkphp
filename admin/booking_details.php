<?php
require_once '../config.php';

if (!isLoggedIn() || !canManageEquipment()) {
    redirect('login.php');
}

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get booking details
$sql = "SELECT r.*, u.first_name, u.last_name, u.id_number, u.email, u.department,
        e.name as equipment_name, e.image as equipment_image,
        admin.first_name as admin_first, admin.last_name as admin_last
        FROM reservations r 
        JOIN users u ON r.user_id = u.id 
        JOIN equipment e ON r.equipment_id = e.id 
        LEFT JOIN users admin ON r.approved_by = admin.id
        WHERE r.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('manage_bookings.php');
}

$booking = $result->fetch_assoc();

$success = '';
$error = '';

// Handle actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $admin_notes = isset($_POST['admin_notes']) ? clean($_POST['admin_notes']) : '';
    $admin_id = $_SESSION['user_id'];
    
    if ($action === 'approve') {
        $sql = "UPDATE reservations SET status = 'approved', approved_by = ?, approved_at = NOW(), admin_notes = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $admin_id, $admin_notes, $booking_id);
        
        if ($stmt->execute()) {
            $update_equip = "UPDATE equipment SET available_quantity = available_quantity - 1 WHERE id = ?";
            $stmt = $conn->prepare($update_equip);
            $stmt->bind_param("i", $booking['equipment_id']);
            $stmt->execute();
            
            $message = "Your booking request for " . $booking['equipment_name'] . " has been approved!";
            $notif_sql = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'booking_approved')";
            $stmt = $conn->prepare($notif_sql);
            $stmt->bind_param("is", $booking['user_id'], $message);
            $stmt->execute();
            
            redirect('manage_bookings.php?success=approved');
        }
    } elseif ($action === 'reject') {
        $sql = "UPDATE reservations SET status = 'rejected', approved_by = ?, approved_at = NOW(), admin_notes = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $admin_id, $admin_notes, $booking_id);
        
        if ($stmt->execute()) {
            $message = "Your booking request for " . $booking['equipment_name'] . " has been rejected. Reason: " . $admin_notes;
            $notif_sql = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'booking_rejected')";
            $stmt = $conn->prepare($notif_sql);
            $stmt->bind_param("is", $booking['user_id'], $message);
            $stmt->execute();
            
            redirect('manage_bookings.php?success=rejected');
        }
    } elseif ($action === 'complete') {
        $sql = "UPDATE reservations SET status = 'completed' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $booking_id);
        
        if ($stmt->execute()) {
            $update_equip = "UPDATE equipment SET available_quantity = available_quantity + 1 WHERE id = ?";
            $stmt = $conn->prepare($update_equip);
            $stmt->bind_param("i", $booking['equipment_id']);
            $stmt->execute();
            
            $message = "Your borrowed " . $booking['equipment_name'] . " has been marked as returned. Thank you!";
            $notif_sql = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'booking_completed')";
            $stmt = $conn->prepare($notif_sql);
            $stmt->bind_param("is", $booking['user_id'], $message);
            $stmt->execute();
            
            redirect('manage_bookings.php?success=completed');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - KitKeeper</title>
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
        .equipment-img {
            width: 100%;
            max-width: 300px;
            border-radius: 10px;
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
            <a class="nav-link active" href="manage_bookings.php">
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
            <a href="manage_bookings.php" class="btn btn-outline-primary mb-3">
                <i class="fas fa-arrow-left"></i> Back to Bookings
            </a>
            <h4>Booking Details #<?php echo $booking['id']; ?></h4>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Booking Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Status:</strong><br>
                                <?php
                                $badge_class = [
                                    'pending' => 'bg-warning',
                                    'approved' => 'bg-success',
                                    'rejected' => 'bg-danger',
                                    'completed' => 'bg-info',
                                    'cancelled' => 'bg-secondary'
                                ];
                                ?>
                                <span class="badge <?php echo $badge_class[$booking['status']]; ?> mt-2">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </div>
                            <div class="col-md-6">
                                <strong>Submitted:</strong><br>
                                <?php echo date('F d, Y h:i A', strtotime($booking['created_at'])); ?>
                            </div>
                        </div>

                        <hr>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Equipment:</strong><br>
                                <?php echo htmlspecialchars($booking['equipment_name']); ?>
                            </div>
                            <div class="col-md-6">
                                <?php if (!empty($booking['equipment_image'])): ?>
                                    <img src="<?php echo BASE_URL . EQUIPMENT_UPLOAD_DIR . $booking['equipment_image']; ?>" class="equipment-img" alt="Equipment">
                                <?php endif; ?>
                            </div>
                        </div>

                        <hr>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Borrow Date:</strong><br>
                                <?php echo date('F d, Y', strtotime($booking['start_date'])); ?><br>
                                <small class="text-muted"><?php echo date('h:i A', strtotime($booking['start_time'])); ?></small>
                            </div>
                            <div class="col-md-6">
                                <strong>Return Date:</strong><br>
                                <?php echo date('F d, Y', strtotime($booking['end_date'])); ?><br>
                                <small class="text-muted"><?php echo date('h:i A', strtotime($booking['end_time'])); ?></small>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <strong>Purpose of Use:</strong><br>
                            <p class="mt-2"><?php echo nl2br(htmlspecialchars($booking['purpose'])); ?></p>
                        </div>

                        <?php if (!empty($booking['admin_notes'])): ?>
                            <hr>
                            <div class="alert alert-info">
                                <strong>Admin Notes:</strong><br>
                                <?php echo nl2br(htmlspecialchars($booking['admin_notes'])); ?>
                                <?php if ($booking['admin_first']): ?>
                                    <br><small class="text-muted">By: <?php echo $booking['admin_first'] . ' ' . $booking['admin_last']; ?> on <?php echo date('M d, Y', strtotime($booking['approved_at'])); ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($booking['status'] === 'pending'): ?>
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Take Action</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Admin Notes (Optional)</label>
                                    <textarea name="admin_notes" class="form-control" rows="3" placeholder="Add any notes about your decision..."></textarea>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="action" value="approve" class="btn btn-success">
                                        <i class="fas fa-check"></i> Approve Booking
                                    </button>
                                    <button type="submit" name="action" value="reject" class="btn btn-danger">
                                        <i class="fas fa-times"></i> Reject Booking
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($booking['status'] === 'approved'): ?>
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Equipment Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Equipment is currently borrowed. Click below when the user returns it.
                            </div>
                            <form method="POST">
                                <button type="submit" name="action" value="complete" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-check-circle"></i> Mark as Returned
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">User Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Name:</strong><br><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></p>
                        <p><strong>ID Number:</strong><br><?php echo htmlspecialchars($booking['id_number']); ?></p>
                        <p><strong>Email:</strong><br><?php echo htmlspecialchars($booking['email']); ?></p>
                        <p><strong>Department:</strong><br><?php echo htmlspecialchars($booking['department']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>