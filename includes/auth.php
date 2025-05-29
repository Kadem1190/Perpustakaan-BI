<?php
// Start session at the very beginning if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
require_once 'functions.php';

/**
 * Redirect helper function
 */
function redirectTo($url) {
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    }
    // Fallback if headers were already sent
    echo '<script>window.location.href="' . $url . '";</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . $url . '"></noscript>';
    exit;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['p_role']);
}

/**
 * Check if user has specific p_role
 */
function hasp_role($p_role) {
    return isLoggedIn() && $_SESSION['p_role'] === $p_role;
}

/**
 * Check if user has any of the specified p_roles
 */
function hasAnyp_role($p_roles) {
    if (!isLoggedIn()) return false;
    return in_array($_SESSION['p_role'], $p_roles);
}

/**
 * Require login - but don't redirect if already on login page
 */
function requireLogin() {
    if (!isLoggedIn() && basename($_SERVER['PHP_SELF']) !== 'login.php') {
        redirectTo('login.php');
    }
}

/**
 * Require specific p_role
 */
function requirep_role($p_role) {
    if (!isLoggedIn()) {
        if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
            redirectTo('login.php');
        }
        return;
    }
    
    if (!hasp_role($p_role)) {
        redirectTo('unauthorized.php');
    }
}

/**
 * Login user with MD5 password hashing
 */
function loginUser($username, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT u.*, a.name FROM users u 
                              LEFT JOIN anggota a ON u.anggota_id = a.anggota_id 
                              WHERE u.username = ? AND u.status = 'active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        // Use MD5 hashing for password verification
        if ($user && md5($password) === $user['password']) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['p_role'] = $user['p_role'];
            $_SESSION['anggota_id'] = $user['anggota_id'];
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
            
            // Log login activity
            logActivity('User login', $user['user_id']);
            
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

/**
 * Hash password using MD5
 */
function hashPassword($password) {
    return md5($password);
}

/**
 * Verify password using MD5
 */
function verifyPassword($password, $hash) {
    return md5($password) === $hash;
}

function getNavigationMenu() {
    $p_role = $_SESSION['p_role'] ?? '';
    $menu = [];
    
    switch ($p_role) {
        case p_role_ADMIN:
            $menu = [
                ['url' => 'dashboard', 'icon' => 'speedometer2', 'text' => 'Dashboard'],
                ['url' => 'users', 'icon' => 'people', 'text' => 'User Management'],
                ['url' => 'books', 'icon' => 'book', 'text' => 'Books'],
                ['url' => 'borrowings', 'icon' => 'bookmark', 'text' => 'Borrowings'],
                ['url' => 'fines', 'icon' => 'cash-stack', 'text' => 'Fines'],
                ['url' => 'statistics', 'icon' => 'bar-chart', 'text' => 'Statistics'],
                ['url' => 'profile', 'icon' => 'person-circle', 'text' => 'Profile']
            ];
            break;
            
        case p_role_STAFF:
            $menu = [
                ['url' => 'dashboard', 'icon' => 'speedometer2', 'text' => 'Dashboard'],
                ['url' => 'members', 'icon' => 'person-plus', 'text' => 'Add Members'],
                ['url' => 'books', 'icon' => 'book', 'text' => 'View Books'],
                ['url' => 'borrowings', 'icon' => 'bookmark', 'text' => 'Borrowings'],
                ['url' => 'fines', 'icon' => 'cash-stack', 'text' => 'Fines'],
                ['url' => 'profile', 'icon' => 'person-circle', 'text' => 'Profile']
            ];
            break;
            
        case p_role_MEMBER:
            $menu = [
                ['url' => 'books', 'icon' => 'book', 'text' => 'Browse Books'],
                ['url' => 'my-borrowings', 'icon' => 'bookmark', 'text' => 'My Borrowings'],
                ['url' => 'my-fines', 'icon' => 'cash', 'text' => 'My Fines'],
                ['url' => 'profile', 'icon' => 'person-circle', 'text' => 'Profile']
            ];
            break;
    }
    
    return $menu;
}
