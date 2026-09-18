CREATE DATABASE IF NOT EXISTS examprep_db;
USE examprep_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'user',
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS study_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exam_id INT NOT NULL,
    source_mode VARCHAR(50) NOT NULL,
    file_path VARCHAR(255) DEFAULT NULL,
    content_type VARCHAR(50) NOT NULL,
    difficulty VARCHAR(50) NOT NULL,
    raw_results_json LONGTEXT NOT NULL,

    -- REQUIRED FIELDS FOR MOCK EXAM RESULTS
    user_answers_json LONGTEXT DEFAULT NULL,
    score_percentage INT DEFAULT NULL,
    correct_count INT DEFAULT NULL,
    incorrect_count INT DEFAULT NULL,
    passed TINYINT(1) DEFAULT NULL,
    time_taken_seconds INT DEFAULT NULL,
    completed_at TIMESTAMP NULL DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS analytics_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    exam_id INT DEFAULT NULL,
    action_type VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE mock_exam_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exam_id INT NOT NULL,
    material_id INT NOT NULL,
    score_percentage INT NOT NULL,
    correct_count INT NOT NULL,
    incorrect_count INT NOT NULL,
    passed TINYINT(1) NOT NULL,
    time_taken_seconds INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (exam_id) REFERENCES exams(id),
    FOREIGN KEY (material_id) REFERENCES study_materials(id)
);


CREATE TABLE flashcard_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exam_id INT NOT NULL,
    correct INT DEFAULT 0,
    incorrect INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (exam_id) REFERENCES exams(id)
);

CREATE TABLE mock_exam_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    question_index INT NOT NULL,
    selected_option VARCHAR(255),
    correct_option VARCHAR(255),
    timed_out TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (material_id) REFERENCES study_materials(id)
);




-- Default admin credentials are configured by the application environment.
INSERT INTO users (email, password_hash, role, status)
VALUES ('admin@examprep.com', '$2y$10$wK1c7lVz7YjM3yGzE6b0Uea7s.Pq2E0qFhM8N0a9tq2K1s5pQe2F.', 'admin', 'active')
ON DUPLICATE KEY UPDATE id=id;

-- Seed Exams Library
INSERT INTO exams (code, name, description, category) VALUES
-- IT & Cloud
('AZ-900', 'Microsoft Azure Fundamentals', 'Demonstrates foundational knowledge of cloud services and how they are provided with Microsoft Azure.', 'IT & Cloud'),
('AZ-104', 'Microsoft Azure Administrator', 'Validates expertise in implementing, managing, and monitoring identity, governance, storage, compute, and virtual networks.', 'IT & Cloud'),
('AWS-CLF', 'AWS Certified Cloud Practitioner', 'Covers a basic understanding of AWS Cloud platform, security, and technology concepts.', 'IT & Cloud'),
('AWS-SAA', 'AWS Certified Solutions Architect - Associate', 'Tests knowledge of designing cost-efficient, fault-tolerant, and scalable systems on AWS.', 'IT & Cloud'),
('GCP-CDL', 'Google Cloud Digital Leader', 'Distinguishes individuals who can articulate the capabilities of Google Cloud core products and services.', 'IT & Cloud'),
('GCP-PCA', 'Google Cloud Professional Cloud Architect', 'Assesses ability to design, develop, and manage robust, secure, and dynamic solutions to drive business objectives.', 'IT & Cloud'),
-- Networking & Security
('COMP-A', 'CompTIA A+', 'Industry standard for establishing a career in IT, covering hardware, operating systems, and troubleshooting.', 'Networking & Security'),
('COMP-NET', 'CompTIA Network+', 'Covers troubleshooting, configuring, and managing common wired and wireless networks.', 'Networking & Security'),
('COMP-SEC', 'CompTIA Security+', 'Establishes core cybersecurity knowledge, focusing on hands-on practical security problem-solving.', 'Networking & Security'),
('CCNA-200', 'Cisco Certified Network Associate (CCNA)', 'Tests knowledge of network fundamentals, IP connectivity, security fundamentals, and automation.', 'Networking & Security'),
('CEH', 'Certified Ethical Hacker (CEH)', 'Validates skills in vulnerability detection, penetration testing, and securing system architectures.', 'Networking & Security'),
('CISSP', 'Certified Information Systems Security Professional', 'Premier cybersecurity certification covering information security risk management and operations.', 'Networking & Security'),
-- Project Management & Professional
('PMP', 'Project Management Professional', 'Gold standard of project management validation, covering predictive, agile, and hybrid methodologies.', 'Professional & Business'),
('CAPM', 'Certified Associate in Project Management', 'Entry-level project management certification covering fundamental project terms and concepts.', 'Professional & Business'),
('ITIL-4', 'ITIL 4 Foundation', 'Covers the IT service management lifecycle, terminology, and key concepts of service delivery.', 'Professional & Business'),
('SCRUM-PSM', 'Professional Scrum Master I (PSM I)', 'Tests understanding of the Scrum framework and how to apply it to real-world software teams.', 'Professional & Business'),
('CFA-L1', 'Chartered Financial Analyst (CFA) - Level 1', 'Focuses on ethical and professional standards, investment tools, and portfolio asset classes.', 'Professional & Business'),
('CPA', 'Certified Public Accountant (CPA)', 'Validates accounting expertise, auditing procedures, tax laws, and business regulations.', 'Professional & Business'),
-- Languages
('IELTS-AC', 'IELTS Academic', 'Standardized English proficiency test for higher education or professional registration globally.', 'Languages'),
('IELTS-GT', 'IELTS General Training', 'Measures English language proficiency in a practical, everyday context for migration or work.', 'Languages'),
('TOEFL-IBT', 'TOEFL iBT', 'Evaluates English listening, reading, speaking, and writing capabilities in university settings.', 'Languages'),
('TOEIC', 'TOEIC Listening & Reading', 'Assesses English language skills used in an international workplace environment.', 'Languages'),
('DELE-B2', 'DELE Spanish Diploma B2', 'Certifies sufficient knowledge of Spanish to communicate in everyday situations without difficulty.', 'Languages'),
('JLPT-N5', 'Japanese Language Proficiency Test N5', 'Basic Japanese reading and listening, covering hiragana, katakana, and elementary kanji.', 'Languages'),
('JLPT-N4', 'Japanese Language Proficiency Test N4', 'Ability to understand basic Japanese, including daily conversations and simple readings.', 'Languages'),
('JLPT-N3', 'Japanese Language Proficiency Test N3', 'Bridges basic and advanced Japanese, focusing on understanding daily life scenarios.', 'Languages'),
-- Driving Licences
('UK-DRIVE', 'UK Driving Theory Test (Car)', 'Covers UK road signs, traffic regulations, hazard perception, and safe driving rules.', 'Driving'),
('US-DMV-NY', 'US DMV Permit Practice Test (NY)', 'Tests knowledge of New York traffic laws, road safety signs, and vehicle operation rules.', 'Driving'),
('US-DMV-CA', 'US DMV Permit Practice Test (CA)', 'Covers California-specific driving guidelines, speed limits, and traffic enforcement regulations.', 'Driving'),
('AU-DKT', 'Australia Driver Knowledge Test', 'Tests knowledge of road rules and safety procedures required for a learner permit in Australia.', 'Driving'),
('CA-G1', 'Ontario G1 Driving Theory Test', 'Ontario G1 theory test questions covering traffic control signals, rules, and vehicle laws.', 'Driving'),
-- Technical & Computing Academics
('AP-COMP-A', 'AP Computer Science A', 'High school level exam covering fundamental Java programming, object-oriented concepts, and algorithms.', 'Academics'),
('AP-COMP-P', 'AP Computer Science Principles', 'Introduces students to the foundational concepts of computer science and computational thinking.', 'Academics'),
('CLEP-INFO', 'CLEP Information Systems', 'College-level credit examination covering office suites, networking, databases, and programming.', 'Academics'),
-- Graduate & College Admissions
('SAT', 'SAT Prep Test', 'Standardized test widely used for college admissions in the United States, covering math and English.', 'Academics'),
('ACT', 'ACT Prep Test', 'College readiness assessment covering English, mathematics, reading, and science reasoning.', 'Academics'),
('GRE', 'GRE General Test', 'Graduate level exam testing verbal reasoning, quantitative reasoning, and analytical writing.', 'Academics'),
('GMAT', 'GMAT Focus Edition', 'Graduate management admission test evaluating data insights, quantitative and verbal reasoning.', 'Academics'),
('MCAT', 'Medical College Admission Test', 'Standardized exam for prospective medical students testing biology, physics, and critical thinking.', 'Academics'),
-- Healthcare & Nursing
('NCLEX-RN', 'NCLEX-RN Practice Test', 'Licensing exam for registered nurses, focusing on safe and effective patient care environments.', 'Healthcare'),
('USMLE-1', 'USMLE Step 1', 'First part of the medical licensing examination assessing understanding of basic medical sciences.', 'Healthcare');
