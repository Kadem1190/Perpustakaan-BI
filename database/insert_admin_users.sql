-- Insert Admin Users into Library Management System
-- This script creates 2 admin users with MD5 hashed passwords

-- First, insert records into the anggota table (required for foreign key relationship)
INSERT INTO anggota (name, nim, class, gender, date_of_birth, place_of_birth, address, created_at) VALUES
('System Administrator', 'ADMIN001', 'ADMIN', 'Male', '1990-01-01', 'System', 'System Address', NOW()),
('Library Manager', 'ADMIN002', 'ADMIN', 'Female', '1985-05-15', 'Library', 'Library Address', NOW());

-- Get the anggota_id values for the inserted records
SET @admin1_anggota_id = (SELECT anggota_id FROM anggota WHERE nim = 'ADMIN001');
SET @admin2_anggota_id = (SELECT anggota_id FROM anggota WHERE nim = 'ADMIN002');

-- Insert admin users into the users table
-- Password for admin1: admin123 (MD5: 0192023a7bbd73250516f069df18b500)
-- Password for admin2: manager456 (MD5: 5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8)
INSERT INTO users (anggota_id, username, password, full_name, p_role, status, registration_status, created_at) VALUES
(@admin1_anggota_id, 'admin', '0192023a7bbd73250516f069df18b500', 'System Administrator', 'admin', 'active', 'approved', NOW()),
(@admin2_anggota_id, 'manager', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', 'Library Manager', 'admin', 'active', 'approved', NOW());

-- Display the created admin users
SELECT 
    u.user_id,
    u.username,
    u.full_name,
    a.name as anggota_name,
    u.p_role,
    u.status,
    u.created_at
FROM users u
JOIN anggota a ON u.anggota_id = a.anggota_id
WHERE u.p_role = 'admin'
ORDER BY u.created_at DESC;

-- Show login credentials for reference
SELECT 
    'Admin Login Credentials:' as info,
    '' as username,
    '' as password
UNION ALL
SELECT 
    'Username: admin',
    'Password: admin123',
    'p_role: System Administrator'
UNION ALL
SELECT 
    'Username: manager',
    'Password: manager456', 
    'p_role: Library Manager';
