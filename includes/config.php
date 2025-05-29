<?php
// Database configuration
$host = getenv("MYSQL_ADDON_HOST");
$dbname = getenv("MYSQL_ADDON_DB");
$port = getenv("MYSQL_ADDON_PORT");
$username = getenv("MYSQL_ADDON_USER");
$password = getenv("MYSQL_ADDON_PASSWORD");

// Timezone setting
date_default_timezone_set('Asia/Jakarta');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Application settings
define('SITE_NAME', 'Library Management System');
define('ADMIN_EMAIL', 'admin@library.com');
define('ITEMS_PER_PAGE', 10);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('DEFAULT_BORROW_DAYS', 14);
define('MAX_BORROW_BOOKS', 3);
define('FINE_PER_DAY', 1000); // Fine in rupiah per day

// Add after other constants
define('FINE_GRACE_PERIOD', 0); // Days before fine starts counting

// p_role definitions
define('p_role_ADMIN', 'admin');
define('p_role_STAFF', 'staff');
define('p_role_MEMBER', 'member');

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    session_start();
}
