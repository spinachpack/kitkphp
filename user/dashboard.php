<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Get user's reservations
$user_id = $_SESSION['user_id'];
$reservations_sql = "SELECT r.*, e.name as equipment_name, e.image 
                     FROM reservations r 
                     JOIN equipment e ON r.equipment_id = e.id 
                     WHERE r.user_id = ? 
                     ORDER BY r.created_at DESC LIMIT 5";
$stmt = $conn->prepare($reservations_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reservations = $stmt->get_result();

// Get notifications
$notif_sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($notif_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result();

// Count unread notifications
$unread_sql = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($unread_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$unread_count = $stmt->get_result()->fetch_assoc()['unread'];

// Get statistics
$stats_sql = "SELECT 
              COUNT(*) as total_bookings,
              SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
              SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
              SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
              FROM reservations WHERE user_id = ?";
$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - KitKeeper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #1e5a96;
            --light-blue: #4a9dd6;
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
            padding: 0;
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
        .top-navbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
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
            <a class="nav-link active" href="dashboard.php">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>
            <a class="nav-link" href="browse_equipment.php">
                <i class="fas fa-box me-2"></i> Browse Equipment
            </a>
            <a class="nav-link" href="my_bookings.php">
                <i class="fas fa-calendar-check me-2"></i> My Bookings
            </a>
            <a class="nav-link" href="notifications.php">
                <i class="fas fa-bell me-2"></i> Notifications
                <?php if ($unread_count > 0): ?>
                    <span class="badge bg-danger"><?php echo $unread_count; ?></span>
                <?php endif; ?>
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
        <div class="top-navbar d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">Dashboard</h4>
                <small class="text-muted">Welcome back, <?php echo $_SESSION['first_name']; ?>!</small>
            </div>
            <div>
                <span class="text-muted me-3"><i class="fas fa-calendar"></i> <?php echo date('l, F j, Y'); ?></span>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Recent Bookings</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($reservations->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Equipment</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($booking = $reservations->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-box text-primary me-2"></i>
                                                        <?php echo htmlspecialchars($booking['equipment_name']); ?>
                                                    </div>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($booking['start_date'])); ?></td>
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
                                                    <a href="booking_details.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="my_bookings.php" class="btn btn-link">View All Bookings →</a>
                        <?php else: ?>
                            <p class="text-center text-muted py-4">No bookings yet. <a href="browse_equipment.php">Browse equipment</a> to make your first reservation.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Notifications</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($notifications->num_rows > 0): ?>
                            <?php while ($notif = $notifications->fetch_assoc()): ?>
                                <div class="alert alert-light mb-2 <?php echo $notif['is_read'] ? '' : 'border-primary'; ?>">
                                    <small class="d-block text-muted"><?php echo date('M d, h:i A', strtotime($notif['created_at'])); ?></small>
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                </div>
                            <?php endwhile; ?>
                            <a href="notifications.php" class="btn btn-link">View All Notifications →</a>
                        <?php else: ?>
                            <p class="text-center text-muted py-3">No notifications yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-plus fa-3x text-primary mb-3"></i>
                        <h5>Quick Action</h5>
                        <p class="text-muted">Ready to book equipment?</p>
                        <a href="browse_equipment.php" class="btn btn-primary">
                            <i class="fas fa-search"></i> Browse Equipment
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>