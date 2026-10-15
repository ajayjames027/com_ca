-- SQL Schema for HACKFEST '26
-- Compatible with MySQL 5.7+ / MySQL 8.0+ / MariaDB and SQLite

CREATE TABLE IF NOT EXISTS teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pass_id VARCHAR(50),
    team_name VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    college VARCHAR(255) NOT NULL,
    track VARCHAR(150) DEFAULT 'General Application Development',
    leader_name VARCHAR(150) NOT NULL,
    leader_email VARCHAR(150) NOT NULL,
    leader_phone VARCHAR(50),
    leader_dept VARCHAR(100),
    member2_name VARCHAR(150) NOT NULL,
    member2_email VARCHAR(150) NOT NULL,
    member2_phone VARCHAR(50),
    member2_dept VARCHAR(100),
    payment_pic VARCHAR(255) NOT NULL,
    payment_ref VARCHAR(100),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin user reference (if storing credentials in DB)
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'superadmin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
