CREATE DATABASE IF NOT EXISTS guidancehub;
USE guidancehub;

-- USERS TABLE
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(100) NOT NULL,
    role ENUM('admin', 'facilitator', 'student', 'professor') NOT NULL
);

-- COLLEGE TABLE
CREATE TABLE IF NOT EXISTS college (
    id INT AUTO_INCREMENT PRIMARY KEY,
    college_id VARCHAR(100) UNIQUE NOT NULL,
    college_name VARCHAR(255) NOT NULL,
    college_code VARCHAR(50) NOT NULL
);

-- STUDENT TABLE
CREATE TABLE IF NOT EXISTS student (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    m_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    college_id VARCHAR(100),
    FOREIGN KEY (college_id) REFERENCES college(college_id)
);

-- FACILITATOR TABLE
CREATE TABLE IF NOT EXISTS facilitator (
    id INT AUTO_INCREMENT PRIMARY KEY,
    facilitator_id VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    m_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    college_id VARCHAR(100),
    FOREIGN KEY (college_id) REFERENCES college(college_id)
);

-- PROFESSOR TABLE
CREATE TABLE IF NOT EXISTS professor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    m_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    college_id VARCHAR(100),
    FOREIGN KEY (college_id) REFERENCES college(college_id)
);

-- ADMIN TABLE
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    m_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL
);
-- APPOINTMENTS TABLE
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(100) NOT NULL,
    facilitator_id VARCHAR(100) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    purpose TEXT,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES student(student_id),
    FOREIGN KEY (facilitator_id) REFERENCES facilitator(facilitator_id)
);

-- SCHEDULES TABLE
CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    facilitator_id VARCHAR(100) NOT NULL,
    available_day ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (facilitator_id) REFERENCES facilitator(facilitator_id)
);

-- REPORTS TABLE
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    report_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(100) NOT NULL,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id),
    FOREIGN KEY (created_by) REFERENCES facilitator(facilitator_id)
);

-- CHATBOT LOGS TABLE
CREATE TABLE IF NOT EXISTS chatbot_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role ENUM('student', 'professor') NOT NULL,
    message TEXT NOT NULL,
    response TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
CREATE TABLE IF NOT EXISTS referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referred_by_id INT NOT NULL,  -- User ID from `users` table (professor/facilitator/admin)
    referred_by_role ENUM('professor') NOT NULL,
    student_id VARCHAR(100) NOT NULL,
    reason TEXT NOT NULL,
    referral_date DATE DEFAULT CURRENT_DATE,
    status ENUM('pending', 'acknowledged', 'in_progress', 'completed') DEFAULT 'pending',
    FOREIGN KEY (referred_by_id) REFERENCES users(id),
    FOREIGN KEY (student_id) REFERENCES student(student_id)
);