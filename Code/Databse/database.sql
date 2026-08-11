-- ===========================
-- Defines the project database schema and seed data for the Ghar Sathi application.
-- ===========================

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
    hourly_rate DECIMAL(10,2) NOT NULL DEFAULT 2000.00,
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

CREATE TABLE IF NOT EXISTS worker_verifications (
    verification_id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    status ENUM('Pending', 'Accepted', 'Rejected') NOT NULL DEFAULT 'Pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    reviewed_by INT NULL,
    FOREIGN KEY (worker_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL
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
    requested_finish_time TIME NOT NULL DEFAULT '00:00:00',
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
    start_time TIME NOT NULL DEFAULT '00:00:00',
    finish_time TIME NOT NULL DEFAULT '00:00:00',
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

CREATE TABLE IF NOT EXISTS email_otps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    otp VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

-- ===========================
-- Seed Data
-- ===========================
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

-- Seed passwords are converted to PHP password_hash() values after all users are inserted.

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

-- Two distinct, verified workers for every service category. Worker-category rows are unique,
-- so a worker can never be shown twice for the same category.
INSERT IGNORE INTO users (user_id, full_name, username, email, phone, password, role) VALUES
(100, 'Asha Bista', 'asha_bista', 'asha.bista@gharsathi.local', '9810000100', 'worker123', 'Worker'),
(101, 'Laxmi Raut', 'laxmi_raut', 'laxmi.raut@gharsathi.local', '9810000101', 'worker123', 'Worker'),
(102, 'Sarita Thapa', 'sarita_thapa', 'sarita.thapa@gharsathi.local', '9810000102', 'worker123', 'Worker'),
(103, 'Binod Shahi', 'binod_shahi', 'binod.shahi@gharsathi.local', '9810000103', 'worker123', 'Worker'),
(104, 'Rina Khatri', 'rina_khatri', 'rina.khatri@gharsathi.local', '9810000104', 'worker123', 'Worker'),
(105, 'Suman Kandel', 'suman_kandel', 'suman.kandel@gharsathi.local', '9810000105', 'worker123', 'Worker'),
(106, 'Manisha Nepal', 'manisha_nepal', 'manisha.nepal@gharsathi.local', '9810000106', 'worker123', 'Worker'),
(107, 'Prakash Bhandari', 'prakash_bhandari', 'prakash.bhandari@gharsathi.local', '9810000107', 'worker123', 'Worker'),
(108, 'Rojina Basnet', 'rojina_basnet', 'rojina.basnet@gharsathi.local', '9810000108', 'worker123', 'Worker'),
(109, 'Deepak Poudel', 'deepak_poudel', 'deepak.poudel@gharsathi.local', '9810000109', 'worker123', 'Worker'),
(110, 'Kavita Shrestha', 'kavita_shrestha', 'kavita.shrestha@gharsathi.local', '9810000110', 'worker123', 'Worker'),
(111, 'Roshan Lama', 'roshan_lama', 'roshan.lama@gharsathi.local', '9810000111', 'worker123', 'Worker'),
(112, 'Nisha Giri', 'nisha_giri', 'nisha.giri@gharsathi.local', '9810000112', 'worker123', 'Worker'),
(113, 'Sagar Gurung', 'sagar_gurung', 'sagar.gurung@gharsathi.local', '9810000113', 'worker123', 'Worker'),
(114, 'Pema Sherpa', 'pema_sherpa', 'pema.sherpa@gharsathi.local', '9810000114', 'worker123', 'Worker'),
(115, 'Rajan Rai', 'rajan_rai', 'rajan.rai@gharsathi.local', '9810000115', 'worker123', 'Worker'),
(116, 'Mina Chaudhary', 'mina_chaudhary', 'mina.chaudhary@gharsathi.local', '9810000116', 'worker123', 'Worker'),
(117, 'Anish Joshi', 'anish_joshi', 'anish.joshi@gharsathi.local', '9810000117', 'worker123', 'Worker'),
(118, 'Sushma KC', 'sushma_kc', 'sushma.kc@gharsathi.local', '9810000118', 'worker123', 'Worker'),
(119, 'Bimal Acharya', 'bimal_acharya', 'bimal.acharya@gharsathi.local', '9810000119', 'worker123', 'Worker'),
(120, 'Rupa Magar', 'rupa_magar', 'rupa.magar@gharsathi.local', '9810000120', 'worker123', 'Worker'),
(121, 'Milan Adhikari', 'milan_adhikari', 'milan.adhikari@gharsathi.local', '9810000121', 'worker123', 'Worker'),
(122, 'Sabina Maharjan', 'sabina_maharjan', 'sabina.maharjan@gharsathi.local', '9810000122', 'worker123', 'Worker'),
(123, 'Kamal Tamang', 'kamal_tamang', 'kamal.tamang@gharsathi.local', '9810000123', 'worker123', 'Worker'),
(124, 'Nirmala Karki', 'nirmala_karki', 'nirmala.karki@gharsathi.local', '9810000124', 'worker123', 'Worker'),
(125, 'Hemanta Oli', 'hemanta_oli', 'hemanta.oli@gharsathi.local', '9810000125', 'worker123', 'Worker'),
(126, 'Isha Pandey', 'isha_pandey', 'isha.pandey@gharsathi.local', '9810000126', 'worker123', 'Worker'),
(127, 'Naren Bohara', 'naren_bohara', 'naren.bohara@gharsathi.local', '9810000127', 'worker123', 'Worker'),
(128, 'Anju Rana', 'anju_rana', 'anju.rana@gharsathi.local', '9810000128', 'worker123', 'Worker'),
(129, 'Suresh Malla', 'suresh_malla', 'suresh.malla@gharsathi.local', '9810000129', 'worker123', 'Worker');

INSERT INTO worker_profiles (worker_id, skills, experience_years, profile_image, verification_status, current_status)
SELECT u.user_id, CONCAT('Verified ', c.category_name, ' service and household support.'), 3, 'images/profile.jpg', 'Approved', 'Available - Kathmandu'
FROM users u
INNER JOIN (SELECT 100 worker_id, 'House Work' category_name UNION ALL SELECT 101, 'House Work' UNION ALL SELECT 102, 'Culinary Aid' UNION ALL SELECT 103, 'Culinary Aid' UNION ALL SELECT 104, 'Culinary Service' UNION ALL SELECT 105, 'Culinary Service' UNION ALL SELECT 106, 'Home Tuition' UNION ALL SELECT 107, 'Home Tuition' UNION ALL SELECT 108, 'Education' UNION ALL SELECT 109, 'Education' UNION ALL SELECT 110, 'Pet Care' UNION ALL SELECT 111, 'Pet Care' UNION ALL SELECT 112, 'Self Care' UNION ALL SELECT 113, 'Self Care' UNION ALL SELECT 114, 'Elderly Care' UNION ALL SELECT 115, 'Elderly Care' UNION ALL SELECT 116, 'Babysitting' UNION ALL SELECT 117, 'Babysitting' UNION ALL SELECT 118, 'Gardening' UNION ALL SELECT 119, 'Gardening' UNION ALL SELECT 120, 'Plumbing' UNION ALL SELECT 121, 'Plumbing' UNION ALL SELECT 122, 'Electrical Work' UNION ALL SELECT 123, 'Electrical Work' UNION ALL SELECT 124, 'Personal' UNION ALL SELECT 125, 'Personal' UNION ALL SELECT 126, 'Repair' UNION ALL SELECT 127, 'Repair' UNION ALL SELECT 128, 'Other Services' UNION ALL SELECT 129, 'Other Services') c ON c.worker_id=u.user_id
WHERE NOT EXISTS (SELECT 1 FROM worker_profiles wp WHERE wp.worker_id=u.user_id);

INSERT IGNORE INTO worker_categories (worker_id, category_id)
SELECT assignments.worker_id, categories.category_id
FROM (SELECT 100 worker_id, 'House Work' category_name UNION ALL SELECT 101, 'House Work' UNION ALL SELECT 102, 'Culinary Aid' UNION ALL SELECT 103, 'Culinary Aid' UNION ALL SELECT 104, 'Culinary Service' UNION ALL SELECT 105, 'Culinary Service' UNION ALL SELECT 106, 'Home Tuition' UNION ALL SELECT 107, 'Home Tuition' UNION ALL SELECT 108, 'Education' UNION ALL SELECT 109, 'Education' UNION ALL SELECT 110, 'Pet Care' UNION ALL SELECT 111, 'Pet Care' UNION ALL SELECT 112, 'Self Care' UNION ALL SELECT 113, 'Self Care' UNION ALL SELECT 114, 'Elderly Care' UNION ALL SELECT 115, 'Elderly Care' UNION ALL SELECT 116, 'Babysitting' UNION ALL SELECT 117, 'Babysitting' UNION ALL SELECT 118, 'Gardening' UNION ALL SELECT 119, 'Gardening' UNION ALL SELECT 120, 'Plumbing' UNION ALL SELECT 121, 'Plumbing' UNION ALL SELECT 122, 'Electrical Work' UNION ALL SELECT 123, 'Electrical Work' UNION ALL SELECT 124, 'Personal' UNION ALL SELECT 125, 'Personal' UNION ALL SELECT 126, 'Repair' UNION ALL SELECT 127, 'Repair' UNION ALL SELECT 128, 'Other Services' UNION ALL SELECT 129, 'Other Services') assignments
INNER JOIN categories ON categories.category_name=assignments.category_name;

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

-- Give every worker who has no completed-service history three completed jobs.
-- Each row uses a job in the worker's category and a distinct sample employer.
INSERT INTO booking_requests (job_id, employer_id, worker_id, service_id, category_id, booking_date, requested_date, service_category, notes, status)
SELECT selected_job.job_id, history.employer_id, workers.user_id, selected_job.job_id,
       selected_job.category_id, history.completed_on, history.completed_on,
       category.category_name, 'Seed completed service history', 'Completed'
FROM users workers
INNER JOIN jobs selected_job ON selected_job.job_id = (
    SELECT MIN(category_jobs.job_id)
    FROM jobs category_jobs
    INNER JOIN worker_categories worker_category
        ON worker_category.category_id = category_jobs.category_id
    WHERE worker_category.worker_id = workers.user_id
)
INNER JOIN categories category ON category.category_id = selected_job.category_id
CROSS JOIN (
    SELECT 20 AS employer_id, '2026-02-14' AS completed_on UNION ALL
    SELECT 21, '2026-03-18' UNION ALL
    SELECT 22, '2026-04-22'
) history
WHERE workers.role = 'Worker'
  AND NOT EXISTS (
      SELECT 1
      FROM employment_status existing
      WHERE existing.worker_id = workers.user_id
        AND existing.status = 'Service Completed'
  );

UPDATE booking_requests SET request_id = booking_id WHERE request_id IS NULL;

INSERT INTO employment_status (worker_id, employer_id, service_id, status, start_date, completion_date)
SELECT workers.user_id, history.employer_id,
       COALESCE(
           (SELECT MIN(category_jobs.job_id)
            FROM jobs category_jobs
            INNER JOIN worker_categories worker_category
                ON worker_category.category_id = category_jobs.category_id
            WHERE worker_category.worker_id = workers.user_id),
           (SELECT MIN(fallback_jobs.job_id) FROM jobs fallback_jobs)
       ),
       'Service Completed', history.completed_on, history.completed_on
FROM users workers
CROSS JOIN (
    SELECT 20 AS employer_id, '2026-02-14' AS completed_on UNION ALL
    SELECT 21, '2026-03-18' UNION ALL
    SELECT 22, '2026-04-22'
) history
WHERE workers.role = 'Worker'
  AND NOT EXISTS (
      SELECT 1
      FROM employment_status existing
      WHERE existing.worker_id = workers.user_id
        AND existing.status = 'Service Completed'
  );

-- Give each worker without feedback three distinct starter reviews. The query is
-- idempotent: a worker who already has any review is left unchanged.
INSERT INTO reviews (reviewer_id, reviewee_id, worker_id, employer_id, rating, comment, review_comment)
SELECT reviewers.employer_id, workers.user_id, workers.user_id, reviewers.employer_id,
       reviewers.rating, reviewers.review_text, reviewers.review_text
FROM users workers
CROSS JOIN (
    SELECT 20 AS employer_id, 5 AS rating, 'Reliable service, clear communication, and careful work.' AS review_text UNION ALL
    SELECT 21, 4, 'Arrived as agreed and handled the service professionally.' UNION ALL
    SELECT 22, 5, 'Friendly, skilled, and respectful throughout the service.'
) reviewers
WHERE workers.role = 'Worker'
  AND NOT EXISTS (
      SELECT 1 FROM reviews existing WHERE existing.worker_id = workers.user_id
  );

-- Default development accounts: admin/admin123, workers/worker123, employers/employer123.
-- Suggested hourly offers vary slightly between workers in the same service category.
UPDATE worker_profiles wp
INNER JOIN worker_categories wc ON wc.worker_id = wp.worker_id
INNER JOIN categories c ON c.category_id = wc.category_id
SET wp.hourly_rate = CASE wp.worker_id
    WHEN 100 THEN 2150.00
    WHEN 101 THEN 2350.00
    WHEN 102 THEN 2550.00
    WHEN 103 THEN 2750.00
    WHEN 104 THEN 2900.00
    WHEN 105 THEN 3200.00
    WHEN 106 THEN 3800.00
    WHEN 107 THEN 4200.00
    WHEN 108 THEN 3900.00
    WHEN 109 THEN 4300.00
    WHEN 110 THEN 2400.00
    WHEN 111 THEN 2650.00
    WHEN 112 THEN 3300.00
    WHEN 113 THEN 3700.00
    WHEN 114 THEN 3600.00
    WHEN 115 THEN 4000.00
    WHEN 116 THEN 2700.00
    WHEN 117 THEN 2950.00
    WHEN 118 THEN 2200.00
    WHEN 119 THEN 2450.00
    WHEN 120 THEN 4300.00
    WHEN 121 THEN 4700.00
    WHEN 122 THEN 4800.00
    WHEN 123 THEN 5300.00
    WHEN 124 THEN 3300.00
    WHEN 125 THEN 3700.00
    WHEN 126 THEN 4600.00
    WHEN 127 THEN 5100.00
    WHEN 128 THEN 2450.00
    WHEN 129 THEN 2750.00
    ELSE CASE c.category_name
    WHEN 'House Work' THEN 2200.00
    WHEN 'Culinary Aid' THEN 2600.00
    WHEN 'Culinary Service' THEN 3000.00
    WHEN 'Home Tuition' THEN 4000.00
    WHEN 'Education' THEN 4000.00
    WHEN 'Pet Care' THEN 2500.00
    WHEN 'Self Care' THEN 3500.00
    WHEN 'Elderly Care' THEN 3800.00
    WHEN 'Babysitting' THEN 2800.00
    WHEN 'Gardening' THEN 2300.00
    WHEN 'Plumbing' THEN 4500.00
    WHEN 'Electrical Work' THEN 5000.00
    WHEN 'Personal' THEN 3500.00
    WHEN 'Repair' THEN 4800.00
    WHEN 'Other Services' THEN 2600.00
    ELSE 2000.00
    END
END;

UPDATE users SET password = '$2y$10$78fMc8I8ZjM5z/nGLNDjP.8BmkSWdAfubjg.HaubfZpoE.IN.EXzy' WHERE password = 'admin123';
UPDATE users SET password = '$2y$10$vX71K8iCvqzlCMAQtwxTIOEx8WdO.JRp0Qk8p35DRL9x9A4C0SeF2' WHERE password = 'worker123';
UPDATE users SET password = '$2y$10$jpampZPegE2w385WWSYSlOPVJ26Wq3pn8Uv0mqt9JmyACsuH6JfRO' WHERE password = 'employer123';
