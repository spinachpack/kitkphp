<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Get categories
$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories = $conn->query($categories_sql);

// Filter parameters
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? clean($_GET['category']) : '';

// Build query
$sql = "SELECT e.*, c.name as category_name 
        FROM equipment e 
        LEFT JOIN categories c ON e.category_id = c.id 
        WHERE e.status = 'available'";

if (!empty($search)) {
    $sql .= " AND (e.name LIKE '%$search%' OR e.description LIKE '%$search%')";
}

if (!empty($category_filter)) {
    $sql .= " AND e.category_id = '$category_filter'";
}

$sql .= " ORDER BY e.name";
$equipment_result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Equipment - KitKeeper</title>
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
        .equipment-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }
        .equipment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .equipment-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .equipment-placeholder {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .filter-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
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
            <h4>Browse Equipment</h4>
            <p class="text-muted">Find and reserve equipment for your needs</p>
        </div>

        <div class="filter-card">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Search equipment..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php 
                        $categories->data_seek(0);
                        while ($cat = $categories->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ($category_filter == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>

        <div class="row">
            <?php if ($equipment_result->num_rows > 0): ?>
                <?php while ($equip = $equipment_result->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="equipment-card">
                            <?php if (!empty($equip['image'])): ?>
                                <img src="<?php echo BASE_URL . EQUIPMENT_UPLOAD_DIR . $equip['image']; ?>" class="equipment-image" alt="<?php echo htmlspecialchars($equip['name']); ?>">
                            <?php else: ?>
                                <div class="equipment-placeholder">
                                    <i class="fas fa-box fa-4x text-white"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="p-3">
                                <span class="badge bg-primary mb-2"><?php echo htmlspecialchars($equip['category_name']); ?></span>
                                <h5 class="mb-2"><?php echo htmlspecialchars($equip['name']); ?></h5>
                                <p class="text-muted small mb-3">
                                    <?php echo htmlspecialchars(substr($equip['description'], 0, 100)); ?>...
                                </p>
                                
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-success">
                                        <i class="fas fa-check-circle"></i> 
                                        <?php echo $equip['available_quantity']; ?> Available
                                    </span>
                                    <span class="text-muted small">
                                        Total: <?php echo $equip['quantity']; ?>
                                    </span>
                                </div>
                                
                                <a href="book_equipment.php?id=<?php echo $equip['id']; ?>" class="btn btn-primary w-100">
                                    <i class="fas fa-calendar-plus"></i> Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                        <h5>No Equipment Found</h5>
                        <p class="text-muted">Try adjusting your search or filters</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>