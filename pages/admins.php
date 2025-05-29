<?php
$pageTitle = 'Admin Users';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Add new admin
        if ($action === 'add') {
            $username = sanitizeInput($_POST['username']);
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'];
            $fullName = sanitizeInput($_POST['full_name']);
            
            if (empty($username) || empty($password) || empty($fullName)) {
                $errorMessage = "All fields are required.";
            } elseif ($password !== $confirmPassword) {
                $errorMessage = "Passwords do not match.";
            } else {
                try {
                    // Check if username already exists
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    $usernameExists = $stmt->fetch()['count'] > 0;
                    
                    if ($usernameExists) {
                        $errorMessage = "Username already exists.";
                    } else {
                        // Hash password
                        $passwordHash = password($password, PASSWORD_DEFAULT);
                        
                        // Create anggota record first (required for foreign key)
                        $stmt = $pdo->prepare("INSERT INTO anggota (name, nim, class, gender, date_of_birth) 
                                              VALUES (?, 'ADMIN', 'ADMIN', 'Male', CURDATE())");
                        $stmt->execute([$fullName]);
                        $anggotaId = $pdo->lastInsertId();
                        
                        // Insert admin user
                        $stmt = $pdo->prepare("INSERT INTO users (anggota_id, username, password, full_name, p_role, status, registration_status, created_at) 
                                              VALUES (?, ?, ?, ?, 'admin', 'active', 'approved', NOW())");
                        $stmt->execute([$anggotaId, $username, $passwordHash, $fullName]);
                        
                        $adminId = $pdo->lastInsertId();
                        
                        logActivity('Admin user added', $_SESSION['admin_id'], 'users', 'INSERT', $adminId);
                        
                        $successMessage = "Admin user has been added successfully.";
                    }
                } catch (PDOException $e) {
                    $errorMessage = "Error adding admin user: " . $e->getMessage();
                }
            }
        }
        
        // Update admin
        elseif ($action === 'update') {
            $adminId = intval($_POST['admin_id']);
            $fullName = sanitizeInput($_POST['full_name']);
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'];
            
            if (empty($fullName)) {
                $errorMessage = "Full name is required.";
            } elseif (!empty($password) && $password !== $confirmPassword) {
                $errorMessage = "Passwords do not match.";
            } else {
                try {
                    // Update admin user
                    if (!empty($password)) {
                        // Update with new password
                        $passwordHash = password($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, password = ? WHERE user_id = ?");
                        $stmt->execute([$fullName, $passwordHash, $adminId]);
                    } else {
                        // Update without changing password
                        $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
                        $stmt->execute([$fullName, $adminId]);
                    }
                    
                    // Update anggota record
                    $stmt = $pdo->prepare("UPDATE anggota a JOIN users u ON a.anggota_id = u.anggota_id 
                                          SET a.name = ? WHERE u.user_id = ?");
                    $stmt->execute([$fullName, $adminId]);
                    
                    logActivity('Admin user updated', $_SESSION['admin_id'], 'users', 'UPDATE', $adminId);
                    
                    $successMessage = "Admin user has been updated successfully.";
                } catch (PDOException $e) {
                    $errorMessage = "Error updating admin user: " . $e->getMessage();
                }
            }
        }
        
        // Delete admin
        elseif ($action === 'delete') {
            $adminId = intval($_POST['admin_id']);
            
            // Prevent deleting own account
            if ($adminId === $_SESSION['admin_id']) {
                $errorMessage = "You cannot delete your own account.";
            } else {
                try {
                    // Get anggota_id first
                    $stmt = $pdo->prepare("SELECT anggota_id FROM users WHERE user_id = ?");
                    $stmt->execute([$adminId]);
                    $anggotaId = $stmt->fetch()['anggota_id'];
                    
                    // Delete user record
                    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->execute([$adminId]);
                    
                    // Delete anggota record
                    $stmt = $pdo->prepare("DELETE FROM anggota WHERE anggota_id = ?");
                    $stmt->execute([$anggotaId]);
                    
                    logActivity('Admin user deleted', $_SESSION['admin_id'], 'users', 'DELETE', $adminId);
                    
                    $successMessage = "Admin user has been deleted successfully.";
                } catch (PDOException $e) {
                    $errorMessage = "Error deleting admin user: " . $e->getMessage();
                }
            }
        }
    }
}

// Process GET actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $adminId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($action === 'edit' && $adminId > 0) {
        // Get admin details for editing
        try {
            $stmt = $pdo->prepare("SELECT u.*, a.name FROM users u 
                                  JOIN anggota a ON u.anggota_id = a.anggota_id 
                                  WHERE u.user_id = ? AND u.p_role = 'admin'");
            $stmt->execute([$adminId]);
            $admin = $stmt->fetch();
            
            if (!$admin) {
                $errorMessage = "Admin user not found.";
            }
        } catch (PDOException $e) {
            $errorMessage = "Error retrieving admin details: " . $e->getMessage();
        }
    } elseif ($action === 'delete' && $adminId > 0) {
        // Get admin details for confirmation
        try {
            $stmt = $pdo->prepare("SELECT u.*, a.name FROM users u 
                                  JOIN anggota a ON u.anggota_id = a.anggota_id 
                                  WHERE u.user_id = ? AND u.p_role = 'admin'");
            $stmt->execute([$adminId]);
            $admin = $stmt->fetch();
            
            if (!$admin) {
                $errorMessage = "Admin user not found.";
            }
        } catch (PDOException $e) {
            $errorMessage = "Error retrieving admin details: " . $e->getMessage();
        }
    }
}

// Get all admin users
try {
    $stmt = $pdo->prepare("SELECT u.*, a.name FROM users u 
                          JOIN anggota a ON u.anggota_id = a.anggota_id 
                          WHERE u.p_role = 'admin' 
                          ORDER BY u.created_at DESC");
    $stmt->execute();
    $admins = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMessage = "Error retrieving admin users: " . $e->getMessage();
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Admin Users</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
            <i class="bi bi-plus-circle me-1"></i> Add New Admin
        </button>
    </div>
</div>

<?php if (isset($successMessage)): ?>
    <div class="alert alert-success"><?php echo $successMessage; ?></div>
<?php endif; ?>

<?php if (isset($errorMessage)): ?>
    <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
<?php endif; ?>

<!-- Admin Users Table -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">
            Admin Users
            <span class="badge bg-secondary ms-2"><?php echo count($admins); ?></span>
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($admins)): ?>
            <div class="p-4 text-center text-muted">No admin users found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?php echo $admin['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                <td><?php echo htmlspecialchars($admin['name']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $admin['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($admin['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($admin['created_at']); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="index.php?page=admins&action=edit&id=<?php echo $admin['user_id']; ?>" class="btn btn-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($admin['user_id'] !== $_SESSION['admin_id']): ?>
                                            <a href="index.php?page=admins&action=delete&id=<?php echo $admin['user_id']; ?>" class="btn btn-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="index.php?page=admins" method="post">
                <input type="hidden" name="action" value="add">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="addAdminModalLabel">Add New Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#confirm_password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Admin Modal -->
<?php if (isset($admin) && isset($action) && $action === 'edit'): ?>
<div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="index.php?page=admins" method="post">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="admin_id" value="<?php echo $admin['user_id']; ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="editAdminModalLabel">Edit Admin</h5>
                    <a href="index.php?page=admins" class="btn-close" aria-label="Close"></a>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="edit_username" value="<?php echo htmlspecialchars($admin['username']); ?>" readonly>
                        <div class="form-text text-muted">Username cannot be changed.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name" value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="edit_password" name="password">
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#edit_password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text text-muted">Leave blank to keep current password.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_confirm_password" class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="edit_confirm_password" name="confirm_password">
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#edit_confirm_password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="index.php?page=admins" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var editAdminModal = new bootstrap.Modal(document.getElementById('editAdminModal'));
        editAdminModal.show();
    });
</script>
<?php endif; ?>

<!-- Delete Admin Modal -->
<?php if (isset($admin) && isset($action) && $action === 'delete'): ?>
<div class="modal fade" id="deleteAdminModal" tabindex="-1" aria-labelledby="deleteAdminModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="index.php?page=admins" method="post">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="admin_id" value="<?php echo $admin['user_id']; ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAdminModalLabel">Delete Admin</h5>
                    <a href="index.php?page=admins" class="btn-close" aria-label="Close"></a>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the following admin user?</p>
                    <div class="alert alert-info">
                        <strong>Username:</strong> <?php echo htmlspecialchars($admin['username']); ?><br>
                        <strong>Full Name:</strong> <?php echo htmlspecialchars($admin['name']); ?>
                    </div>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        This action cannot be undone. All activity logs associated with this admin will remain in the database.
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="index.php?page=admins" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-danger">Delete Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var deleteAdminModal = new bootstrap.Modal(document.getElementById('deleteAdminModal'));
        deleteAdminModal.show();
    });
</script>
<?php endif; ?>
