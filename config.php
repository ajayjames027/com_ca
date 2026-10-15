<?php
/**
 * Configuration for HACKFEST '26
 * Department of Commerce Computer Applications
 * St. Joseph's College (Autonomous), Tiruchirappalli - 620 002
 * Developed by FrontierWox Tech Private Limited
 */

// Prevent multiple inclusion
if (defined('HACKFEST_CONFIG_LOADED')) {
    return;
}
define('HACKFEST_CONFIG_LOADED', true);

// -------------------------------------------------------------
// 1. DATABASE CONFIGURATION (BigRock cPanel MySQL)
// -------------------------------------------------------------
// When setting up in BigRock cPanel:
//   1. Go to cPanel -> MySQL Database Wizard
//   2. Create database (e.g. yourcpanel_hackfest)
//   3. Create user with strong password & grant ALL PRIVILEGES
//   4. Import schema.sql in phpMyAdmin
//   5. Replace DB_NAME, DB_USER, DB_PASS below with your values
// -------------------------------------------------------------
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'hackfest26');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// -------------------------------------------------------------
// 2. ADMIN CREDENTIALS
// -------------------------------------------------------------
define('ADMIN_USER', getenv('ADMIN_USER') ?: 'admin');
define('ADMIN_PASS', getenv('ADMIN_PASS') ?: 'admin123');

// -------------------------------------------------------------
// 3. EMAIL DISPATCH CONFIGURATION (Gmail SMTP)
// -------------------------------------------------------------
define('EMAIL_HOST', 'smtp.gmail.com');
define('EMAIL_PORT', 465); // SSL
define('EMAIL_USER', getenv('EMAIL_USER') ?: 'ajayjames027@gmail.com');
define('EMAIL_PASS', getenv('EMAIL_PASS') ?: 'ohksocfoozjdwqok'); // Gmail App Password
define('EMAIL_FROM_NAME', "HACKFEST '26 - St. Joseph's College");

// -------------------------------------------------------------
// 4. EVENT & COMMUNITY LINKS
// -------------------------------------------------------------
define('WHATSAPP_LINK', 'https://chat.whatsapp.com/CGQ2aJinJQNIbKR39taZc0');
define('COLLEGE_PAYMENT_URL', 'https://service.sjctni.edu/Seminar/25/index.php?programcode=CC21124631');

// -------------------------------------------------------------
// 5. HELPER: Set CORS & Common Headers
// -------------------------------------------------------------
function setCorsHeaders() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// -------------------------------------------------------------
// 6. HELPER: JSON Response
// -------------------------------------------------------------
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// -------------------------------------------------------------
// 7. HELPER: Database Connection (PDO with MySQL / SQLite fallback)
// -------------------------------------------------------------
function getDatabase() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    // Try MySQL first
    $mysqlDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    try {
        $pdo = new PDO($mysqlDsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return $pdo;
    } catch (PDOException $mysqlEx) {
        // Fallback: If MySQL is not running locally and SQLite hackfest.db is present, use SQLite
        $sqlitePath = __DIR__ . '/hackfest.db';
        if (file_exists($sqlitePath) && extension_loaded('pdo_sqlite')) {
            try {
                $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                return $pdo;
            } catch (PDOException $sqliteEx) {
                // Ignore and proceed to display MySQL error
            }
        }

        // Return clean JSON error
        jsonResponse([
            'error' => 'Database connection failed: ' . $mysqlEx->getMessage(),
            'hint'  => 'Please verify your database credentials in config.php and make sure schema.sql has been imported into your MySQL database in cPanel.'
        ], 500);
    }
}
