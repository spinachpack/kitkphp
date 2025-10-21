<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get all user bookings
$sql = "SELECT r.*, e.name as equipment_name, e.image 
        FROM reservations r 
        JOIN equipment e ON r.equipment_id = e.id 
        WHERE r.user_id = ? 
        ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - KitKeeper</title>
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
        .booking-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        .booking-card:hover {
            transform: translateY(-3px);
        }
        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
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
            <a class="nav-link" href="browse_equipment.php">
                <i class="fas fa-box me-2"></i> Browse Equipment
            </a>
            <a class="nav-link active" href="my_bookings.php">
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
            <h4>My Bookings</h4>
            <p class="text-muted">View all your equipment reservation history</p>
        </div>

        <?php if ($bookings->num_rows > 0): ?>
            <?php while ($booking = $bookings->fetch_assoc()): ?>
                <div class="booking-card">
                    <div class="row align-items-center">
                        <div class="col-md-2">
                            <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-box fa-2x text-white"></i>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h5 class="mb-1"><?php echo htmlspecialchars($booking['equipment_name']); ?></h5>
                            <small class="text-muted">Booking ID: #<?php echo $booking['id']; ?></small>
                        </div>
                        <div class="col-md-3">
                            <p class="mb-0"><i class="fas fa-calendar text-primary"></i> <strong>Start:</strong></p>
                            <small><?php echo date('M d, Y h:i A', strtotime($booking['start_date'] . ' ' . $booking['start_time'])); ?></small>
                            <p class="mb-0 mt-2"><i class="fas fa-calendar text-danger"></i> <strong>End:</strong></p>
                            <small><?php echo date('M d, Y h:i A', strtotime($booking['end_date'] . ' ' . $booking['end_time'])); ?></small>
                        </div>
                        <div class="col-md-2">
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
                            <p class="mb-0 mt-2">
                                <small class="text-muted">Requested: <?php echo date('M d, Y', strtotime($booking['created_at'])); ?></small>
                            </p>
                        </div>
                        <div class="col-md-1">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bookingModal<?php echo $booking['id']; ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal for booking details -->
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
                                        <h6>Equipment Information</h6>
                                        <p><strong>Name:</strong> <?php echo htmlspecialchars($booking['equipment_name']); ?></p>
                                        <p><strong>Status:</strong> 
                                            <span class="badge <?php echo $badge_class[$booking['status']]; ?> badge-status">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Booking Period</h6>
                                        <p><strong>Start:</strong> <?php echo date('F d, Y h:i A', strtotime($booking['start_date'] . ' ' . $booking['start_time'])); ?></p>
                                        <p><strong>End:</strong> <?php echo date('F d, Y h:i A', strtotime($booking['end_date'] . ' ' . $booking['end_time'])); ?></p>
                                    </div>
                                </div>
                                <hr>
                                <h6>Purpose of Use</h6>
                                <p><?php echo nl2br(htmlspecialchars($booking['purpose'])); ?></p>
                                
                                <?php if (!empty($booking['admin_notes'])): ?>
                                    <hr>
                                    <h6>Admin Notes</h6>
                                    <div class="alert alert-info">
                                        <?php echo nl2br(htmlspecialchars($booking['admin_notes'])); ?>
                                    </div>
                                <?php endif; ?>

                                <hr>
                                <p><small class="text-muted">Submitted on: <?php echo date('F d, Y h:i A', strtotime($booking['created_at'])); ?></small></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                    <h5>No Bookings Yet</h5>
                    <p class="text-muted">You haven't made any equipment reservations yet.</p>
                    <a href="browse_equipment.php" class="btn btn-primary">
                        <i class="fas fa-search"></i> Browse Equipment
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>