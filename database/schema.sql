CREATE DATABASE jru_pulse; --database


--office table
CREATE TABLE offices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);



--services PK ofc_id
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    office_id INT NOT NULL,
    name VARCHAR(300) NOT NULL,
    code VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE CASCADE,
    UNIQUE KEY unique_service_code (office_id, code)
);


INSERT INTO offices (name, code, description) VALUES
('Registrar\'s Office', 'REG', 'Handles student registration and academic records'),
('Student Accounts Office', 'SAO', 'Manages student financial accounts and billing'),
('Cashier', 'CASH', 'Responsible for payment processing and financial transactions'),
('Library', 'LIB', 'Provides library services and resources for students and faculty'),
('Information Technology Office', 'IT', 'Offers technical support and IT services'),
('Medical & Dental Clinic', 'MED', 'Provides health services to students'),
('Guidance & Testing Office', 'GTO', 'Offers student guidance and testing services'),
('Student Development Office', 'SDO', 'Focuses on student affairs and development programs'),
('Athletics Office', 'ATH', 'Manages sports and athletics programs'),
('Customer Advocacy Office', 'CAO', 'Handles customer service and advocacy issues'),
('Engineering & Maintenance Office', 'EMO', 'Responsible for facilities management and maintenance');


-- Registrar's Office (ID = 1)
INSERT INTO services (office_id, name, code) VALUES
(1, 'Document request', 'reg-doc-req');

-- Student Accounts Office (ID = 2)
INSERT INTO services (office_id, name, code) VALUES
(2, 'Onsite inquiry', 'sao-ons-inq'),
(2, 'Online inquiry', 'sao-onl-inq');

-- Cashier (ID = 3)
INSERT INTO services (office_id, name, code) VALUES
(3, 'Onsite Payment', 'cash-ons-pay');

-- Library (ID = 4)
INSERT INTO services (office_id, name, code) VALUES
(4, 'Online Library Services (Email, social media platforms)', 'lib-onl-svc'),
(4, 'Face-to-Face Library Services', 'lib-ff-svc'),
(4, 'Borrowing of printed materials', 'lib-brw-mat'),
(4, 'Online Library Instructions', 'lib-onl-inst'),
(4, 'Participation on Library activities and programs', 'lib-acts-prog');

-- Information Technology Office (ID = 5)
INSERT INTO services (office_id, name, code) VALUES
(5, 'Online Inquiry / Technical assistance', 'ito-onl-asst'),
(5, 'Face-To-Face inquiry assistance', 'ito-ff-inq'),
(5, 'Technical Assistance during events', 'ito-evnt-asst'),
(5, 'Classroom/Office Technical Assistance', 'ito-room-asst');

-- Medical & Dental Clinic (ID = 6)
INSERT INTO services (office_id, name, code) VALUES
(6, 'Medical check-up/consultation', 'med-med-cons'),
(6, 'Dental check-up/consultation', 'med-den-cons'),
(6, 'Request for medical clearances', 'med-clr-req');

-- Guidance & Testing Office (ID = 7)
INSERT INTO services (office_id, name, code) VALUES
(7, 'Request for Good Moral Certificate', 'gto-gmc-req'),
(7, 'Request for Counseling', 'gto-coun-req'),
(7, 'Scholarship Inquiry', 'gto-schol-inq');

-- Student Development Office (ID = 8)
INSERT INTO services (office_id, name, code) VALUES
(8, 'Filing of a complaint', 'sdo-comp-file'),
(8, 'Request for ID Replacement Form', 'sdo-id-repl'),
(8, 'Request for Admission Slip', 'sdo-adm-slip'),
(8, 'Request for Temporary School ID', 'sdo-temp-id');

-- Athletics Office (ID = 9)
INSERT INTO services (office_id, name, code) VALUES
(9, 'Borrowing of sports equipment', 'ath-brw-equip');

-- Customer Advocacy Office (ID = 10)
INSERT INTO services (office_id, name, code) VALUES
(10, 'General Inquiries', 'cao-gen-inq');

-- Engineering and Maintenance Office (ID = 11)
INSERT INTO services (office_id, name, code) VALUES
(11, 'Request for vehicle', 'emo-veh-req'),
(11, 'Facility maintenance', 'emo-fac-maint'),
(11, 'Auditorium reservation', 'emo-aud-resv');


CREATE TABLE surveys (
  id INT(11) NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL DEFAULT NULL,
  office_id INT(11) NOT NULL,
  service_id INT(11) NOT NULL,
  status ENUM('draft', 'active', 'inactive', 'archived') NOT NULL DEFAULT 'draft';
  questions_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX fk_surveys_office_id (office_id),
  INDEX fk_surveys_service_id (service_id)
) ENGINE=InnoDB;

ALTER TABLE surveys
ADD COLUMN is_locked TINYINT(1) NOT NULL DEFAULT 0 AFTER questions_json;

ALTER TABLE surveys
MODIFY COLUMN status ENUM('draft', 'active', 'inactive', 'archived') NOT NULL DEFAULT 'draft';

ALTER TABLE surveys
ADD COLUMN status_before_archived ENUM('draft', 'active', 'inactive') NULL DEFAULT NULL AFTER status;

CREATE TABLE `respondents` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `respondent_type` ENUM('student', 'non-student') NOT NULL,.
  `student_id` INT(11) NULL DEFAULT NULL,
  `identifier_email` VARCHAR(255) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `respondents` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `respondent_type` ENUM('student', 'non-student') NOT NULL,
  `student_id` INT(11) NULL DEFAULT NULL,
  `identifier_email` VARCHAR(255) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;


CREATE TABLE `survey_responses` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `survey_id` INT(11) NOT NULL,
  `respondent_id` INT(11) NOT NULL, -- This links to our NEW respondents table
  `answers_json` JSON NOT NULL,
  `submitted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`survey_id`) REFERENCES `surveys`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`respondent_id`) REFERENCES `respondents`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE `students` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_number` VARCHAR(50) NOT NULL UNIQUE,
  `first_name` VARCHAR(100) NOT NULL, 
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `division` VARCHAR(100) NULL,
  `course_or_strand` VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB;

ALTER TABLE `students`
  DROP COLUMN `full_name`,
  ADD COLUMN `first_name` VARCHAR(100) NOT NULL AFTER `student_number`,
  ADD COLUMN `last_name` VARCHAR(100) NOT NULL AFTER `first_name`;

INSERT INTO `students` (student_number, first_name, last_name, email, division, course_or_strand) 
VALUES 
('23-261655', 'Lyle', 'Earl', 'lyleearl.rementizo@my.jru.edu', 'College', 'BSIT');

CREATE TABLE survey_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL,
    description TEXT,
    questions_json JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'office_head') NOT NULL DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);