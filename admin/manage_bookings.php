<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$success = '';
$error = '';

// Handle approval/rejection
if (isset($_POST['action']) && isset($_POST['booking_id'])) {
    $booking_id = (int)$_POST['booking_id'];
    $action = $_POST['action'];
    $admin_notes = isset($_POST['admin_notes']) ? clean($_POST['admin_notes']) : '';
    $admin_id = $_SESSION['user_id'];
    
    if ($action === 'approve') {
        // Update booking status
        $sql = "UPDATE reservations SET status = 'approved', approved_by = ?, approved_at = NOW(), admin_notes = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $admin_id, $admin_notes, $booking_id);
        
        if ($stmt->execute()) {
            // Get booking and user details
            $booking_sql = "SELECT r.*, u.id as user_id, e.name as equipment_name FROM reservations r 
                           JOIN users u ON r.user_id = u.id 
                           JOIN equipment e ON r.equipment_id = e.id 
                           WHERE r.id = ?";
            $stmt = $conn->prepare($booking_sql);
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $booking = $stmt->get_result()->fetch_assoc();
            
            // Update equipment availability
            $update_equip = "UPDATE equipment SET available_quantity = available_quantity - 1 WHERE id = ?";
            $stmt = $conn->prepare($update_equip);
            $stmt->bind_param("i", $booking['equipment_id']);
            $stmt->execute();
            
            // Send notification to user
            $message = "Your booking request for " . $booking['equipment_name'] . " has been approved!";
            $notif_sql = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'booking_approved')";
            $stmt = $conn->prepare($notif_sql);
            $stmt->bind_param("is", $booking['user_id'], $message);
            $stmt->execute();
            
            $success = "Booking approved successfully!";
        } else {
            $error = "Failed to approve booking.";
        }
    } elseif ($action === 'reject') {
        $sql = "UPDATE reservations SET status = 'rejected', approved_by = ?, approved_at = NOW(), admin_notes = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $admin_id, $admin_notes, $booking_id);
        
        if ($stmt->execute()) {
            // Get booking details
            $booking_sql = "SELECT r.*, u.id as user_id, e.name as equipment_name FROM reservations r 
                           JOIN users u ON r.user_id = u.id 
                           JOIN equipment e ON r.equipment_id = e.id 
                           WHERE r.id = ?";
            $stmt = $conn->prepare($booking_sql);
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $booking = $stmt->get_result()->fetch_assoc();
            
            // Send notification
            $message = "Your booking request for " . $booking['equipment_name'] . " has been rejected. Reason: " . $admin_notes;
            $notif_sql = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'booking_rejected')";
            $stmt = $conn->prepare($notif_sql);
            $stmt->bind_param("is", $booking['user_id'], $message);
            $stmt->execute();
            
            $success = "Booking rejected successfully!";
        } else {
            $error = "Failed to reject booking.";
        }
    } elseif ($action === 'complete') {
        // Mark as returned/completed
        $sql = "UPDATE reservations SET status = 'completed' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $booking_id);
        
        if ($stmt->execute()) {
            // Get booking details
            $booking_sql = "SELECT r.*, u.id as user_id, e.name as equipment_name, e.id as equipment_id FROM reservations r 
                           JOIN users u ON r.user_id = u.id 
                           JOIN equipment e ON r.equipment_id = e.id 
                           WHERE r.id = ?";
            $stmt = $conn->prepare($booking_sql);
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $booking = $stmt->get_result()->fetch_assoc();
            
            // Restore equipment availability
            $update_equip = "UPDATE equipment SET available_quantity = available_quantity + 1 WHERE id = ?";
            $stmt = $conn->prepare($update_equip);
            $stmt->bind_param("i", $booking['equipment_id']);
            $stmt->execute();
            
            // Send notification
            $message = "Your borrowed " . $booking['equipment_name'] . " has been marked as returned. Thank you!";
            $notif_sql = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'booking_completed')";
            $stmt = $conn->prepare($notif_sql);
            $stmt->bind_param("is", $booking['user_id'], $message);
            $stmt->execute();
            
            $success = "Equipment marked as returned successfully!";
        } else {
            $error = "Failed to mark as returned.";
        }
    }
}

// Filter by status
$status_filter = isset($_GET['status']) ? clean($_GET['status']) : '';

// Get bookings
$sql = "SELECT r.*, u.first_name, u.last_name, u.id_number, u.email, e.name as equipment_name, 
        admin.first_name as admin_first, admin.last_name as admin_last
        FROM reservations r 
        JOIN users u ON r.user_id = u.id 
        JOIN equipment e ON r.equipment_id = e.id 
        LEFT JOIN users admin ON r.approved_by = admin.id";

if (!empty($status_filter)) {
    $sql .= " WHERE r.status = '$status_filter'";
}

$sql .= " ORDER BY r.created_at DESC";
$bookings = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - KitKeeper</title>
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
        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
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
            .btn-group {
                flex-wrap: wrap;
            }
            .btn-group .btn {
                font-size: 0.85rem;
                padding: 8px 12px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="profile-section">
            <img src="<?php echo BASE_URL . PROFILE_UPLOAD_DIR . $_SESSION['profile_picture']; ?>" class="profile-img" alt="Profile">
            <h5 class="mt-3 mb-0"><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></h5>
            <small class="text-white-50">Administrator</small>
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
            <a class="nav-link" href="manage_users.php">
                <i class="fas fa-users me-2"></i> Manage Users
            </a>
            <a class="nav-link" href="reports.php">
                <i class="fas fa-chart-bar me-2"></i> Reports
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
            <h4>Manage Bookings</h4>
            <p class="text-muted">Review and manage equipment booking requests</p>
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

        <div class="card mb-4">
            <div class="card-body">
                <div class="btn-group" role="group">
                    <a href="manage_bookings.php" class="btn <?php echo empty($status_filter) ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        All Bookings
                    </a>
                    <a href="manage_bookings.php?status=pending" class="btn <?php echo $status_filter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                        Pending
                    </a>
                    <a href="manage_bookings.php?status=approved" class="btn <?php echo $status_filter === 'approved' ? 'btn-success' : 'btn-outline-success'; ?>">
                        Approved
                    </a>
                    <a href="manage_bookings.php?status=rejected" class="btn <?php echo $status_filter === 'rejected' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                        Rejected
                    </a>
                    <a href="manage_bookings.php?status=completed" class="btn <?php echo $status_filter === 'completed' ? 'btn-info' : 'btn-outline-info'; ?>">
                        Completed
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <?php if ($bookings->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User Details</th>
                                    <th>Equipment</th>
                                    <th>Date & Time</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($booking = $bookings->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $booking['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo $booking['id_number']; ?></small><br>
                                            <small class="text-muted"><?php echo $booking['email']; ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($booking['equipment_name']); ?></td>
                                        <td>
                                            <strong><?php echo date('M d', strtotime($booking['start_date'])); ?> - <?php echo date('M d, Y', strtotime($booking['end_date'])); ?></strong><br>
                                            <small><?php echo date('h:i A', strtotime($booking['start_time'])); ?> - <?php echo date('h:i A', strtotime($booking['end_time'])); ?></small>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars(substr($booking['purpose'], 0, 50)); ?>...</small>
                                        </td>
                                        <td>
                                            <?php
                                            $badge_class = [
                                                'pending' => 'bg-warning',
                                                'approved' => 'bg-success',
                                                'rejected' => 'bg-danger',
                                                'completed' => 'bg-info',
                                                'cancelled' => 'bg-secondary'
                                            ];
                                            ?>
                                            <span class="badge <?php echo $badge_class[$booking['status']]; ?> badge-status">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bookingModal<?php echo $booking['id']; ?>">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Modal for each booking -->
                                    <div class="modal fade" id="bookingModal<?php echo $booking['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Booking Details #<?php echo $booking['id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6>User Information</h6>
                                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></p>
                                                            <p><strong>ID Number:</strong> <?php echo $booking['id_number']; ?></p>
                                                            <p><strong>Email:</strong> <?php echo $booking['email']; ?></p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6>Booking Information</h6>
                                                            <p><strong>Equipment:</strong> <?php echo htmlspecialchars($booking['equipment_name']); ?></p>
                                                            <p><strong>Start:</strong> <?php echo date('M d, Y h:i A', strtotime($booking['start_date'] . ' ' . $booking['start_time'])); ?></p>
                                                            <p><strong>End:</strong> <?php echo date('M d, Y h:i A', strtotime($booking['end_date'] . ' ' . $booking['end_time'])); ?></p>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <h6>Purpose of Use</h6>
                                                    <p><?php echo nl2br(htmlspecialchars($booking['purpose'])); ?></p>
                                                    
                                                    <?php if (!empty($booking['admin_notes'])): ?>
                                                        <hr>
                                                        <h6>Admin Notes</h6>
                                                        <p><?php echo nl2br(htmlspecialchars($booking['admin_notes'])); ?></p>
                                                        <p><small class="text-muted">Reviewed by: <?php echo $booking['admin_first'] . ' ' . $booking['admin_last']; ?> on <?php echo date('M d, Y', strtotime($booking['approved_at'])); ?></small></p>
                                                    <?php endif; ?>

                                                    <?php if ($booking['status'] === 'pending'): ?>
                                                        <hr>
                                                        <form method="POST">
                                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label">Admin Notes</label>
                                                                <textarea name="admin_notes" class="form-control" rows="3" placeholder="Add notes about your decision..."></textarea>
                                                            </div>
                                                            <div class="d-flex gap-2">
                                                                <button type="submit" name="action" value="approve" class="btn btn-success">
                                                                    <i class="fas fa-check"></i> Approve
                                                                </button>
                                                                <button type="submit" name="action" value="reject" class="btn btn-danger">
                                                                    <i class="fas fa-times"></i> Reject
                                                                </button>
                                                            </div>
                                                        </form>
                                                    <?php endif; ?>

                                                    <?php if ($booking['status'] === 'approved'): ?>
                                                        <hr>
                                                        <div class="alert alert-info">
                                                            <i class="fas fa-info-circle"></i> Equipment has been borrowed. Mark as returned when the user returns it.
                                                        </div>
                                                        <form method="POST">
                                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                            <button type="submit" name="action" value="complete" class="btn btn-primary w-100">
                                                                <i class="fas fa-check-circle"></i> Mark as Returned
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                        <h5>No Bookings Found</h5>
                        <p class="text-muted">There are no bookings matching your filter.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>