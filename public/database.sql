
-- Create Database
CREATE DATABASE IF NOT EXISTS emrs_db;

-- Use the Database
USE emrs_db;

-- Create Admins Table
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100),
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- Create the 'users' table for storing user information
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(50) NOT NULL,
    lastname VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Reports Table
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    crime_type VARCHAR(100) NOT NULL,
    severity ENUM('Low', 'Medium', 'High') NOT NULL,
    description TEXT,
    location VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create User Logins Table
CREATE TABLE user_logins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create Admin Logins Table (Optional)
CREATE TABLE admin_logins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);

CREATE TABLE community_threads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- Insert Sample Admin Data
INSERT INTO admins (firstname, lastname, email, password) VALUES
('Admin', 'User', 'admin@example.com', 'admin123');

-- Insert Sample Users Data
INSERT INTO users (firstname, lastname, email, password) VALUES
('John', 'Doe', 'john@example.com', 'password123'),
('Jane', 'Smith', 'jane@example.com', 'password123');

-- Insert Sample Reports Data
INSERT INTO reports (user_id, crime_type, severity, description, location) VALUES
(1, 'Pollution', 'High', 'Industrial waste dumping in the river', 'River City'),
(2, 'Deforestation', 'Medium', 'Illegal tree cutting in forest area', 'Pine Woods');

-- Insert Sample User Logins Data
INSERT INTO user_logins (user_id) VALUES
(1), (2);

-- Insert Sample Admin Logins Data (Optional)
INSERT INTO admin_logins (admin_id) VALUES
(1);
