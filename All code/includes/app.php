<?php
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = __DIR__ . '/../tmp/sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0775, true);
    }
    session_save_path($sessionPath);
    session_start();
}

require_once __DIR__ . '/db.php';

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function project_path(string $base, string $path): string {
    if (preg_match('/^(?:[a-z][a-z0-9+.-]*:|\/|#)/i', $path) || strpos($path, '../') === 0 || strpos($path, './') === 0) {
        return $path;
    }

    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function table_has_column(mysqli $conn, string $table, string $column): bool {
    $stmt = mysqli_prepare($conn, 'SHOW COLUMNS FROM `' . $table . '` LIKE ?');
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 's', $column);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $exists = $result && mysqli_num_rows($result) > 0;
    mysqli_stmt_close($stmt);
    return $exists;
}

function ensure_column(mysqli $conn, string $table, string $column, string $definition): void {
    if (!table_has_column($conn, $table, $column)) {
        mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN $definition");
    }
}

function ensure_booking_status_values(mysqli $conn): void {
    mysqli_query($conn, "ALTER TABLE booking_requests MODIFY status ENUM('Pending','Accepted','Rejected','Completed') NOT NULL DEFAULT 'Pending'");
}

function ensure_app_schema(mysqli $conn): void {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        username VARCHAR(50) UNIQUE,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(20),
        password VARCHAR(255) NOT NULL,
        role ENUM('Employer','Worker','Admin') NOT NULL DEFAULT 'Employer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    ensure_column($conn, 'users', 'created_at', "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS categories (
        category_id INT AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(100) NOT NULL UNIQUE
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS jobs (
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
    )");
    ensure_column($conn, 'jobs', 'created_at', "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS worker_profiles (
        profile_id INT AUTO_INCREMENT PRIMARY KEY,
        worker_id INT NOT NULL,
        skills TEXT,
        experience_years INT DEFAULT 0,
        profile_image VARCHAR(255),
        verification_status VARCHAR(30) NOT NULL DEFAULT 'Pending',
        current_status VARCHAR(60) NOT NULL DEFAULT 'Available',
        FOREIGN KEY (worker_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");
    ensure_column($conn, 'worker_profiles', 'profile_image', "profile_image VARCHAR(255)");
    ensure_column($conn, 'worker_profiles', 'verification_status', "verification_status VARCHAR(30) NOT NULL DEFAULT 'Pending'");
    ensure_column($conn, 'worker_profiles', 'current_status', "current_status VARCHAR(60) NOT NULL DEFAULT 'Available'");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS worker_categories (
        worker_id INT NOT NULL,
        category_id INT NOT NULL,
        PRIMARY KEY (worker_id, category_id),
        FOREIGN KEY (worker_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS job_applications (
        application_id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        worker_id INT NOT NULL,
        cover_letter TEXT,
        status VARCHAR(50) NOT NULL DEFAULT 'Pending',
        resume_text TEXT,
        resume_file VARCHAR(255),
        admin_status VARCHAR(30) NOT NULL DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE,
        FOREIGN KEY (worker_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");
    ensure_column($conn, 'job_applications', 'resume_text', "resume_text TEXT");
    ensure_column($conn, 'job_applications', 'resume_file', "resume_file VARCHAR(255)");
    ensure_column($conn, 'job_applications', 'police_report_file', "police_report_file VARCHAR(255)");
    ensure_column($conn, 'job_applications', 'citizenship_file', "citizenship_file VARCHAR(255)");
    ensure_column($conn, 'job_applications', 'admin_status', "admin_status VARCHAR(30) NOT NULL DEFAULT 'Pending'");
    ensure_column($conn, 'job_applications', 'created_at', "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS resume_uploads (
        resume_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");
    ensure_column($conn, 'applications', 'resume_path', "resume_path VARCHAR(255)");
    ensure_column($conn, 'applications', 'police_report_path', "police_report_path VARCHAR(255)");
    ensure_column($conn, 'applications', 'citizenship_card_path', "citizenship_card_path VARCHAR(255)");
    ensure_column($conn, 'applications', 'upload_date', "upload_date TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS application_documents (
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
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS police_report_uploads (
        report_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS citizenship_uploads (
        citizenship_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_citizenship (user_id),
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS applications (
        application_id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        worker_id INT NOT NULL,
        resume_id INT NULL,
        cover_letter TEXT,
        status VARCHAR(50) NOT NULL DEFAULT 'Pending',
        admin_status VARCHAR(30) NOT NULL DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE,
        FOREIGN KEY (worker_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (resume_id) REFERENCES resume_uploads(resume_id) ON DELETE SET NULL
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS hire_requests (
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
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS booking_requests (
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
        status ENUM('Pending','Accepted','Rejected') NOT NULL DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE,
        FOREIGN KEY (employer_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (worker_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
    )");
    ensure_column($conn, 'booking_requests', 'request_id', "request_id INT NULL");
    ensure_column($conn, 'booking_requests', 'service_id', "service_id INT NOT NULL DEFAULT 0");
    ensure_column($conn, 'booking_requests', 'category_id', "category_id INT NOT NULL DEFAULT 0");
    ensure_column($conn, 'booking_requests', 'booking_date', "booking_date DATE NULL");
    ensure_column($conn, 'booking_requests', 'requested_date', "requested_date DATE NULL");
    ensure_column($conn, 'booking_requests', 'service_category', "service_category VARCHAR(100)");
    ensure_booking_status_values($conn);
    mysqli_query($conn, "UPDATE booking_requests SET request_id = booking_id WHERE request_id IS NULL");
    mysqli_query($conn, "UPDATE booking_requests SET booking_date = requested_date WHERE booking_date IS NULL AND requested_date IS NOT NULL");
    mysqli_query($conn, "UPDATE booking_requests SET requested_date = booking_date WHERE requested_date IS NULL AND booking_date IS NOT NULL");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS booked_dates (
        booking_id INT AUTO_INCREMENT PRIMARY KEY,
        worker_id INT NOT NULL,
        booking_date DATE NOT NULL,
        request_id INT NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'Reserved',
        UNIQUE KEY unique_worker_booking_date (worker_id, booking_date),
        FOREIGN KEY (request_id) REFERENCES booking_requests(booking_id) ON DELETE CASCADE,
        FOREIGN KEY (worker_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");
    ensure_column($conn, 'booked_dates', 'booking_date', "booking_date DATE NULL");
    ensure_column($conn, 'booked_dates', 'request_id', "request_id INT NULL");
    ensure_column($conn, 'booked_dates', 'status', "status VARCHAR(30) NOT NULL DEFAULT 'Reserved'");
    if (table_has_column($conn, 'booked_dates', 'booked_date')) {
        mysqli_query($conn, "UPDATE booked_dates SET booking_date = booked_date WHERE booking_date IS NULL");
    }

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS employment_status (
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
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS contact_exchanges (
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
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(150) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS subscribers (
        subscriber_id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL UNIQUE,
        subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
        subscription_id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL UNIQUE
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS contact_messages (
        message_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        first_name VARCHAR(150) NOT NULL,
        email VARCHAR(100) NOT NULL,
        subject VARCHAR(150) DEFAULT 'General Inquiry',
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    ensure_column($conn, 'contact_messages', 'name', "name VARCHAR(150) NOT NULL DEFAULT ''");
    ensure_column($conn, 'contact_messages', 'first_name', "first_name VARCHAR(150) NOT NULL DEFAULT ''");
    ensure_column($conn, 'contact_messages', 'subject', "subject VARCHAR(150) DEFAULT 'General Inquiry'");
    ensure_column($conn, 'contact_messages', 'created_at', "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    ensure_column($conn, 'contact_messages', 'submitted_at', "submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS reviews (
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
    )");
    ensure_column($conn, 'reviews', 'worker_id', "worker_id INT NULL");
    ensure_column($conn, 'reviews', 'employer_id', "employer_id INT NULL");
    ensure_column($conn, 'reviews', 'review_comment', "review_comment TEXT");
    ensure_column($conn, 'reviews', 'review_date', "review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS complaints (
        complaint_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        booking_id INT NULL,
        subject VARCHAR(150) NOT NULL,
        message TEXT NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'Open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
    )");

    mysqli_query($conn, "INSERT IGNORE INTO categories (category_name) VALUES
        ('House Work'), ('Culinary Aid'), ('Culinary Service'), ('Home Tuition'),
        ('Education'), ('Pet Care'), ('Self Care'), ('Elderly Care'), ('Babysitting'),
        ('Gardening'), ('Plumbing'), ('Electrical Work'), ('Personal'), ('Repair'), ('Other Services')");

    mysqli_query($conn, "INSERT IGNORE INTO users (user_id, full_name, username, email, phone, password, role) VALUES
        (1, 'Ghar Sathi Admin', 'admin', 'admin@gharsathi.local', '9800000000', 'admin123', 'Admin')");

    seed_sample_content($conn);
}

function seed_sample_content(mysqli $conn): void {
    $jobs = [
        ['House Work', 'Daily House Cleaner', 'Sweeping, mopping, laundry, dishwashing, and routine home upkeep for a family apartment.', 'Full/Part Time', 20000, 'Kathmandu'],
        ['House Work', 'Deep Cleaning Helper', 'Kitchen, bathroom, windows, and seasonal deep cleaning for busy households.', 'Part time', 12000, 'Lalitpur'],
        ['House Work', 'Live-out Domestic Assistant', 'Morning household support including laundry, organization, and light errands.', 'Part time', 18000, 'Bhaktapur'],
        ['Culinary Aid', 'Family Meal Cook', 'Prepare Nepali meals, manage grocery lists, and keep the kitchen organized.', 'Full/Part Time', 22000, 'Bouddha, Kathmandu'],
        ['Culinary Aid', 'Event Kitchen Assistant', 'Support chopping, plating, serving, and cleanup for small family events.', 'Seasonal', 15000, 'Patan'],
        ['Culinary Aid', 'Healthy Tiffin Cook', 'Cook balanced breakfast and lunch tiffins for a working family.', 'Part time', 16000, 'Baneshwor'],
        ['Home Tuition', 'Primary Level Tutor', 'Tutor grades 1-5 in English, Mathematics, Nepali, and homework routines.', 'Part time', 14000, 'Kathmandu'],
        ['Home Tuition', 'SEE Math and Science Tutor', 'Focused home tuition for SEE students with weekly progress tracking.', 'Part time', 25000, 'Lalitpur'],
        ['Home Tuition', 'Early Reading Tutor', 'Phonics, handwriting, reading confidence, and playful learning at home.', 'Part time', 10000, 'Bhaktapur'],
        ['Pet Care', 'Dog Walker and Feeder', 'Daily walks, feeding, medication reminders, and basic pet companionship.', 'Freelance', 12000, 'Kathmandu'],
        ['Pet Care', 'Pet Grooming Assistant', 'Bathing, brushing, nail trimming support, and hygiene care for cats and dogs.', 'Part time', 18000, 'Lalitpur'],
        ['Pet Care', 'Vacation Pet Sitter', 'Short-term pet sitting while families travel, including feeding and updates.', 'Seasonal', 20000, 'Boudha'],
        ['Self Care', 'Home Beauty Assistant', 'At-home threading, basic facial, hair oil massage, and grooming support.', 'Freelance', 18000, 'Kathmandu'],
        ['Self Care', 'Wellness Companion', 'Light exercise support, appointment assistance, and personal care routines.', 'Part time', 20000, 'Lalitpur'],
        ['Self Care', 'Spa and Grooming Helper', 'Manicure, pedicure, hair care, and relaxing self-care services at home.', 'Freelance', 22000, 'Bhaktapur'],
        ['Elderly Care', 'Daytime Elderly Caregiver', 'Companionship, meal reminders, medicine reminders, and mobility assistance.', 'Full/Part Time', 28000, 'Kathmandu'],
        ['Elderly Care', 'Night Care Attendant', 'Overnight supervision, comfort support, and family updates for elderly care.', 'Full/Part Time', 32000, 'Lalitpur'],
        ['Elderly Care', 'Hospital Visit Companion', 'Assist with appointments, reports, travel, and post-visit coordination.', 'Freelance', 15000, 'Bhaktapur'],
        ['Babysitting', 'After-school Babysitter', 'Pickup support, snacks, homework routine, and play supervision.', 'Part time', 18000, 'Kathmandu'],
        ['Babysitting', 'Toddler Care Helper', 'Safe toddler supervision, feeding support, naps, and play activities.', 'Full/Part Time', 26000, 'Lalitpur'],
        ['Babysitting', 'Weekend Childcare', 'Weekend babysitting for events, errands, and family commitments.', 'Freelance', 12000, 'Bhaktapur'],
        ['Gardening', 'Balcony Garden Care', 'Watering, trimming, soil care, and seasonal plant maintenance.', 'Part time', 10000, 'Kathmandu'],
        ['Gardening', 'Kitchen Garden Setup', 'Set up herbs, vegetables, pots, composting, and care instructions.', 'Fixed-Price', 18000, 'Lalitpur'],
        ['Gardening', 'Lawn and Yard Helper', 'Grass cutting, weeding, pruning, and garden cleanup for homes.', 'Freelance', 16000, 'Bhaktapur'],
        ['Plumbing', 'Leak Repair Plumber', 'Fix tap leaks, pipe joints, sink drains, and bathroom fittings.', 'Freelance', 15000, 'Kathmandu'],
        ['Plumbing', 'Bathroom Fixture Installer', 'Install faucets, showers, commodes, and kitchen sink fittings.', 'Fixed-Price', 22000, 'Lalitpur'],
        ['Plumbing', 'Water Tank Line Service', 'Inspect tank lines, pressure issues, and household water flow problems.', 'Freelance', 18000, 'Bhaktapur'],
        ['Electrical Work', 'Home Electrician', 'Repair switches, lights, wiring issues, fans, and power outlets safely.', 'Freelance', 18000, 'Kathmandu'],
        ['Electrical Work', 'Inverter and Lighting Setup', 'Install backup lighting, inverter points, and efficient home lighting.', 'Fixed-Price', 26000, 'Lalitpur'],
        ['Electrical Work', 'Appliance Wiring Technician', 'Diagnose appliance wiring, tripping issues, and minor electrical faults.', 'Part time', 20000, 'Bhaktapur'],
    ];
    foreach ($jobs as $job) {
        seed_job($conn, ...$job);
    }

    $workers = [
        ['Sita Tamang', 'House Work', 5, 'Kathmandu', 'Available weekdays', 4.8, 'Cleaning, laundry, kitchen organization, and deep cleaning.'],
        ['Maya Shrestha', 'Culinary Aid', 7, 'Patan', 'Mornings and events', 4.9, 'Nepali meals, tiffin planning, event kitchen support.'],
        ['Ramesh Karki', 'Home Tuition', 4, 'Baneshwor', 'Evenings', 4.7, 'Math, science, English, homework routines, SEE preparation.'],
        ['Puja Rai', 'Pet Care', 3, 'Boudha', 'Flexible', 4.6, 'Dog walking, feeding, pet sitting, bathing, and grooming support.'],
        ['Anita Gurung', 'Self Care', 6, 'Lalitpur', 'Weekends and afternoons', 4.8, 'Home beauty, wellness, grooming, massage, and personal care.'],
        ['Hari Prasad Adhikari', 'Elderly Care', 8, 'Kathmandu', 'Day and night shifts', 4.9, 'Elderly companionship, medicine reminders, and mobility support.'],
        ['Sunita Lama', 'Babysitting', 5, 'Bhaktapur', 'After school', 4.8, 'Toddler care, after-school supervision, meals, and play routines.'],
        ['Kiran Thapa', 'Gardening', 4, 'Lalitpur', 'Flexible', 4.6, 'Balcony gardens, pruning, soil care, and kitchen garden setup.'],
        ['Bikash Maharjan', 'Plumbing', 6, 'Kathmandu', 'On call', 4.7, 'Leak repair, fixtures, drainage, water line maintenance.'],
        ['Nabin K.C.', 'Electrical Work', 7, 'Bhaktapur', 'On call', 4.8, 'Switches, wiring, lighting, inverter points, and appliance faults.'],
        ['Rekha Basnet', 'House Work', 4, 'Lalitpur', 'Available mornings', 4.5, 'Daily cleaning, dishes, laundry, and room organization.'],
        ['Deepak Bhandari', 'Plumbing', 9, 'Patan', 'Emergency visits', 4.9, 'Bathroom fittings, tank lines, and household plumbing repairs.'],
    ];
    foreach ($workers as $index => $worker) {
        seed_worker($conn, $index + 50, ...$worker);
    }
}

function seed_job(mysqli $conn, string $category, string $title, string $description, string $type, float $salary, string $location): void {
    $categoryId = 0;
    $stmt = mysqli_prepare($conn, 'SELECT category_id FROM categories WHERE category_name = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $category);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if ($row) {
        $categoryId = (int) $row['category_id'];
    }
    if ($categoryId <= 0) {
        return;
    }
    $stmt = mysqli_prepare($conn, 'SELECT job_id FROM jobs WHERE title = ? AND location = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'ss', $title, $location);
    mysqli_stmt_execute($stmt);
    $exists = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if ($exists) {
        return;
    }
    $employerId = 1;
    $stmt = mysqli_prepare($conn, 'INSERT INTO jobs (employer_id, category_id, title, description, job_type, salary, location) VALUES (?, ?, ?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'iisssds', $employerId, $categoryId, $title, $description, $type, $salary, $location);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function seed_worker(mysqli $conn, int $userId, string $name, string $category, int $experience, string $location, string $availability, float $rating, string $skills): void {
    $email = strtolower(preg_replace('/[^a-z0-9]+/i', '.', $name)) . '@gharsathi.local';
    $username = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $name));
    $phone = '98' . str_pad((string) $userId, 8, '0', STR_PAD_LEFT);
    $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO users (user_id, full_name, username, email, phone, password, role) VALUES (?, ?, ?, ?, ?, 'worker123', 'Worker')");
    mysqli_stmt_bind_param($stmt, 'issss', $userId, $name, $username, $email, $phone);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $profileImage = 'images/profile.jpg';
    $status = 'Available - ' . $availability . ' - ' . $location;
    $stmt = mysqli_prepare($conn, 'SELECT profile_id FROM worker_profiles WHERE worker_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $exists = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$exists) {
        $stmt = mysqli_prepare($conn, "INSERT INTO worker_profiles (worker_id, skills, experience_years, profile_image, verification_status, current_status) VALUES (?, ?, ?, ?, 'Approved', ?)");
        mysqli_stmt_bind_param($stmt, 'isiss', $userId, $skills, $experience, $profileImage, $status);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $categoryId = category_id_by_name($conn, $category);
    if ($categoryId > 0) {
        $stmt = mysqli_prepare($conn, 'INSERT IGNORE INTO worker_categories (worker_id, category_id) VALUES (?, ?)');
        mysqli_stmt_bind_param($stmt, 'ii', $userId, $categoryId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $adminId = 1;
    $comment = 'Reliable, punctual, and professional service.';
    $stmt = mysqli_prepare($conn, 'SELECT review_id FROM reviews WHERE worker_id = ? AND employer_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'ii', $userId, $adminId);
    mysqli_stmt_execute($stmt);
    $hasReview = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$hasReview) {
        $stmt = mysqli_prepare($conn, 'INSERT INTO reviews (reviewer_id, reviewee_id, worker_id, employer_id, rating, comment, review_comment) VALUES (?, ?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iiiiiss', $adminId, $userId, $userId, $adminId, $rating, $comment, $comment);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function category_id_by_name(mysqli $conn, string $category): int {
    $stmt = mysqli_prepare($conn, 'SELECT category_id FROM categories WHERE category_name = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $category);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return (int) ($row['category_id'] ?? 0);
}

function service_image(string $category): string {
    $images = [
        'House Work' => '../images/profile.jpg',
        'Culinary Aid' => '../images/profile.jpg',
        'Culinary Service' => '../images/profile.jpg',
        'Education' => '../images/profile.jpg',
        'Home Tuition' => '../images/profile.jpg',
        'Pet Care' => '../images/profile.jpg',
        'Self Care' => '../images/profile.jpg',
        'Personal' => '../images/profile.jpg',
        'Elderly Care' => '../images/profile.jpg',
        'Babysitting' => '../images/profile.jpg',
        'Gardening' => '../images/profile.jpg',
        'Plumbing' => '../images/profile.jpg',
        'Electrical Work' => '../images/profile.jpg',
        'Repair' => '../images/profile.jpg',
        'Other Services' => '../images/profile.jpg',
    ];
    return $images[$category] ?? '../images/profile.jpg';
}

function allowed_upload_extension(array $file, array $allowed): bool {
    if (empty($file['name'])) {
        return false;
    }
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    return in_array($extension, $allowed, true);
}

function save_uploaded_file(array $file, string $relativeDir, int $userId, array $allowed): ?string {
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK || !allowed_upload_extension($file, $allowed)) {
        return null;
    }
    $uploadDir = dirname(__DIR__) . '/' . trim($relativeDir, '/');
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }
    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
    $storedName = $userId . '_' . time() . '_' . bin2hex(random_bytes(3)) . '_' . $safeName;
    $target = $uploadDir . '/' . $storedName;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return null;
    }
    return trim($relativeDir, '/') . '/' . $storedName;
}

function profile_image_url(?string $filename, string $base = '..'): string {
    $filename = trim($filename ?: 'profile.jpg');
    if (strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
        return $base . '/' . ltrim(str_replace('\\', '/', $filename), '/');
    }

    return $base . '/images/' . $filename;
}

function fetch_workers_for_category(mysqli $conn, int $categoryId, int $limit = 6): array {
    $stmt = mysqli_prepare($conn, "SELECT users.user_id, users.full_name, users.email, users.phone,
            worker_profiles.skills, worker_profiles.experience_years, worker_profiles.profile_image,
            worker_profiles.current_status,
            COALESCE(AVG(reviews.rating), 0) AS avg_rating,
            COUNT(reviews.review_id) AS total_reviews
        FROM users
        INNER JOIN worker_profiles ON worker_profiles.worker_id = users.user_id
        INNER JOIN worker_categories ON worker_categories.worker_id = users.user_id
        LEFT JOIN reviews ON reviews.worker_id = users.user_id OR reviews.reviewee_id = users.user_id
        WHERE users.role = 'Worker'
          AND worker_profiles.verification_status = 'Approved'
          AND worker_categories.category_id = ?
        GROUP BY users.user_id
        ORDER BY avg_rating DESC, worker_profiles.experience_years DESC, users.full_name
        LIMIT ?");
    mysqli_stmt_bind_param($stmt, 'ii', $categoryId, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = $result ? mysqli_fetch_assoc($result) : null) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $rows;
}

function fetch_latest_reviews(mysqli $conn, int $workerId, int $limit = 3): array {
    $stmt = mysqli_prepare($conn, "SELECT reviews.rating, COALESCE(reviews.review_comment, reviews.comment) AS review_comment,
            COALESCE(reviews.review_date, reviews.created_at) AS review_date, users.full_name AS employer_name
        FROM reviews
        LEFT JOIN users ON users.user_id = COALESCE(reviews.employer_id, reviews.reviewer_id)
        WHERE reviews.worker_id = ? OR reviews.reviewee_id = ?
        ORDER BY reviews.review_id DESC
        LIMIT ?");
    if (!$stmt) {
        return [];
    }
    mysqli_stmt_bind_param($stmt, 'iii', $workerId, $workerId, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = $result ? mysqli_fetch_assoc($result) : null) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $rows;
}

function fetch_worker_completed_history(mysqli $conn, int $workerId, int $limit = 5): array {
    $stmt = mysqli_prepare($conn, "SELECT booking_requests.booking_id, jobs.title, users.full_name AS employer_name,
            employment_status.completion_date, employment_status.updated_at,
            reviews.rating, COALESCE(reviews.review_comment, reviews.comment) AS review_summary
        FROM employment_status
        INNER JOIN jobs ON jobs.job_id = employment_status.service_id
        LEFT JOIN booking_requests ON booking_requests.job_id = employment_status.service_id
            AND booking_requests.worker_id = employment_status.worker_id
            AND booking_requests.employer_id = employment_status.employer_id
        LEFT JOIN users ON users.user_id = employment_status.employer_id
        LEFT JOIN reviews ON reviews.booking_id = booking_requests.booking_id
        WHERE employment_status.worker_id = ? AND employment_status.status = 'Service Completed'
        ORDER BY employment_status.id DESC
        LIMIT ?");
    if (!$stmt) {
        return [];
    }
    mysqli_stmt_bind_param($stmt, 'ii', $workerId, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = $result ? mysqli_fetch_assoc($result) : null) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $rows;
}

function current_user(mysqli $conn): ?array {
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = mysqli_prepare($conn, 'SELECT user_id, full_name, username, email, phone, role FROM users WHERE user_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);
    return $user ?: null;
}

function require_user(mysqli $conn, ?string $role = null): array {
    $user = current_user($conn);
    if (!$user) {
        header('Location: /Group-08/Login Page/login.html');
        exit();
    }
    if ($role && $user['role'] !== $role && $user['role'] !== 'Admin') {
        header('Location: /Group-08/dasboard/dashboard.php?auth=denied');
        exit();
    }
    return $user;
}

function create_notification(mysqli $conn, int $userId, string $title, string $message): void {
    $stmt = mysqli_prepare($conn, 'INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
    if (!$stmt) {
        return;
    }
    mysqli_stmt_bind_param($stmt, 'iss', $userId, $title, $message);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function record_employment_status(mysqli $conn, int $workerId, ?int $employerId, ?int $serviceId, string $status, ?string $startDate = null, ?string $completionDate = null): void {
    $stmt = mysqli_prepare($conn, 'INSERT INTO employment_status (worker_id, employer_id, service_id, status, start_date, completion_date) VALUES (?, ?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'iiisss', $workerId, $employerId, $serviceId, $status, $startDate, $completionDate);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, 'UPDATE worker_profiles SET current_status = ? WHERE worker_id = ?');
    mysqli_stmt_bind_param($stmt, 'si', $status, $workerId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function latest_status_for_worker(mysqli $conn, int $workerId): string {
    $stmt = mysqli_prepare($conn, 'SELECT status FROM employment_status WHERE worker_id = ? ORDER BY id DESC LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $workerId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row['status'] ?? 'Available';
}

function render_navbar(mysqli $conn, string $base = '..', string $active = ''): void {
    $user = current_user($conn);
    $home = project_path($base, 'Homepage/homepage.php');
    $jobs = project_path($base, 'Jobs page/jobs.php');
    $about = project_path($base, 'About Us Page/aboutus.php');
    $contact = project_path($base, 'Contact Us Page/contactus.php');
    $dashboard = project_path($base, 'dasboard/dashboard.php');
    $logout = project_path($base, 'Login Page/logout.php');
    $login = project_path($base, 'Login Page/login.html');
    $signup = project_path($base, 'Signup page/signup.html');
    $logo = project_path($base, 'images/logo.png');
    $dashboardLabel = 'Dashboard';
    if ($user) {
        $dashboardLabel = $user['role'] === 'Admin' ? 'Admin Dashboard' : ($user['role'] === 'Worker' ? 'Worker Dashboard' : 'Employer Dashboard');
    }
    ?>
    <nav class="navbar">
        <a class="logo" href="<?php echo e($home); ?>">
            <img src="<?php echo e($logo); ?>" alt="Ghar Sathi logo">
            <span>Ghar Sathi</span>
        </a>
        <ul class="nav-links" id="navLinks">
            <li><a class="<?php echo $active === 'home' ? 'active' : ''; ?>" href="<?php echo e($home); ?>">Home</a></li>
            <li><a class="<?php echo $active === 'jobs' ? 'active' : ''; ?>" href="<?php echo e($jobs); ?>">Jobs</a></li>
            <li><a class="<?php echo $active === 'about' ? 'active' : ''; ?>" href="<?php echo e($about); ?>">About Us</a></li>
            <li><a class="<?php echo $active === 'contact' ? 'active' : ''; ?>" href="<?php echo e($contact); ?>">Contact Us</a></li>
        </ul>
        <div class="auth-buttons nav-buttons">
            <?php if ($user): ?>
                <a class="signup-btn dashboard-icon" href="<?php echo e($dashboard); ?>" title="<?php echo e($dashboardLabel); ?>" aria-label="<?php echo e($dashboardLabel); ?>"><i class="fa-solid fa-table-cells-large"></i></a>
                <a class="login-btn" href="<?php echo e($logout); ?>">Logout</a>
            <?php else: ?>
                <a class="login-btn" href="<?php echo e($login); ?>">Login</a>
                <a class="signup-btn" href="<?php echo e($signup); ?>">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>
    <?php
}

function render_footer(string $base = '..', string $redirect = 'Homepage/homepage.php'): void {
    $subscribe = project_path($base, 'Contact Us Page/subscribe.php');
    $redirectPath = project_path($base, $redirect);
    ?>
    <footer>
    <div class="footer-container">
    <div class="footer-column">
    <h3><i class="fa-solid fa-briefcase"></i> Job</h3>
    <p>Ghar Sathi connects skilled people with trusted opportunities, making everyday services easier, faster, and more reliable for every home.</p>
    </div>
    <div class="footer-column"><h3>About Us</h3><ul><li>Our Team</li><li>For Service Providers</li><li>For Employers</li></ul></div>
    <div class="footer-column"><h3>Job Categories</h3><ul><li>House Work</li><li>Culinary Aid</li><li>Home Tuition</li><li>Pet Care</li><li>Self Care</li></ul></div>
    <div class="footer-column">
    <h3>Be Up to Date!</h3>
    <p>Stay updated with trusted home services and latest job opportunities from Ghar Sathi.</p>
    <form class="subscribe-form" action="<?php echo e($subscribe); ?>" method="POST"><input type="email" name="email" placeholder="Email Address" aria-label="Email Address" required><input type="hidden" name="redirect" value="<?php echo e($redirectPath); ?>"><button type="submit">Subscribe now</button></form>
    </div>
    </div>
    <div class="footer-bottom">
    <p>&copy; Copyright Ghar Sathi 2026.</p>
    <div><a href="#">Privacy Policy</a><a href="#">Terms &amp; Conditions</a></div>
    </div>
    </footer>
    <?php
}

ensure_app_schema($conn);
?>
