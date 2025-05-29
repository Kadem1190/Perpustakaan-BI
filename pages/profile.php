<?php
$pageTitle = 'My Profile';
$p_role = $_SESSION['p_role'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;

// Get user profile data
try {
    $stmt = $pdo->prepare("SELECT u.*, a.* FROM users u 
                          JOIN anggota a ON u.anggota_id = a.anggota_id 
                          WHERE u.user_id = ?");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch();
    
    if (!$profile) {
        $errorMessage = "Profile not found.";
    }
} catch (PDOException $e) {
    $errorMessage = "Error retrieving profile: " . $e->getMessage();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_profile') {
        $name = sanitizeInput($_POST['name']);
        $email = sanitizeInput($_POST['email']);
        $phone = sanitizeInput($_POST['phone']);
        $address = sanitizeInput($_POST['address']);
        
        try {
            // Update anggota table
            $stmt = $pdo->prepare("UPDATE anggota SET name = ?, email = ?, phone = ?, address = ? WHERE anggota_id = ?");
            $stmt->execute([$name, $email, $phone, $address, $profile['anggota_id']]);
            
            // Update users table
            $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
            $stmt->execute([$name, $userId]);
            
            logActivity('Profile updated', $userId);
            
            $successMessage = "Profile updated successfully.";
            
            // Refresh profile data
            $stmt = $pdo->prepare("SELECT u.*, a.* FROM users u 
                                  JOIN anggota a ON u.anggota_id = a.anggota_id 
                                  WHERE u.user_id = ?");
            $stmt->execute([$userId]);
            $profile = $stmt->fetch();
            
        } catch (PDOException $e) {
            $errorMessage = "Error updating profile: " . $e->getMessage();
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errorMessage = "All password fields are required.";
        } elseif ($newPassword !== $confirmPassword) {
            $errorMessage = "New passwords do not match.";
        } elseif (strlen($newPassword) < 6) {
            $errorMessage = "New password must be at least 6 characters long.";
        } else {
            // Verify current password
            if (verifyPassword($currentPassword, $profile['password'])) {
                try {
                    $hashedPassword = hashPassword($newPassword);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                    $stmt->execute([$hashedPassword, $userId]);
                    
                    logActivity('Password changed', $userId);
                    
                    $successMessage = "Password changed successfully.";
                } catch (PDOException $e) {
                    $errorMessage = "Error changing password: " . $e->getMessage();
                }
            } else {
                $errorMessage = "Current password is incorrect.";
            }
        }
    }
}

// Get borrowing statistics for member
if ($p_role === 'member') {
    try {
        $stmt = $pdo->prepare("SELECT 
                              COUNT(*) as total_borrowings,
                              SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) as active_borrowings,
                              SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as returned_books,
                              SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_books
                              FROM borrowings WHERE anggota_id = ?");
        $stmt->execute([$profile['anggota_id']]);
        $borrowingStats = $stmt->fetch();
    } catch (PDOException $e) {
        $borrowingStats = ['total_borrowings' => 0, 'active_borrowings' => 0, 'returned_books' => 0, 'overdue_books' => 0];
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">My Profile</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <span class="badge bg-primary"><?php echo ucfirst($p_role); ?></span>
        </div>
    </div>
</div>

<?php if (isset($successMessage)): ?>
    <div class="alert alert-success"><?php echo $successMessage; ?></div>
<?php endif; ?>

<?php if (isset($errorMessage)): ?>
    <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
<?php endif; ?>

<?php if (isset($profile)): ?>
<div class="row">
    <!-- Profile Information -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Profile Information</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($profile['name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="nim" class="form-label">NIM</label>
                            <input type="text" class="form-control" id="nim" value="<?php echo htmlspecialchars($profile['nim']); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="class" class="form-label">Class</label>
                            <input type="text" class="form-control" id="class" value="<?php echo htmlspecialchars($profile['class']); ?>" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <input type="text" class="form-control" id="gender" value="<?php echo htmlspecialchars($profile['gender']); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
        
        <!-- Change Password -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Change Password</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="form-text">Password must be at least 6 characters long.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-warning">Change Password</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Profile Summary -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Account Summary</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th>Username:</th>
                        <td><?php echo htmlspecialchars($profile['username']); ?></td>
                    </tr>
                    <tr>
                        <th>p_role:</th>
                        <td><span class="badge bg-primary"><?php echo ucfirst($profile['p_role']); ?></span></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <span class="badge bg-<?php echo $profile['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($profile['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Member Since:</th>
                        <td><?php echo formatDate($profile['created_at']); ?></td>
                    </tr>
                    <?php if ($profile['last_login']): ?>
                    <tr>
                        <th>Last Login:</th>
                        <td><?php echo formatDateTime($profile['last_login']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
        <?php if ($p_role === 'member' && isset($borrowingStats)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Borrowing Statistics</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="border rounded p-2">
                            <h4 class="text-primary"><?php echo $borrowingStats['total_borrowings']; ?></h4>
                            <small class="text-muted">Total Borrowings</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="border rounded p-2">
                            <h4 class="text-info"><?php echo $borrowingStats['active_borrowings']; ?></h4>
                            <small class="text-muted">Active</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <h4 class="text-success"><?php echo $borrowingStats['returned_books']; ?></h4>
                            <small class="text-muted">Returned</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <h4 class="text-danger"><?php echo $borrowingStats['overdue_books']; ?></h4>
                            <small class="text-muted">Overdue</small>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="index.php?page=my-borrowings" class="btn btn-sm btn-outline-primary w-100">View My Borrowings</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="alert alert-danger">Unable to load profile information.</div>
<?php endif; ?>
