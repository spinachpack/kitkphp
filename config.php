<?php
// Database Configuration
// define('DB_HOST', 'localhost');
// define('DB_USER', 'root');
// define('DB_PASS', '');
// define('DB_NAME', 'kitkeeper_db');

define('DB_HOST', 'sql12.freesqldatabase.com');
define('DB_USER', 'sql12803943');
define('DB_PASS', 'c7Qml3hX7b');
define('DB_NAME', 'sql12803943');



// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL
define('BASE_URL', 'https://kitkphp.onrender.com/');

// Upload directory
define('UPLOAD_DIR', 'uploads/');
define('PROFILE_UPLOAD_DIR', UPLOAD_DIR . 'profiles/');
define('EQUIPMENT_UPLOAD_DIR', UPLOAD_DIR . 'equipment/');

// Create upload directories if they don't exist
if (!file_exists(PROFILE_UPLOAD_DIR)) {
    mkdir(PROFILE_UPLOAD_DIR, 0777, true);
}
if (!file_exists(EQUIPMENT_UPLOAD_DIR)) {
    mkdir(EQUIPMENT_UPLOAD_DIR, 0777, true);
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Helper function to check if user is department staff
function isDepartment() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'department';
}

// Helper function to check if user can manage equipment (admin or department)
function canManageEquipment() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'department');
}

// Helper function to redirect
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function clean($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

// Helper function to get profile picture URL
function getProfilePicture($filename) {
    if (empty($filename) || $filename === 'default-avatar.png' || !file_exists(PROFILE_UPLOAD_DIR . $filename)) {
        // Return default avatar using UI Avatars API
        $initials = strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1));
        return "https://ui-avatars.com/api/?name=" . urlencode($_SESSION['first_name'] . '+' . $_SESSION['last_name']) . "&size=200&background=1e5a96&color=fff&bold=true";
    }
    return BASE_URL . PROFILE_UPLOAD_DIR . $filename;
}

?>
