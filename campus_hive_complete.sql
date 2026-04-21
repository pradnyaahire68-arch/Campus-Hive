-- CampusHive Complete Database Schema + Stripe Payments
-- Import via phpMyAdmin. Adds payments table & migration.

CREATE DATABASE IF NOT EXISTS myproject;
USE myproject;

-- Students table
CREATE TABLE students (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    college VARCHAR(100),
    course VARCHAR(50),
    semester VARCHAR(20),
    address TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Colleges table
CREATE TABLE colleges (
    college_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    address TEXT,
    phone VARCHAR(15),
    website VARCHAR(100),
    status ENUM('pending', 'approved', 'blocked') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Events table
CREATE TABLE events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    college_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    location VARCHAR(200),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    total_seats INT NOT NULL,
    available_seats INT NOT NULL,
    image VARCHAR(255),
    registration_fee DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('upcoming', 'ongoing', 'expired', 'draft') DEFAULT 'upcoming',
    is_draft TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (college_id) REFERENCES colleges(college_id) ON DELETE CASCADE
);

-- Registrations table + NEW payment_id
CREATE TABLE registrations (
    registration_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    event_id INT NOT NULL,
    payment_id VARCHAR(100),  -- Links to Stripe payment_intent
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    attendance_marked BOOLEAN DEFAULT FALSE,
    feedback TEXT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    UNIQUE KEY unique_registration (student_id, event_id),
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
);

-- NEW: Payments table for Stripe proofs
CREATE TABLE payments (
    payment_id VARCHAR(100) PRIMARY KEY,  -- Stripe payment_intent_id
    registration_id INT,
    student_id INT NOT NULL,
    event_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'inr',
    status ENUM('pending', 'succeeded', 'failed') DEFAULT 'pending',
    stripe_charge_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (registration_id) REFERENCES registrations(registration_id) ON DELETE SET NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (event_id) REFERENCES events(event_id)
);

-- Admin table
CREATE TABLE admin (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX idx_events_date ON events(event_date);
CREATE INDEX idx_events_status ON events(status);
CREATE INDEX idx_registrations_event ON registrations(event_id);
CREATE INDEX idx_payments_student ON payments(student_id);
CREATE INDEX idx_payments_status ON payments(status);

-- Default admin
INSERT IGNORE INTO admin (username, password, email) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@campusconnect.com');

-- Sample colleges
INSERT IGNORE INTO colleges (name, email, password, address, phone, website, status) VALUES
('ABC University', 'info@abcuni.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '123 University Street', '+1234567890', 'www.abcuni.edu', 'approved'),
('XYZ College', 'contact@xyzcollege.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '456 College Avenue', '+1234567891', 'www.xyzcollege.edu', 'approved');

-- Sample students
INSERT IGNORE INTO students (name, email, password, phone, college, course, semester) VALUES
('John Doe', 'john.doe@student.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1234567892', 'ABC University', 'Computer Science', '6th'),
('Jane Smith', 'jane.smith@student.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1234567893', 'XYZ College', 'Information Technology', '4th');

-- Sample events (ONE with FEE=500 for testing)
INSERT IGNORE INTO events (college_id, title, description, category, event_date, event_time, location, latitude, longitude, total_seats, available_seats, registration_fee, status) VALUES
(1, 'AI & ML Workshop', 'Hands-on AI/ML workshop.', 'Workshop', '2024-12-15', '10:00:00', 'Auditorium A', 40.7128, -74.0060, 100, 95, 500.00, 'upcoming'),
(1, 'Career Seminar (FREE)', 'Free career guidance.', 'Seminar', '2024-12-20', '14:00:00', 'Hall B', 40.7128, -74.0060, 150, 120, 0.00, 'upcoming'),
(2, 'Coding Competition', 'Coding challenges.', 'Competition', '2024-12-25', '09:00:00', 'Lab C', 40.7589, -73.9851, 50, 35, 0.00, 'upcoming');

SELECT '✅ CampusHive + Stripe Payments Schema Ready! Import via phpMyAdmin.' as status;

\nALTER TABLE events ADD COLUMN is_draft TINYINT(1) DEFAULT 0;\nCREATE INDEX idx_events_draft ON events(is_draft);
