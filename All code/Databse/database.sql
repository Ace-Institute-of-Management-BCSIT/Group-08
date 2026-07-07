CREATE DATABASE IF NOT EXISTS ghar_sathi;
USE ghar_sathi;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('Employer','Worker','Admin') NOT NULL DEFAULT 'Employer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS jobs (
    job_id INT AUTO_INCREMENT PRIMARY KEY,
    employer_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    job_type VARCHAR(50),
    salary DECIMAL(10,2) DEFAULT 0,
    location VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS worker_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    skills TEXT,
    experience_years INT DEFAULT 0,
    profile_image VARCHAR(255),
    verification_status VARCHAR(30) NOT NULL DEFAULT 'Pending',
    current_status VARCHAR(60) NOT NULL DEFAULT 'Available',
    FOREIGN KEY (worker_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS worker_categories (
    worker_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (worker_id, category_id),
    FOREIGN KEY (worker_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS job_applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    worker_id INT NOT NULL,
    cover_letter TEXT,
    status VARCHAR(50) NOT NULL DEFAULT 'Pending',
    resume_text TEXT,
    resume_file VARCHAR(255),
    police_report_file VARCHAR(255),
    citizenship_file VARCHAR(255),
    admin_status VARCHAR(30) NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE,
    FOREIGN KEY (worker_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS resume_uploads (
    resume_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    worker_id INT NOT NULL,
    resume_id INT NULL,
    cover_letter TEXT,
    status VARCHAR(50) NOT NULL DEFAULT 'Pending',
    admin_status VARCHAR(30) NOT NULL DEFAULT 'Pending',
    resume_path VARCHAR(255),
    police_report_path VARCHAR(255),
    citizenship_card_path VARCHAR(255),
    upload_date TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE,
    FOREIGN KEY (worker_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (resume_id) REFERENCES resume_uploads(resume_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS application_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    job_id INT NOT NULL,
    application_id INT NULL,
    resume_path VARCHAR(255),
    police_report_path VARCHAR(255),
    citizenship_card_path VARCHAR(255) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_job_documents (user_id, job_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE,
    FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS police_report_uploads (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS citizenship_uploads (
    citizenship_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_citizenship (user_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS hire_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    employer_id INT NOT NULL,
    worker_id INT NOT NULL,
    requested_date DATE NOT NULL,
    requested_time TIME NOT NULL DEFAULT '00:00:00',
    worker_salary DECIMAL(10,2) NOT NULL DEFAULT 0,
    offered_salary DECIMAL(10,2) NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'Pending',
    employer_message TEXT,
    worker_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE,
    FOREIGN KEY (employer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (worker_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS booking_requests (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NULL,
    job_id INT NOT NULL,
    employer_id INT NOT NULL,
    worker_id INT NOT NULL,
    service_id INT NOT NULL,
    category_id INT NOT NULL,
    booking_date DATE NOT NULL,
    requested_date DATE NOT NULL,
    service_category VARCHAR(100),
    notes TEXT,
    status ENUM('Pending','Accepted','Rejected','Completed') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE,
    FOREIGN KEY (employer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (worker_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS booked_dates (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    booking_date DATE NOT NULL,
    request_id INT NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Reserved',
    UNIQUE KEY unique_worker_booking_date (worker_id, booking_date),
    FOREIGN KEY (request_id) REFERENCES booking_requests(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (worker_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS employment_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    employer_id INT NULL,
    service_id INT NULL,
    status VARCHAR(80) NOT NULL DEFAULT 'Available',
    start_date DATE NULL,
    completion_date DATE NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (worker_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (employer_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (service_id) REFERENCES jobs(job_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS contact_exchanges (
    exchange_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    job_id INT NOT NULL,
    employer_id INT NOT NULL,
    worker_id INT NOT NULL,
    exchanged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_booking_exchange (booking_id),
    FOREIGN KEY (booking_id) REFERENCES booking_requests(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE,
    FOREIGN KEY (employer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (worker_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS subscribers (
    subscriber_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
    subscription_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS contact_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    first_name VARCHAR(150) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(150) DEFAULT 'General Inquiry',
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    reviewer_id INT NULL,
    reviewee_id INT NULL,
    worker_id INT NULL,
    employer_id INT NULL,
    booking_id INT NULL,
    rating INT,
    comment TEXT,
    review_comment TEXT,
    review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewer_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (reviewee_id) REFERENCES users(user_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS complaints (
    complaint_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    booking_id INT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

INSERT IGNORE INTO categories (category_name) VALUES
('House Work'),
('Culinary Aid'),
('Culinary Service'),
('Home Tuition'),
('Education'),
('Pet Care'),
('Self Care'),
('Elderly Care'),
('Babysitting'),
('Gardening'),
('Plumbing'),
('Electrical Work'),
('Personal'),
('Repair'),
('Other Services');

INSERT IGNORE INTO users (user_id, full_name, username, email, phone, password, role) VALUES
(1, 'Ghar Sathi Admin', 'admin', 'admin@gharsathi.local', '9800000000', 'admin123', 'Admin');

INSERT IGNORE INTO jobs (employer_id, category_id, title, description, job_type, salary, location)
SELECT 1, category_id, 'House Cleaner', 'Daily household cleaning, laundry, and basic home help.', 'Full/Part Time', 20000, 'Kathmandu'
FROM categories WHERE category_name = 'House Work';

INSERT IGNORE INTO jobs (employer_id, category_id, title, description, job_type, salary, location)
SELECT 1, category_id, 'Culinary Aid', 'Cooking, meal preparation, and kitchen support for homes and events.', 'Seasonal', 1250, 'Bouddha-06, Kathmandu'
FROM categories WHERE category_name = 'Culinary Aid';

INSERT IGNORE INTO jobs (employer_id, category_id, title, description, job_type, salary, location)
SELECT 1, category_id, 'Home Tuition', 'Personalized learning support at home.', 'Part time', 10000, 'Kathmandu'
FROM categories WHERE category_name = 'Home Tuition';

INSERT IGNORE INTO jobs (employer_id, category_id, title, description, job_type, salary, location)
SELECT 1, category_id, 'Pet Care', 'Pet sitting, dog walking, feeding, and grooming assistance.', 'Freelance', 30000, 'Kathmandu'
FROM categories WHERE category_name = 'Pet Care';

INSERT IGNORE INTO jobs (employer_id, category_id, title, description, job_type, salary, location)
SELECT 1, category_id, 'Self Care Assistant', 'Wellness, beauty, and personal care support at home.', 'Part time', 20000, 'Kathmandu'
FROM categories WHERE category_name = 'Self Care';

INSERT IGNORE INTO jobs (employer_id, category_id, title, description, job_type, salary, location)
SELECT 1, category_id, 'Tech Repair', 'Phone, laptop, and household appliance repair support.', 'Freelance', 15000, 'Kathmandu'
FROM categories WHERE category_name = 'Other Services';

INSERT IGNORE INTO users (user_id, full_name, username, email, phone, password, role) VALUES
(50, 'Sita Tamang', 'sita_tamang', 'sita.tamang@gharsathi.local', '9800000050', 'worker123', 'Worker'),
(51, 'Maya Shrestha', 'maya_shrestha', 'maya.shrestha@gharsathi.local', '9800000051', 'worker123', 'Worker'),
(52, 'Ramesh Karki', 'ramesh_karki', 'ramesh.karki@gharsathi.local', '9800000052', 'worker123', 'Worker'),
(53, 'Puja Rai', 'puja_rai', 'puja.rai@gharsathi.local', '9800000053', 'worker123', 'Worker'),
(54, 'Anita Gurung', 'anita_gurung', 'anita.gurung@gharsathi.local', '9800000054', 'worker123', 'Worker'),
(55, 'Hari Prasad Adhikari', 'hari_prasad_adhikari', 'hari.adhikari@gharsathi.local', '9800000055', 'worker123', 'Worker'),
(56, 'Sunita Lama', 'sunita_lama', 'sunita.lama@gharsathi.local', '9800000056', 'worker123', 'Worker'),
(57, 'Kiran Thapa', 'kiran_thapa', 'kiran.thapa@gharsathi.local', '9800000057', 'worker123', 'Worker'),
(58, 'Bikash Maharjan', 'bikash_maharjan', 'bikash.maharjan@gharsathi.local', '9800000058', 'worker123', 'Worker'),
(59, 'Nabin K.C.', 'nabin_kc', 'nabin.kc@gharsathi.local', '9800000059', 'worker123', 'Worker');

INSERT IGNORE INTO worker_profiles (worker_id, skills, experience_years, profile_image, verification_status, current_status) VALUES
(50, 'Cleaning, laundry, kitchen organization, and deep cleaning.', 5, 'images/profile.jpg', 'Approved', 'Available weekdays - Kathmandu'),
(51, 'Nepali meals, tiffin planning, event kitchen support.', 7, 'images/profile.jpg', 'Approved', 'Available mornings and events - Patan'),
(52, 'Math, science, English, homework routines, SEE preparation.', 4, 'images/profile.jpg', 'Approved', 'Available evenings - Baneshwor'),
(53, 'Dog walking, feeding, pet sitting, bathing, and grooming support.', 3, 'images/profile.jpg', 'Approved', 'Flexible - Boudha'),
(54, 'Home beauty, wellness, grooming, massage, and personal care.', 6, 'images/profile.jpg', 'Approved', 'Weekends and afternoons - Lalitpur'),
(55, 'Elderly companionship, medicine reminders, and mobility support.', 8, 'images/profile.jpg', 'Approved', 'Day and night shifts - Kathmandu'),
(56, 'Toddler care, after-school supervision, meals, and play routines.', 5, 'images/profile.jpg', 'Approved', 'After school - Bhaktapur'),
(57, 'Balcony gardens, pruning, soil care, and kitchen garden setup.', 4, 'images/profile.jpg', 'Approved', 'Flexible - Lalitpur'),
(58, 'Leak repair, fixtures, drainage, water line maintenance.', 6, 'images/profile.jpg', 'Approved', 'On call - Kathmandu'),
(59, 'Switches, wiring, lighting, inverter points, and appliance faults.', 7, 'images/profile.jpg', 'Approved', 'On call - Bhaktapur');

INSERT IGNORE INTO worker_categories (worker_id, category_id)
SELECT 50, category_id FROM categories WHERE category_name = 'House Work';
INSERT IGNORE INTO worker_categories (worker_id, category_id)
SELECT 51, category_id FROM categories WHERE category_name = 'Culinary Aid';
INSERT IGNORE INTO worker_categories (worker_id, category_id)
SELECT 52, category_id FROM categories WHERE category_name = 'Home Tuition';
INSERT IGNORE INTO worker_categories (worker_id, category_id)
SELECT 53, category_id FROM categories WHERE category_name = 'Pet Care';
INSERT IGNORE INTO worker_categories (worker_id, category_id)
SELECT 54, category_id FROM categories WHERE category_name = 'Self Care';
INSERT IGNORE INTO worker_categories (worker_id, category_id)
SELECT 55, category_id FROM categories WHERE category_name = 'Elderly Care';
INSERT IGNORE INTO worker_categories (worker_id, category_id)
SELECT 56, category_id FROM categories WHERE category_name = 'Babysitting';
INSERT IGNORE INTO worker_categories (worker_id, category_id)
SELECT 57, category_id FROM categories WHERE category_name = 'Gardening';
INSERT IGNORE INTO worker_categories (worker_id, category_id)
SELECT 58, category_id FROM categories WHERE category_name = 'Plumbing';
INSERT IGNORE INTO worker_categories (worker_id, category_id)
SELECT 59, category_id FROM categories WHERE category_name = 'Electrical Work';

INSERT IGNORE INTO jobs (employer_id, category_id, title, description, job_type, salary, location)
SELECT 1, category_id, 'Daytime Elderly Caregiver', 'Companionship, meal reminders, medicine reminders, and mobility assistance.', 'Full/Part Time', 28000, 'Kathmandu'
FROM categories WHERE category_name = 'Elderly Care';
INSERT IGNORE INTO jobs (employer_id, category_id, title, description, job_type, salary, location)
SELECT 1, category_id, 'After-school Babysitter', 'Pickup support, snacks, homework routine, and play supervision.', 'Part time', 18000, 'Kathmandu'
FROM categories WHERE category_name = 'Babysitting';
INSERT IGNORE INTO jobs (employer_id, category_id, title, description, job_type, salary, location)
SELECT 1, category_id, 'Kitchen Garden Setup', 'Set up herbs, vegetables, pots, composting, and care instructions.', 'Fixed-Price', 18000, 'Lalitpur'
FROM categories WHERE category_name = 'Gardening';
INSERT IGNORE INTO jobs (employer_id, category_id, title, description, job_type, salary, location)
SELECT 1, category_id, 'Leak Repair Plumber', 'Fix tap leaks, pipe joints, sink drains, and bathroom fittings.', 'Freelance', 15000, 'Kathmandu'
FROM categories WHERE category_name = 'Plumbing';
INSERT IGNORE INTO jobs (employer_id, category_id, title, description, job_type, salary, location)
SELECT 1, category_id, 'Home Electrician', 'Repair switches, lights, wiring issues, fans, and power outlets safely.', 'Freelance', 18000, 'Kathmandu'
FROM categories WHERE category_name = 'Electrical Work';

INSERT IGNORE INTO users (user_id, full_name, username, email, phone, password, role) VALUES
(20, 'Aarav Sharma', 'aarav_sharma', 'aarav.sharma@gharsathi.local', '9811000020', 'employer123', 'Employer'),
(21, 'Bina Koirala', 'bina_koirala', 'bina.koirala@gharsathi.local', '9811000021', 'employer123', 'Employer'),
(22, 'Nitesh Maharjan', 'nitesh_maharjan', 'nitesh.maharjan@gharsathi.local', '9811000022', 'employer123', 'Employer');

INSERT INTO booking_requests (job_id, employer_id, worker_id, service_id, category_id, booking_date, requested_date, service_category, notes, status)
SELECT jobs.job_id, history.employer_id, workers.worker_id, jobs.job_id, categories.category_id, history.completed_on, history.completed_on, workers.category_name, 'Seed completed service history', 'Completed'
FROM (
    SELECT 50 AS worker_id, 'House Work' AS category_name UNION ALL
    SELECT 51, 'Culinary Aid' UNION ALL
    SELECT 52, 'Home Tuition' UNION ALL
    SELECT 53, 'Pet Care' UNION ALL
    SELECT 54, 'Self Care' UNION ALL
    SELECT 55, 'Elderly Care' UNION ALL
    SELECT 56, 'Babysitting' UNION ALL
    SELECT 57, 'Gardening' UNION ALL
    SELECT 58, 'Plumbing' UNION ALL
    SELECT 59, 'Electrical Work'
) workers
CROSS JOIN (
    SELECT 20 AS employer_id, '2026-04-12' AS completed_on, 4 AS rating, 'Arrived on time and completed the service carefully.' AS review_text UNION ALL
    SELECT 21, '2026-05-08', 5, 'Communication was clear and the work quality was excellent.' UNION ALL
    SELECT 22, '2026-06-03', 4, 'Very dependable service and respectful inside the home.'
) history
INNER JOIN categories ON categories.category_name = workers.category_name
INNER JOIN jobs ON jobs.job_id = (
    SELECT MIN(category_jobs.job_id)
    FROM jobs category_jobs
    WHERE category_jobs.category_id = categories.category_id
)
WHERE NOT EXISTS (
    SELECT 1 FROM booking_requests existing
    WHERE existing.worker_id = workers.worker_id
      AND existing.employer_id = history.employer_id
      AND existing.job_id = jobs.job_id
      AND existing.booking_date = history.completed_on
      AND existing.status = 'Completed'
);

UPDATE booking_requests SET request_id = booking_id WHERE request_id IS NULL;

INSERT INTO employment_status (worker_id, employer_id, service_id, status, start_date, completion_date)
SELECT workers.worker_id, history.employer_id, jobs.job_id, 'Service Completed', history.completed_on, history.completed_on
FROM (
    SELECT 50 AS worker_id, 'House Work' AS category_name UNION ALL
    SELECT 51, 'Culinary Aid' UNION ALL
    SELECT 52, 'Home Tuition' UNION ALL
    SELECT 53, 'Pet Care' UNION ALL
    SELECT 54, 'Self Care' UNION ALL
    SELECT 55, 'Elderly Care' UNION ALL
    SELECT 56, 'Babysitting' UNION ALL
    SELECT 57, 'Gardening' UNION ALL
    SELECT 58, 'Plumbing' UNION ALL
    SELECT 59, 'Electrical Work'
) workers
CROSS JOIN (
    SELECT 20 AS employer_id, '2026-04-12' AS completed_on UNION ALL
    SELECT 21, '2026-05-08' UNION ALL
    SELECT 22, '2026-06-03'
) history
INNER JOIN categories ON categories.category_name = workers.category_name
INNER JOIN jobs ON jobs.job_id = (
    SELECT MIN(category_jobs.job_id)
    FROM jobs category_jobs
    WHERE category_jobs.category_id = categories.category_id
)
WHERE NOT EXISTS (
    SELECT 1 FROM employment_status existing
    WHERE existing.worker_id = workers.worker_id
      AND existing.employer_id = history.employer_id
      AND existing.service_id = jobs.job_id
      AND existing.status = 'Service Completed'
      AND existing.completion_date = history.completed_on
);

INSERT INTO reviews (reviewer_id, reviewee_id, worker_id, employer_id, booking_id, rating, comment, review_comment, review_date)
SELECT history.employer_id, workers.worker_id, workers.worker_id, history.employer_id, booking_requests.booking_id, history.rating, history.review_text, history.review_text, history.completed_on
FROM (
    SELECT 50 AS worker_id, 'House Work' AS category_name UNION ALL
    SELECT 51, 'Culinary Aid' UNION ALL
    SELECT 52, 'Home Tuition' UNION ALL
    SELECT 53, 'Pet Care' UNION ALL
    SELECT 54, 'Self Care' UNION ALL
    SELECT 55, 'Elderly Care' UNION ALL
    SELECT 56, 'Babysitting' UNION ALL
    SELECT 57, 'Gardening' UNION ALL
    SELECT 58, 'Plumbing' UNION ALL
    SELECT 59, 'Electrical Work'
) workers
CROSS JOIN (
    SELECT 20 AS employer_id, '2026-04-12' AS completed_on, 4 AS rating, 'Arrived on time and completed the service carefully.' AS review_text UNION ALL
    SELECT 21, '2026-05-08', 5, 'Communication was clear and the work quality was excellent.' UNION ALL
    SELECT 22, '2026-06-03', 4, 'Very dependable service and respectful inside the home.'
) history
INNER JOIN categories ON categories.category_name = workers.category_name
INNER JOIN jobs ON jobs.job_id = (
    SELECT MIN(category_jobs.job_id)
    FROM jobs category_jobs
    WHERE category_jobs.category_id = categories.category_id
)
INNER JOIN booking_requests ON booking_requests.worker_id = workers.worker_id
    AND booking_requests.employer_id = history.employer_id
    AND booking_requests.job_id = jobs.job_id
    AND booking_requests.booking_date = history.completed_on
    AND booking_requests.status = 'Completed'
WHERE NOT EXISTS (
    SELECT 1 FROM reviews existing
    WHERE existing.worker_id = workers.worker_id
      AND existing.employer_id = history.employer_id
      AND existing.booking_id = booking_requests.booking_id
);
