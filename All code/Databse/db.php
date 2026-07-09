<?php
/**
 * Creates the database connection used by legacy database includes.
 */

// ===========================
// Bootstrap and Dependencies
// ===========================
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'ghar_sathi';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
?>
