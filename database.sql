-- Create Database
CREATE DATABASE IF NOT EXISTS kitkeeper_db;
USE kitkeeper_db;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_number VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role ENUM('user', 'admin', 'department') DEFAULT 'user',
    department VARCHAR(100),
    profile_picture VARCHAR(255) DEFAULT 'default-avatar.png',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Equipment Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Equipment Table
CREATE TABLE IF NOT EXISTS equipment (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    category_id INT,
    description TEXT,
    quantity INT DEFAULT 1,
    available_quantity INT DEFAULT 1,
    image VARCHAR(255),
    status ENUM('available', 'unavailable', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Reservations Table
CREATE TABLE IF NOT EXISTS reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    equipment_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    purpose TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
    admin_notes TEXT,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert Default Admin and Department Staff
INSERT INTO users (id_number, email, password, first_name, last_name, role, department) 
VALUES 
('ADMIN001', 'admin@ucbanilad.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin', 'Administration'),
('DEPT001', 'sports@ucbanilad.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sports', 'Office', 'department', 'Sports Department');
-- Default password for both: password
-- Department role: Can manage equipment, approve bookings, and mark items as returned

-- Insert Sample Categories
INSERT INTO categories (name, description) VALUES
('Laboratory Equipment', 'Science and computer lab equipment'),
('Sports Equipment', 'Athletic and recreational gear'),
('Audio-Visual Equipment', 'Projectors, cameras, sound systems'),
('Musical Instruments', 'Instruments for music classes and events'),
('Office Equipment', 'Printers, scanners, and office tools');

-- Insert Sample Equipment
INSERT INTO equipment (name, category_id, description, quantity, available_quantity, status) VALUES
('Basketball', 2, 'Official size basketball for indoor and outdoor use', 15, 15, 'available'),
('Volleyball', 2, 'Standard volleyball for games and training', 10, 10, 'available'),
('Football/Soccer Ball', 2, 'FIFA standard soccer ball', 8, 8, 'available'),
('Badminton Racket Set', 2, 'Complete badminton racket set with shuttlecocks', 12, 12, 'available'),
('Table Tennis Set', 2, 'Table tennis paddles and balls set', 6, 6, 'available'),
('LCD Projector', 3, 'Full HD projector 3000 lumens with HDMI', 5, 5, 'available'),