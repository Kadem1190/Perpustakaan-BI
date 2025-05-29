<?php
/**
 * Script to create admin users for the Library Management System
 * Run this script once to set up initial admin accounts
 */

require_once '../includes/config.php';

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Admin user data
    $adminUsers = [
        [
            'name' => 'System Administrator',
            'nim' => 'ADMIN001',
            'username' => 'admin',
            'password' => 'admin123',
            'full_name' => 'System Administrator'
        ],
        [
            'name' => 'Library Manager', 
            'nim' => 'ADMIN002',
            'username' => 'manager',
            'password' => 'manager456',
            'full_name' => 'Library Manager'
        ]
    ];
    
    foreach ($adminUsers as $admin) {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE username = ?");
        $stmt->execute([$admin['username']]);
        $exists = $stmt->fetch()['count'] > 0;
        
        if ($exists) {
            echo "Username '{$admin['username']}' already exists. Skipping...\n";
            continue;
        }
        
        // Insert into anggota table first
        $stmt = $pdo->prepare("INSERT INTO anggota (name, nim, class, gender, date_of_birth, place_of_birth, address, created_at) 
                              VALUES (?, ?, 'ADMIN', 'Male', '1990-01-01', 'System', 'System Address', NOW())");
        $stmt->execute([$admin['name'], $admin['nim']]);
        $anggotaId = $pdo->lastInsertId();
        
        // Hash password with MD5
        $hashedPassword = md5($admin['password']);
        
        // Insert into users table
        $stmt = $pdo->prepare("INSERT INTO users (anggota_id, username, password, full_name, p_role, status, registration_status, created_at) 
                              VALUES (?, ?, ?, ?, 'admin', 'active', 'approved', NOW())");
        $stmt->execute([$anggotaId, $admin['username'], $hashedPassword, $admin['full_name']]);
        
        echo "Created admin user: {$admin['username']} (Password: {$admin['password']})\n";
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Display all admin users
    echo "\n=== Current Admin Users ===\n";
    $stmt = $pdo->prepare("SELECT u.user_id, u.username, u.full_name, a.name as anggota_name, u.status, u.created_at
                          FROM users u 
                          JOIN anggota a ON u.anggota_id = a.anggota_id 
                          WHERE u.p_role = 'admin' 
                          ORDER BY u.created_at DESC");
    $stmt->execute();
    $admins = $stmt->fetchAll();
    
    foreach ($admins as $admin) {
        echo "ID: {$admin['user_id']} | Username: {$admin['username']} | Name: {$admin['full_name']} | Status: {$admin['status']} | Created: {$admin['created_at']}\n";
    }
    
    echo "\n=== Login Credentials ===\n";
    echo "Username: admin | Password: admin123\n";
    echo "Username: manager | Password: manager456\n";
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollback();
    echo "Error creating admin users: " . $e->getMessage() . "\n";
}
?>
