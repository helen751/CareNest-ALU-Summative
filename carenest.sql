-- DATABASE SCHEMA: carenest_db
-- TARGET DBMS: MySQL

CREATE DATABASE IF NOT EXISTS `carenest_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `carenest_db`;

-- 1. Table structure for table `users` (Parents/Guardians with country detection)
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT AUTO_INCREMENT,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `country` VARCHAR(100) DEFAULT 'Unknown',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table structure for table `children` (Validated age <= 12)
CREATE TABLE IF NOT EXISTS `children` (
  `child_id` INT AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `child_name` VARCHAR(100) NOT NULL,
  `dob` DATE NOT NULL,
  `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
  `previous_diagnoses` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`child_id`),
  CONSTRAINT `fk_child_guardian` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table structure for table `assessments` (Symptom evaluation histories with parent satisfaction rating)
CREATE TABLE IF NOT EXISTS `assessments` (
  `assessment_id` INT AUTO_INCREMENT,
  `child_id` INT NOT NULL,
  `symptoms_json` TEXT NULL, 
  `severity` ENUM('Low', 'Medium', 'High') NOT NULL,
  `recommendation` TEXT NULL,
  `satisfaction_rating` TINYINT DEFAULT NULL, -- Storing parent satisfaction level after each evaluation (e.g., 1 to 5 stars)
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`assessment_id`),
  CONSTRAINT `fk_assessment_child` FOREIGN KEY (`child_id`) 
    REFERENCES `children` (`child_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table structure for table `appointments` (Pediatric consultations)
CREATE TABLE IF NOT EXISTS `appointments` (
  `appointment_id` INT AUTO_INCREMENT,
  `assessment_id` INT NOT NULL,
  `appointment_date` DATE NOT NULL,
  `appointment_time` TIME NOT NULL,
  `status` ENUM('Pending', 'Confirmed', 'Cancelled') DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`appointment_id`),
  CONSTRAINT `fk_appointment_assessment` FOREIGN KEY (`assessment_id`) 
    REFERENCES `assessments` (`assessment_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Database Indexes for optimal performance tuning
CREATE INDEX `idx_children_user` ON `children` (`user_id`);
CREATE INDEX `idx_assessments_child` ON `assessments` (`child_id`);
CREATE INDEX `idx_appointments_assessment` ON `appointments` (`assessment_id`);
CREATE INDEX `idx_appointments_date` ON `appointments` (`appointment_date`);