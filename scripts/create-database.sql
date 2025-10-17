-- Create database and tables
CREATE DATABASE IF NOT EXISTS zenstudy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE zenstudy;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    gender ENUM('male', 'female') NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- User moods table
CREATE TABLE IF NOT EXISTS user_moods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mood_value INT NOT NULL CHECK (mood_value BETWEEN 1 AND 5),
    mood_name VARCHAR(50) NOT NULL,
    mood_icon VARCHAR(10) NOT NULL,
    notes TEXT,
    mood_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, mood_date)
);

-- Add indexes for performance optimization
CREATE INDEX idx_user_moods_date ON user_moods(user_id, mood_date);
CREATE INDEX idx_user_moods_value ON user_moods(mood_value);
