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

CREATE TABLE IF NOT EXISTS cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100),
    month VARCHAR(50),
    count INT
);

INSERT INTO cases (category, month, count) VALUES 
('Bullying', 'January', 20),
('Financial', 'January', 15),
('Adjustment Issue', 'January', 15),
('Bullying', 'February', 30),
('Financial', 'February', 25),
('Adjustment Issue', 'February', 20),
('Bullying', 'March', 25),
('Financial', 'March', 30),
('Adjustment Issue', 'March', 20),
('Bullying', 'April', 40),
('Financial', 'April', 35),
('Adjustment Issue', 'April', 30),
('Bullying', 'May', 45),
('Financial', 'May', 40),
('Adjustment Issue', 'May', 35),
('Bullying', 'June', 50),
('Financial', 'June', 45),
('Adjustment Issue', 'June', 40);

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
    course VARCHAR(100),
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
-- FACILITATORS' SCHEDULES (availability)
CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    facilitator_id VARCHAR(100) NOT NULL,
    available_day ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (facilitator_id) REFERENCES facilitator(facilitator_id) ON DELETE CASCADE
);

-- APPOINTMENTS
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(100) NOT NULL,
    facilitator_id VARCHAR(100) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    duration_minutes INT DEFAULT 30,
    purpose TEXT,
    status ENUM('pending','approved','rejected','rescheduled','cancelled','completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES student(student_id) ON DELETE CASCADE,
    FOREIGN KEY (facilitator_id) REFERENCES facilitator(facilitator_id) ON DELETE CASCADE,
    UNIQUE (facilitator_id, appointment_date, appointment_time)
);

-- APPOINTMENT LOGS (audit trail of actions: created, rescheduled, etc.)
CREATE TABLE IF NOT EXISTS appointment_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    action ENUM('created','approved','rejected','rescheduled','cancelled','completed') NOT NULL,
    action_by VARCHAR(100) NOT NULL, -- could be student/facilitator/admin
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    remarks TEXT,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
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

CREATE TABLE IF NOT EXISTS student_concerns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    q1 TINYINT(1),
    q2 TINYINT(1),
    q3 TINYINT(1),
    q4 TINYINT(1),
    q5 TINYINT(1),
    q6 TINYINT(1),
    q7 TINYINT(1),
    q8 TINYINT(1),
    q9 TINYINT(1),
    q10 TINYINT(1),
    q11 TINYINT(1),
    q12 TINYINT(1),
    q13 TINYINT(1),
    q14 TINYINT(1),
    q15 TINYINT(1),
    q16 TINYINT(1),
    q17 TINYINT(1),
    q18 TINYINT(1),
    q19 TINYINT(1),
    q20 TINYINT(1),
    q21 TINYINT(1),
    q22 TINYINT(1),
    q23 TINYINT(1),
    q24 TINYINT(1),
    q25 TINYINT(1),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);


-- NOTIFICATIONS (in-app alerts)
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- ALTER TABLE `users`
-- ADD COLUMN `otp_code` VARCHAR(10) DEFAULT NULL AFTER `role`,
-- ADD COLUMN `otp_expiry` DATETIME DEFAULT NULL AFTER `otp_code`,
-- ADD COLUMN `is_verified` TINYINT(1) DEFAULT 0 AFTER `otp_expiry`;

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