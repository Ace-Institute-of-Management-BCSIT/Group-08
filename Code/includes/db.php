<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);
    mysqli_set_charset($conn, 'utf8mb4');
} catch (mysqli_sql_exception $exception) {
    error_log('Ghar Sathi database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit(APP_DEBUG ? 'Database connection failed: ' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') : 'Database connection failed. Please try again later.');
}
