<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Get statistics
$total_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'")->fetch_assoc()['total'];
$total_equipment = $conn->query("SELECT COUNT(*) as total FROM equipment")->fetch_assoc()['total'];
$total_bookings = $conn->query("SELECT COUNT(*) as total FROM reservations")->fetch_assoc()['total'];
$pending_bookings = $conn->query("SELECT COUNT(*) as total FROM reservations WHERE status = 'pending'")->fetch_assoc()['total'];
$approved_bookings = $conn->query("SELECT COUNT(*) as total FROM reservations WHERE status = 'approved'")->fetch_assoc()['total'];
$rejected_bookings = $conn->query("SELECT COUNT(*) as total FROM reservations WHERE status = 'rejected'")->fetch_assoc()['total'];
$completed_bookings = $conn->query("SELECT COUNT(*) as total FROM reservations WHERE status = 'completed'")->fetch_assoc()['total'];

// Most booked equipment
$popular_equipment = $conn->query("SELECT e.name, COUNT(r.id) as bookings 
                                   FROM equipment e 
                                   LEFT JOIN reservations r ON e.id = r.equipment_id 
                                   GROUP BY e.id 
                                   ORDER BY bookings DESC 
                                   LIMIT 10");

// Recent activity
$recent_activity = $conn->query("SELECT r.*, u.first_name, u.last_name, e.name as equipment_name 
                                FROM reservations r 
                                JOIN users u ON r.user_id = u.id 
                                JOIN equipment e ON r.equipment_id = e.id 
                                ORDER BY r.created_at DESC 
                                LIMIT 15");

// Bookings by status
$status_data = $conn->query("SELECT status, COUNT(*) as count FROM reservations GROUP BY status");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - KitKeeper</title>
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
        .stat-box {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .stat-box h3 {
            margin: 0;
            color: var(--primary-blue);
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
            <small class="text-white-50">Administrator</small>
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
            <a class="nav-link" href="manage_users.php">
                <i class="fas fa-users me-2"></i> Manage Users
            </a>
            <a class="nav-link active" href="reports.php">
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
            <h4>System Reports</h4>
            <p class="text-muted">Overview of system usage and statistics</p>
        </div>

        <div class="row">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-box text-center">
                    <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                    <h3><?php echo $pending_bookings; ?></h3>
                    <p class="text-muted mb-0">Pending</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Booking Status Summary</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td><span class="badge bg-warning">Pending</span></td>
                                    <td class="text-end"><strong><?php echo $pending_bookings; ?></strong></td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-success">Approved</span></td>
                                    <td class="text-end"><strong><?php echo $approved_bookings; ?></strong></td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-danger">Rejected</span></td>
                                    <td class="text-end"><strong><?php echo $rejected_bookings; ?></strong></td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-info">Completed</span></td>
                                    <td class="text-end"><strong><?php echo $completed_bookings; ?></strong></td>
                                </tr>
                                <tr class="table-active">
                                    <td><strong>Total</strong></td>
                                    <td class="text-end"><strong><?php echo $total_bookings; ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-12 mb-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Most Booked Equipment</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Equipment</th>
                                        <th class="text-end">Bookings</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($popular_equipment->num_rows > 0): ?>
                                        <?php while ($item = $popular_equipment->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td class="text-end">
                                                    <span class="badge bg-primary"><?php echo $item['bookings']; ?></span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="2" class="text-center text-muted">No data available</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>User</th>
                                        <th>Equipment</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent_activity->num_rows > 0): ?>
                                        <?php while ($activity = $recent_activity->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y h:i A', strtotime($activity['created_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($activity['equipment_name']); ?></td>
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
                                                    <span class="badge <?php echo $badge_class[$activity['status']]; ?>">
                                                        <?php echo ucfirst($activity['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No activity yet</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>