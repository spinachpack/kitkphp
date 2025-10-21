<?php
require_once '../config.php';

if (!isLoggedIn() || !isDepartment()) {
    redirect('login.php');
}

// Get statistics
$total_equipment_sql = "SELECT COUNT(*) as total FROM equipment";
$total_equipment = $conn->query($total_equipment_sql)->fetch_assoc()['total'];

$pending_bookings_sql = "SELECT COUNT(*) as total FROM reservations WHERE status = 'pending'";
$pending_bookings = $conn->query($pending_bookings_sql)->fetch_assoc()['total'];

$approved_bookings_sql = "SELECT COUNT(*) as total FROM reservations WHERE status = 'approved'";
$approved_bookings = $conn->query($approved_bookings_sql)->fetch_assoc()['total'];

// Get recent bookings
$recent_bookings_sql = "SELECT r.*, u.first_name, u.last_name, u.id_number, e.name as equipment_name 
                       FROM reservations r 
                       JOIN users u ON r.user_id = u.id 
                       JOIN equipment e ON r.equipment_id = e.id 
                       ORDER BY r.created_at DESC LIMIT 10";
$recent_bookings = $conn->query($recent_bookings_sql);
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
            --dark-blue: #0d3b66;
        }
        body {
            background: #f5f7fa;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, var(--primary-blue), var(--dark-blue));
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
            <small class="text-white-50">Department Staff</small>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link active" href="dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
            <a class="nav-link" href="manage_equipment.php">
                <i class="fas fa-box me-2"></i> Manage Equipment
            </a>
            <a class="nav-link" href="manage_bookings.php">
                <i class="fas fa-calendar-check me-2"></i> Manage Bookings
                <?php if ($pending_bookings > 0): ?>
                    <span class="badge bg-warning"><?php echo $pending_bookings; ?></span>
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
                <h4 class="mb-0">Department Dashboard</h4>
                <small class="text-muted">Manage equipment and bookings</small>
            </div>
            <div>
                <span class="text-muted me-3"><i class="fas fa-calendar"></i> <?php echo date('l, F j, Y'); ?></span>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0"><?php echo $total_equipment; ?></h3>
                            <small class="text-muted">Total Equipment</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0"><?php echo $pending_bookings; ?></h3>
                            <small class="text-muted">Pending Requests</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-info bg-opacity-10 text-info">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0"><?php echo $approved_bookings; ?></h3>
                            <small class="text-muted">Approved Bookings</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Booking Requests</h5>
                        <a href="../admin/manage_bookings.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_bookings->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>User</th>
                                            <th>Equipment</th>
                                            <th>Date Range</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                                            <tr>
                                                <td>#<?php echo $booking['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo $booking['id_number']; ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($booking['equipment_name']); ?></td>
                                                <td>
                                                    <?php echo date('M d', strtotime($booking['start_date'])); ?> - 
                                                    <?php echo date('M d, Y', strtotime($booking['end_date'])); ?>
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
                                                    <a href="../admin/manage_bookings.php" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted py-4">No booking requests yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="../admin/manage_equipment.php" class="btn btn-outline-primary">
                                <i class="fas fa-plus-circle"></i> Add New Equipment
                            </a>
                            <a href="../admin/manage_bookings.php?status=pending" class="btn btn-outline-warning">
                                <i class="fas fa-tasks"></i> Review Pending Requests
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">System Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 d-flex justify-content-between">
                            <span>Equipment Utilization</span>
                            <strong>
                                <?php 
                                $util_sql = "SELECT SUM(quantity - available_quantity) as used, SUM(quantity) as total FROM equipment";
                                $util = $conn->query($util_sql)->fetch_assoc();
                                $percentage = $util['total'] > 0 ? round(($util['used'] / $util['total']) * 100) : 0;
                                echo $percentage . '%';
                                ?>
                            </strong>
                        </div>
                        <div class="progress mb-4">
                            <div class="progress-bar bg-primary" style="width: <?php echo $percentage; ?>%"></div>
                        </div>

                        <div class="mb-3 d-flex justify-content-between">
                            <span>Active Bookings</span>
                            <strong><?php echo $approved_bookings; ?></strong>
                        </div>
                        <div class="mb-3 d-flex justify-content-between">
                            <span>Awaiting Approval</span>
                            <strong class="text-warning"><?php echo $pending_bookings; ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>