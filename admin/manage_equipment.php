<?php
require_once '../config.php';

if (!isLoggedIn() || !canManageEquipment()) {
    redirect('login.php');
}

$success = '';
$error = '';

// Handle Add Equipment
if (isset($_POST['add_equipment'])) {
    $name = clean($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $description = clean($_POST['description']);
    $quantity = (int)$_POST['quantity'];
    
    if (empty($name) || empty($category_id) || empty($quantity)) {
        $error = "Please fill in all required fields";
    } else {
        $image_name = '';
        
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $image_name = 'equip_' . time() . '_' . uniqid() . '.' . $ext;
                $upload_path = '../' . EQUIPMENT_UPLOAD_DIR . $image_name;
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $error = "Failed to upload image";
                }
            } else {
                $error = "Invalid image format. Only JPG, PNG, and GIF allowed";
            }
        }
        
        if (empty($error)) {
            $sql = "INSERT INTO equipment (name, category_id, description, quantity, available_quantity, image, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'available')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sisiis", $name, $category_id, $description, $quantity, $quantity, $image_name);
            
            if ($stmt->execute()) {
                $success = "Equipment added successfully!";
            } else {
                $error = "Failed to add equipment";
            }
        }
    }
}

// Handle Edit Equipment
if (isset($_POST['edit_equipment'])) {
    $equip_id = (int)$_POST['equip_id'];
    $name = clean($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $description = clean($_POST['description']);
    $quantity = (int)$_POST['quantity'];
    $available_quantity = (int)$_POST['available_quantity'];
    $status = clean($_POST['status']);
    
    $image_update = '';
    $new_image = '';
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_image = 'equip_' . time() . '_' . uniqid() . '.' . $ext;
            $upload_path = '../' . EQUIPMENT_UPLOAD_DIR . $new_image;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Delete old image
                $old_sql = "SELECT image FROM equipment WHERE id = ?";
                $stmt = $conn->prepare($old_sql);
                $stmt->bind_param("i", $equip_id);
                $stmt->execute();
                $old_image = $stmt->get_result()->fetch_assoc()['image'];
                
                if (!empty($old_image) && file_exists('../' . EQUIPMENT_UPLOAD_DIR . $old_image)) {
                    unlink('../' . EQUIPMENT_UPLOAD_DIR . $old_image);
                }
                
                $image_update = ", image = '$new_image'";
            }
        }
    }
    
    $sql = "UPDATE equipment SET name = ?, category_id = ?, description = ?, quantity = ?, 
            available_quantity = ?, status = ? $image_update WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sissiisi", $name, $category_id, $description, $quantity, $available_quantity, $status, $equip_id);
    
    if ($stmt->execute()) {
        $success = "Equipment updated successfully!";
    } else {
        $error = "Failed to update equipment";
    }
}

// Handle Delete Equipment
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $equip_id = (int)$_GET['id'];
    
    // Get image filename
    $sql = "SELECT image FROM equipment WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $equip_id);
    $stmt->execute();
    $image = $stmt->get_result()->fetch_assoc()['image'];
    
    // Delete equipment
    $sql = "DELETE FROM equipment WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $equip_id);
    
    if ($stmt->execute()) {
        // Delete image file
        if (!empty($image) && file_exists('../' . EQUIPMENT_UPLOAD_DIR . $image)) {
            unlink('../' . EQUIPMENT_UPLOAD_DIR . $image);
        }
        $success = "Equipment deleted successfully!";
    } else {
        $error = "Failed to delete equipment";
    }
}

// Get all equipment
$equipment_sql = "SELECT e.*, c.name as category_name FROM equipment e 
                  LEFT JOIN categories c ON e.category_id = c.id 
                  ORDER BY e.name";
$equipment = $conn->query($equipment_sql);

// Get categories
$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories = $conn->query($categories_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Equipment - KitKeeper</title>
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
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
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
            .sidebar .nav-link {
                padding: 10px 15px;
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
            <a class="nav-link active" href="manage_equipment.php">
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
            <a class="nav-link" href="profile.php">
                <i class="fas fa-user me-2"></i> My Profile
            </a>
            <a class="nav-link" href="../logout.php">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4>Manage Equipment</h4>
                <p class="text-muted mb-0">Add, edit, or remove equipment from inventory</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus"></i> Add Equipment
            </button>
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

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Available</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($equipment->num_rows > 0): ?>
                                <?php while ($equip = $equipment->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($equip['image'])): ?>
                                                <img src="<?php echo BASE_URL . EQUIPMENT_UPLOAD_DIR . $equip['image']; ?>" class="equipment-img" alt="">
                                            <?php else: ?>
                                                <div class="equipment-img bg-secondary d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-box text-white"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($equip['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($equip['category_name']); ?></td>
                                        <td><?php echo $equip['quantity']; ?></td>
                                        <td><span class="badge bg-success"><?php echo $equip['available_quantity']; ?></span></td>
                                        <td>
                                            <span class="badge <?php echo $equip['status'] == 'available' ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo ucfirst($equip['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editEquipment(<?php echo htmlspecialchars(json_encode($equip)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="manage_equipment.php?delete=1&id=<?php echo $equip['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this equipment?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No equipment found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Equipment Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Equipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Equipment Name *</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category *</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <?php 
                                    $categories->data_seek(0);
                                    while ($cat = $categories->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity *</label>
                            <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Equipment Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">JPG, PNG, or GIF (Max 5MB)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_equipment" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Equipment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Equipment Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Equipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="editForm">
                    <input type="hidden" name="equip_id" id="edit_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Equipment Name *</label>
                                <input type="text" name="name" id="edit_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category *</label>
                                <select name="category_id" id="edit_category" class="form-select" required>
                                    <?php 
                                    $categories->data_seek(0);
                                    while ($cat = $categories->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Total Quantity *</label>
                                <input type="number" name="quantity" id="edit_quantity" class="form-control" min="1" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Available *</label>
                                <input type="number" name="available_quantity" id="edit_available" class="form-control" min="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Status *</label>
                                <select name="status" id="edit_status" class="form-select" required>
                                    <option value="available">Available</option>
                                    <option value="unavailable">Unavailable</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Change Image (Optional)</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">Leave empty to keep current image</small>
                        </div>
                        <div id="current_image_preview"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_equipment" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editEquipment(equip) {
            document.getElementById('edit_id').value = equip.id;
            document.getElementById('edit_name').value = equip.name;
            document.getElementById('edit_category').value = equip.category_id;
            document.getElementById('edit_description').value = equip.description || '';
            document.getElementById('edit_quantity').value = equip.quantity;
            document.getElementById('edit_available').value = equip.available_quantity;
            document.getElementById('edit_status').value = equip.status;
            
            if (equip.image) {
                document.getElementById('current_image_preview').innerHTML = 
                    '<img src="<?php echo BASE_URL . EQUIPMENT_UPLOAD_DIR; ?>' + equip.image + '" style="max-width: 150px;" class="img-thumbnail">';
            } else {
                document.getElementById('current_image_preview').innerHTML = '';
            }
            
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
    </script>
</body>
</html>