<?php

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'db_library');
define('DB_USER', 'root');
define('DB_PASS', '');

require_once 'includes/functions.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Clear existing data first
    $pdo->exec("DELETE FROM users");
    $pdo->exec("DELETE FROM anggota");
    
    // First, insert into anggota table
    $stmt = $pdo->prepare("INSERT INTO anggota (name, nim, gender, date_of_birth) VALUES (?, ?, ?, ?)");
    
    // Add admin
    $stmt->execute(['Administrator', 'ADMIN001', 'Male', '1990-01-01']);
    $adminAnggotaId = $pdo->lastInsertId();
    
    // Add staff
    $stmt->execute(['Staff User', 'STAFF001', 'Female', '1995-01-01']);
    $staffAnggotaId = $pdo->lastInsertId();

    // Then, insert into users table
    $stmt = $pdo->prepare("INSERT INTO users (anggota_id, username, password, full_name, p_role, status, registration_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    // Add admin user with debug output
    $adminPassword = md5('admin123');  // Changed to md5()
    echo "Admin password hash: " . $adminPassword . "\n";
    echo "Hash length: " . strlen($adminPassword) . " characters\n\n";
    
    $stmt->execute([
        $adminAnggotaId,
        'admin',
        $adminPassword,
        'Administrator',
        'admin',
        'active',
        'approved'
    ]);

    // Add staff user with debug output
    $staffPassword = md5('staff123');  // Changed to md5()
    echo "Staff password hash: " . $staffPassword . "\n";
    echo "Hash length: " . strlen($staffPassword) . " characters\n\n";
    
    $stmt->execute([
        $staffAnggotaId,
        'staff',
        $staffPassword,
        'Staff User',
        'staff',
        'active',
        'approved'
    ]);

    // Verify data in database
    $users = $pdo->query("SELECT username, password FROM users")->fetchAll();
    echo "Verification from database:\n";
    foreach ($users as $user) {
        echo "Username: " . $user['username'] . "\n";
        echo "Stored hash: " . $user['password'] . "\n";
        echo "Stored hash length: " . strlen($user['password']) . "\n\n";
    }

    echo "Successfully added admin and staff users!\n";
    echo "Admin login: username = admin, password = admin123\n";
    echo "Staff login: username = staff, password = staff123\n";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}