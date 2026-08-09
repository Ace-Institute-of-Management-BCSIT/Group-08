<?php
/**
 * Central production configuration for Ghar Sathi.
 * Keep this file outside public version control after adding your real secrets.
 */

declare(strict_types=1);

// Set to true only while investigating an error, then set it back to false.
const APP_DEBUG = false;

$db_host = 'sql110.infinityfree.com';
$db_name = 'if0_42354102_gharsathi_db';
$db_user = 'if0_42354102';
$db_pass = 'hW2QRg6aWcF6';
$db_port = 3306;

// InfinityFree does not provide PHP mail(). Use an external SMTP account.
$smtp_host = 'smtp.gmail.com';
$smtp_port = 587;
$smtp_encryption = 'tls'; // Use 'tls' for port 587 or 'ssl' for port 465.
$smtp_username = 'gharsathi11@gmail.com';
$smtp_password = 'wcbxbouisyuwhack';
$smtp_from_email = $smtp_username;
$smtp_from_name = 'Ghar Sathi';
