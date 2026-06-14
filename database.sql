CREATE DATABASE IF NOT EXISTS ghar_sathi;
USE ghar_sathi;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('Employer','Worker','Admin') NOT NULL
);

CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS tags (
    tag_id INT AUTO_INCREMENT PRIMARY KEY,
    tag_name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS jobs (
    job_id INT AUTO_INCREMENT PRIMARY KEY,
    employer_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    job_type VARCHAR(50),
    salary DECIMAL(10,2),
    location VARCHAR(100),
    UNIQUE KEY unique_job_seed (employer_id, title, location),
    FOREIGN KEY (employer_id) REFERENCES users(user_id),
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

CREATE TABLE IF NOT EXISTS job_tags (
    job_id INT,
    tag_id INT,
    PRIMARY KEY (job_id, tag_id),
    FOREIGN KEY (job_id) REFERENCES jobs(job_id),
    FOREIGN KEY (tag_id) REFERENCES tags(tag_id)
);

CREATE TABLE IF NOT EXISTS worker_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT,
    skills TEXT,
    experience_years INT,
    FOREIGN KEY (worker_id) REFERENCES users(user_id)
);

CREATE TABLE IF NOT EXISTS worker_categories (
    worker_id INT,
    category_id INT,
    PRIMARY KEY (worker_id, category_id),
    FOREIGN KEY (worker_id) REFERENCES users(user_id),
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

CREATE TABLE IF NOT EXISTS job_applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT,
    worker_id INT,
    cover_letter TEXT,
    status VARCHAR(50),
    FOREIGN KEY (job_id) REFERENCES jobs(job_id),
    FOREIGN KEY (worker_id) REFERENCES users(user_id)
);

CREATE TABLE IF NOT EXISTS saved_jobs (
    user_id INT,
    job_id INT,
    PRIMARY KEY (user_id, job_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (job_id) REFERENCES jobs(job_id)
);

CREATE TABLE IF NOT EXISTS reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    reviewer_id INT,
    reviewee_id INT,
    rating INT,
    comment TEXT,
    FOREIGN KEY (reviewer_id) REFERENCES users(user_id),
    FOREIGN KEY (reviewee_id) REFERENCES users(user_id)
);

CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
    subscription_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS contact_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    message TEXT NOT NULL
);

INSERT IGNORE INTO categories (category_name) VALUES
('House Work'),
('Culinary Service'),
('Personal'),
('Education'),
('Repair'),
('Pet Care');

INSERT IGNORE INTO tags (tag_name) VALUES
('House Work'),
('Culinary Service'),
('Self Care'),
('Education'),
('Repair'),
('Personal');

INSERT IGNORE INTO users (user_id, full_name, username, email, phone, password, role) VALUES
(1, 'Ghar Sathi Admin', 'admin', 'admin@gharsathi.local', '9800000000', 'admin123', 'Admin');

INSERT IGNORE INTO jobs (employer_id, category_id, title, description, job_type, salary, location)
SELECT 1, category_id, 'House Cleaner', 'Daily household task assistance', 'Full/Part Time', 20000, 'Kathmandu'
FROM categories WHERE category_name = 'House Work';

INSERT IGNORE INTO jobs (employer_id, category_id, title, description, job_type, salary, location)
SELECT 1, category_id, 'Catering Service', 'Professional cooking assistance', 'Seasonal', 1250, 'Bouddha-06, Kathmandu'
FROM categories WHERE category_name = 'Culinary Service';

INSERT IGNORE INTO jobs (employer_id, category_id, title, description, job_type, salary, location)
SELECT 1, category_id, 'Home Tuition', 'Personalized learning at home', 'Part time', 10000, 'Kathmandu'
FROM categories WHERE category_name = 'Education';

INSERT IGNORE INTO jobs (employer_id, category_id, title, description, job_type, salary, location)
SELECT 1, category_id, 'Tech Repair', 'Fast solutions for devices', 'Freelance', 15000, 'Kathmandu'
FROM categories WHERE category_name = 'Repair';

INSERT IGNORE INTO jobs (employer_id, category_id, title, description, job_type, salary, location)
SELECT 1, category_id, 'Pet Care', 'Loving care for pets', 'Freelance', 30000, 'Kathmandu'
FROM categories WHERE category_name = 'Pet Care';
