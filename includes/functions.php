<?php
/**
 * Log activity to the logs table
 */
function logActivity($action, $userId = null, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent, created_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt->execute([
            $userId, 
            $action, 
            $tableName, 
            $recordId, 
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $ipAddress,
            $userAgent
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error logging activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if current user is admin
 */
function isAdmin() {
    return isset($_SESSION['admin_id']);
}

/**
 * Get user by ID
 */
function getUserById($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT u.*, a.* FROM users u 
                              JOIN anggota a ON u.anggota_id = a.anggota_id 
                              WHERE u.user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting user: " . $e->getMessage());
        return false;
    }
}

/**
 * Get admin by ID
 */
function getAdminById($adminId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND p_role = 'admin'");
        $stmt->execute([$adminId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting admin: " . $e->getMessage());
        return false;
    }
}

/**
 * Get book by ID
 */
function getBookById($bookId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = ?");
        $stmt->execute([$bookId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting book: " . $e->getMessage());
        return false;
    }
}

/**
 * Get borrowing by ID
 */
function getBorrowingById($borrowingId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT b.*, a.name as anggota_name, bk.title as book_title 
                              FROM borrowings b
                              JOIN anggota a ON b.anggota_id = a.anggota_id
                              JOIN books bk ON b.book_id = bk.book_id
                              WHERE b.borrowing_id = ?");
        $stmt->execute([$borrowingId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting borrowing: " . $e->getMessage());
        return false;
    }
}

/**
 * Format date for display
 */
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

/**
 * Calculate days remaining or overdue
 */
function calculateDaysRemaining($dueDate) {
    $now = new DateTime();
    $due = new DateTime($dueDate);
    $diff = $now->diff($due);
    
    if ($now > $due) {
        return -$diff->days; // Negative number for overdue
    } else {
        return $diff->days; // Positive number for days remaining
    }
}

/**
 * Generate pagination links
 */
function generatePagination($currentPage, $totalPages, $urlPattern) {
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($currentPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, $currentPage - 1) . '">&laquo;</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><a class="page-link" href="#">&laquo;</a></li>';
    }
    
    // Page numbers
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    if ($startPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, 1) . '">1</a></li>';
        if ($startPage > 2) {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $currentPage) {
            $html .= '<li class="page-item active"><a class="page-link" href="#">' . $i . '</a></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, $i) . '">' . $i . '</a></li>';
        }
    }
    
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, $totalPages) . '">' . $totalPages . '</a></li>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, $currentPage + 1) . '">&raquo;</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><a class="page-link" href="#">&raquo;</a></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Upload image file
 */
function uploadImage($file, $targetDir = UPLOAD_DIR) {
    // Check if directory exists, create if not
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $targetFile = $targetDir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    // Check if image file is a actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ["success" => false, "message" => "File is not an image."];
    }
    
    // Check file size (limit to 5MB)
    if ($file["size"] > 5000000) {
        return ["success" => false, "message" => "File is too large. Maximum size is 5MB."];
    }
    
    // Allow certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
        return ["success" => false, "message" => "Only JPG, JPEG, PNG files are allowed."];
    }
    
    // Generate unique filename
    $newFilename = uniqid() . '.' . $imageFileType;
    $targetFile = $targetDir . $newFilename;
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return ["success" => true, "filename" => $newFilename, "path" => $targetFile];
    } else {
        return ["success" => false, "message" => "There was an error uploading your file."];
    }
}

/**
 * Check if a borrowing is overdue
 */
function isOverdue($dueDate) {
    return strtotime($dueDate) < strtotime(date('Y-m-d'));
}

/**
 * Update overdue borrowings
 */
function updateOverdueBorrowings() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE borrowings 
                              SET status = 'overdue' 
                              WHERE status = 'borrowed' 
                              AND due_date < CURDATE()");
        $stmt->execute();
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Error updating overdue borrowings: " . $e->getMessage());
        return false;
    }
}

/**
 * Get total counts for dashboard
 */
function getDashboardCounts() {
    global $pdo;
    
    try {
        $counts = [];
        
        // Total users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE p_role = 'user'");
        $counts['users'] = $stmt->fetch()['count'];
        
        // Total books
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM books");
        $counts['books'] = $stmt->fetch()['count'];
        
        // Total borrowings
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM borrowings");
        $counts['borrowings'] = $stmt->fetch()['count'];
        
        // Active borrowings
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM borrowings WHERE status = 'borrowed'");
        $counts['active_borrowings'] = $stmt->fetch()['count'];
        
        // Overdue borrowings
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM borrowings WHERE status = 'overdue'");
        $counts['overdue_borrowings'] = $stmt->fetch()['count'];
        
        // Pending registrations
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'inactive' AND p_role = 'user'");
        $counts['pending_registrations'] = $stmt->fetch()['count'];
        
        return $counts;
    } catch (PDOException $e) {
        error_log("Error getting dashboard counts: " . $e->getMessage());
        return [
            'users' => 0,
            'books' => 0,
            'borrowings' => 0,
            'active_borrowings' => 0,
            'overdue_borrowings' => 0,
            'pending_registrations' => 0
        ];
    }
}

/**
 * Get admin dashboard data
 */
function getAdminDashboardData() {
    global $pdo;
    
    try {
        $data = [];
        
        // User counts by p_role
        $stmt = $pdo->query("SELECT p_role, COUNT(*) as count FROM users GROUP BY p_role");
        $p_roleCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $data['total_users'] = array_sum($p_roleCounts);
        $data['admin_count'] = $p_roleCounts['admin'] ?? 0;
        $data['staff_count'] = $p_roleCounts['staff'] ?? 0;
        $data['member_count'] = $p_roleCounts['member'] ?? 0;
        
        // Book statistics
        $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(available_stock) as available FROM books");
        $bookStats = $stmt->fetch();
        $data['total_books'] = $bookStats['total'];
        $data['available_books'] = $bookStats['available'];
        
        // Borrowing statistics
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM borrowings WHERE status = 'borrowed'");
        $data['active_borrowings'] = $stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM borrowings WHERE status = 'overdue'");
        $data['overdue_borrowings'] = $stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM borrowings WHERE MONTH(borrow_date) = MONTH(CURRENT_DATE()) AND YEAR(borrow_date) = YEAR(CURRENT_DATE())");
        $data['monthly_borrowings'] = $stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'inactive' AND p_role = 'member'");
        $data['pending_registrations'] = $stmt->fetch()['count'];
        
        // Recent activities
        $stmt = $pdo->prepare("SELECT l.*, u.username FROM logs l 
                              LEFT JOIN users u ON l.user_id = u.user_id 
                              ORDER BY l.created_at DESC LIMIT 10");
        $stmt->execute();
        $data['recent_activities'] = $stmt->fetchAll();
        
        return $data;
    } catch (PDOException $e) {
        error_log("Error getting admin dashboard data: " . $e->getMessage());
        return [];
    }
}

/**
 * Get staff dashboard data
 */
function getStaffDashboardData() {
    global $pdo;
    
    try {
        $data = [];
        
        // Today's statistics
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM borrowings WHERE DATE(created_at) = CURDATE() AND status = 'borrowed'");
        $data['today_borrowings'] = $stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM borrowings WHERE DATE(updated_at) = CURDATE() AND status = 'returned'");
        $data['today_returns'] = $stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM borrowings WHERE DATE(due_date) = CURDATE() AND status = 'borrowed'");
        $data['due_today'] = $stmt->fetch()['count'];
        
        // Recent transactions
        $stmt = $pdo->prepare("SELECT b.*, bk.title as book_title, a.name as member_name 
                              FROM borrowings b
                              JOIN books bk ON b.book_id = bk.book_id
                              JOIN anggota a ON b.anggota_id = a.anggota_id
                              ORDER BY b.created_at DESC LIMIT 5");
        $stmt->execute();
        $data['recent_transactions'] = $stmt->fetchAll();
        
        return $data;
    } catch (PDOException $e) {
        error_log("Error getting staff dashboard data: " . $e->getMessage());
        return [];
    }
}

/**
 * Get books for member view
 */
function getMemberBooks($search = '') {
    global $pdo;
    
    try {
        $query = "SELECT * FROM books WHERE available_stock > 0";
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND (title LIKE ? OR author LIKE ? OR category LIKE ?)";
            $searchParam = "%$search%";
            $params = [$searchParam, $searchParam, $searchParam];
        }
        
        $query .= " ORDER BY title";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting member books: " . $e->getMessage());
        return [];
    }
}

/**
 * Format date and time
 */
function formatDateTime($datetime) {
    return date('d M Y H:i', strtotime($datetime));
}

/**
 * Check and update overdue status and fines
 */
function checkAndUpdateFines() {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // First update overdue status
        $stmt = $pdo->prepare("
            UPDATE borrowings 
            SET status = 'overdue'
            WHERE status = 'borrowed' 
            AND due_date < CURRENT_DATE 
            AND return_date IS NULL
        ");
        $stmt->execute();
        
        // Then update or create fines
        $stmt = $pdo->prepare("
            INSERT INTO fines (borrowing_id, anggota_id, amount, days_overdue)
            SELECT 
                b.borrowing_id,
                b.anggota_id,
                DATEDIFF(CURRENT_DATE, b.due_date) * ?,
                DATEDIFF(CURRENT_DATE, b.due_date)
            FROM borrowings b
            LEFT JOIN fines f ON b.borrowing_id = f.borrowing_id
            WHERE b.status = 'overdue' 
            AND f.fine_id IS NULL
        ");
        $stmt->execute([FINE_PER_DAY]);
        
        // Update existing fines
        $stmt = $pdo->prepare("
            UPDATE fines f
            JOIN borrowings b ON f.borrowing_id = b.borrowing_id
            SET 
                f.amount = DATEDIFF(CURRENT_DATE, b.due_date) * ?,
                f.days_overdue = DATEDIFF(CURRENT_DATE, b.due_date)
            WHERE b.status = 'overdue'
            AND f.status = 'unpaid'
        ");
        $stmt->execute([FINE_PER_DAY]);
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error updating fines: " . $e->getMessage());
        return false;
    }
}

/**
 * Update fines for overdue books
 */
function updateFines() {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get fine amount per day
        $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'fine_per_day'");
        $finePerDay = $stmt->fetchColumn() ?: 1000;
        
        // Update overdue status first
        $stmt = $pdo->prepare("
            UPDATE borrowings 
            SET status = 'overdue'
            WHERE status = 'borrowed' 
            AND due_date < CURRENT_DATE 
            AND return_date IS NULL
        ");
        $stmt->execute();
        
        // Create new fines for overdue books that don't have fines yet
        $stmt = $pdo->prepare("
            INSERT INTO fines (borrowing_id, anggota_id, amount, days_overdue)
            SELECT 
                b.borrowing_id,
                b.anggota_id,
                DATEDIFF(CURRENT_DATE, b.due_date) * ?,
                DATEDIFF(CURRENT_DATE, b.due_date)
            FROM borrowings b
            LEFT JOIN fines f ON b.borrowing_id = f.borrowing_id
            WHERE b.status = 'overdue' 
            AND f.fine_id IS NULL
        ");
        $stmt->execute([$finePerDay]);
        
        // Update amounts for existing unpaid fines
        $stmt = $pdo->prepare("
            UPDATE fines f
            JOIN borrowings b ON f.borrowing_id = b.borrowing_id
            SET 
                f.amount = DATEDIFF(CURRENT_DATE, b.due_date) * ?,
                f.days_overdue = DATEDIFF(CURRENT_DATE, b.due_date)
            WHERE b.status = 'overdue'
            AND f.status = 'unpaid'
        ");
        $stmt->execute([$finePerDay]);
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error updating fines: " . $e->getMessage());
        return false;
    }
}

/**
 * Get navigation menu for the user
 */
// function getNavigationMenu() {
//     $p_role = $_SESSION['p_role'] ?? '';
//     $menu = [];
    
//     switch ($p_role) {
//         case p_role_ADMIN:
//             $menu = [
//                 ['url' => 'dashboard', 'icon' => 'speedometer2', 'text' => 'Dashboard'],
//                 ['url' => 'users', 'icon' => 'people', 'text' => 'User Management'],
//                 ['url' => 'books', 'icon' => 'book', 'text' => 'Books'],
//                 ['url' => 'borrowings', 'icon' => 'bookmark', 'text' => 'Borrowings'],
//                 ['url' => 'fines', 'icon' => 'cash-stack', 'text' => 'Fines'],
//                 ['url' => 'statistics', 'icon' => 'bar-chart', 'text' => 'Statistics'],
//                 ['url' => 'profile', 'icon' => 'person-circle', 'text' => 'Profile']
//             ];
//             break;
            
//         case p_role_STAFF:
//             $menu = [
//                 ['url' => 'dashboard', 'icon' => 'speedometer2', 'text' => 'Dashboard'],
//                 ['url' => 'members', 'icon' => 'person-plus', 'text' => 'Add Members'],
//                 ['url' => 'books', 'icon' => 'book', 'text' => 'View Books'],
//                 ['url' => 'borrowings', 'icon' => 'bookmark', 'text' => 'Borrowings'],
//                 ['url' => 'fines', 'icon' => 'cash-stack', 'text' => 'Fines'],
//                 ['url' => 'profile', 'icon' => 'person-circle', 'text' => 'Profile']
//             ];
//             break;
            
//         case p_role_MEMBER:
//             $menu = [
//                 ['url' => 'books', 'icon' => 'book', 'text' => 'Browse Books'],
//                 ['url' => 'my-borrowings', 'icon' => 'bookmark', 'text' => 'My Borrowings'],
//                 ['url' => 'my-fines', 'icon' => 'cash', 'text' => 'My Fines'],
//                 ['url' => 'profile', 'icon' => 'person-circle', 'text' => 'My Profile']
//             ];
//             break;
//     }
    
//     return $menu;
// }