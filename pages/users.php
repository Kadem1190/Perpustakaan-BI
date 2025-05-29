<?php
$pageTitle = 'User Management';

// Handle Edit User POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user_id'])) {
    $editUserId = intval($_POST['edit_user_id']);
    $editName = trim($_POST['edit_name']);
    $editUsername = trim($_POST['edit_username']);
    $editStatus = $_POST['edit_status'];

    try {
        // Update both users and anggota tables
        $stmt = $pdo->prepare("UPDATE users u JOIN anggota a ON u.anggota_id = a.anggota_id
            SET a.name = ?, u.username = ?, u.status = ?
            WHERE u.user_id = ?");
        $stmt->execute([$editName, $editUsername, $editStatus, $editUserId]);
        logActivity('User edited', $_SESSION['admin_id'], 'users', 'UPDATE', $editUserId);
        $successMessage = "User updated successfully.";
    } catch (PDOException $e) {
        $errorMessage = "Error updating user: " . $e->getMessage();
    }
}

// Process actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $userId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($action === 'approve' && $userId > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = 'active', registration_status = 'approved' WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            logActivity('User approved', $_SESSION['admin_id'], 'users', 'UPDATE', $userId);
            
            $successMessage = "User has been approved successfully.";
        } catch (PDOException $e) {
            $errorMessage = "Error approving user: " . $e->getMessage();
        }
    } elseif ($action === 'ban' && $userId > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = 'banned' WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            logActivity('User banned', $_SESSION['admin_id'], 'users', 'UPDATE', $userId);
            
            $successMessage = "User has been banned successfully.";
        } catch (PDOException $e) {
            $errorMessage = "Error banning user: " . $e->getMessage();
        }
    } elseif ($action === 'activate' && $userId > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            logActivity('User activated', $_SESSION['admin_id'], 'users', 'UPDATE', $userId);
            
            $successMessage = "User has been activated successfully.";
        } catch (PDOException $e) {
            $errorMessage = "Error activating user: " . $e->getMessage();
        }
    } elseif ($action === 'deactivate' && $userId > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            logActivity('User deactivated', $_SESSION['admin_id'], 'users', 'UPDATE', $userId);
            
            $successMessage = "User has been deactivated successfully.";
        } catch (PDOException $e) {
            $errorMessage = "Error deactivating user: " . $e->getMessage();
        }
    } elseif ($action === 'view' && $userId > 0) {
        // Get user details
        try {
            $stmt = $pdo->prepare("SELECT u.*, a.* FROM users u 
                                  JOIN anggota a ON u.anggota_id = a.anggota_id 
                                  WHERE u.user_id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $errorMessage = "User not found.";
            }
        } catch (PDOException $e) {
            $errorMessage = "Error retrieving user details: " . $e->getMessage();
        }
    }
}

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['p']) ? intval($_GET['p']) : 1;
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Build query based on filters
$query = "SELECT u.*, a.name, a.nim FROM users u JOIN anggota a ON u.anggota_id = a.anggota_id WHERE u.p_role = 'user'";
$countQuery = "SELECT COUNT(*) as total FROM users u JOIN anggota a ON u.anggota_id = a.anggota_id WHERE u.p_role = 'user'";
$params = [];

if ($filter === 'pending') {
    $query .= " AND u.status = 'inactive'";
    $countQuery .= " AND u.status = 'inactive'";
} elseif ($filter === 'active') {
    $query .= " AND u.status = 'active'";
    $countQuery .= " AND u.status = 'active'";
} elseif ($filter === 'inactive') {
    $query .= " AND u.status = 'inactive'";
    $countQuery .= " AND u.status = 'inactive'";
} elseif ($filter === 'banned') {
    $query .= " AND u.status = 'banned'";
    $countQuery .= " AND u.status = 'banned'";
}

if (!empty($search)) {
    $query .= " AND (a.name LIKE ? OR a.nim LIKE ? OR u.username LIKE ?)";
    $countQuery .= " AND (a.name LIKE ? OR a.nim LIKE ? OR u.username LIKE ?)";
    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam];
}

// Get total count
try {
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalItems = $stmt->fetch()['total'];
    $totalPages = ceil($totalItems / $limit);
} catch (PDOException $e) {
    $errorMessage = "Error counting users: " . $e->getMessage();
}

// Get users with pagination
$query .= " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMessage = "Error retrieving users: " . $e->getMessage();
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">User Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="index.php?page=users" class="btn btn-sm btn-outline-secondary <?php echo empty($filter) ? 'active' : ''; ?>">All Users</a>
            <a href="index.php?page=users&filter=pending" class="btn btn-sm btn-outline-warning <?php echo $filter === 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="index.php?page=users&filter=active" class="btn btn-sm btn-outline-success <?php echo $filter === 'active' ? 'active' : ''; ?>">Active</a>
            <a href="index.php?page=users&filter=inactive" class="btn btn-sm btn-outline-secondary <?php echo $filter === 'inactive' ? 'active' : ''; ?>">Inactive</a>
            <a href="index.php?page=users&filter=banned" class="btn btn-sm btn-outline-danger <?php echo $filter === 'banned' ? 'active' : ''; ?>">Banned</a>
        </div>
    </div>
</div>

<?php if (isset($successMessage)): ?>
    <div class="alert alert-success"><?php echo $successMessage; ?></div>
<?php endif; ?>

<?php if (isset($errorMessage)): ?>
    <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
<?php endif; ?>

<?php if (isset($user)): ?>
<!-- User Details Modal -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0">User Details</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted">Personal Information</h6>
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Name</th>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                    </tr>
                    <tr>
                        <th>NIM</th>
                        <td><?php echo htmlspecialchars($user['nim']); ?></td>
                    </tr>
                    <tr>
                        <th>Class</th>
                        <td><?php echo htmlspecialchars($user['class']); ?></td>
                    </tr>
                    <tr>
                        <th>Gender</th>
                        <td><?php echo htmlspecialchars($user['gender']); ?></td>
                    </tr>
                    <tr>
                        <th>Date of Birth</th>
                        <td><?php echo formatDate($user['date_of_birth']); ?></td>
                    </tr>
                    <tr>
                        <th>Place of Birth</th>
                        <td><?php echo htmlspecialchars($user['place_of_birth'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Address</th>
                        <td><?php echo htmlspecialchars($user['address'] ?? 'N/A'); ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted">Account Information</h6>
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Username</th>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="badge bg-<?php 
                                echo $user['status'] == 'active' ? 'success' : 
                                    ($user['status'] == 'inactive' ? 'secondary' : 'danger'); 
                            ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Registration</th>
                        <td>
                            <span class="badge bg-<?php 
                                echo $user['registration_status'] == 'approved' ? 'success' : 'warning'; 
                            ?>">
                                <?php echo ucfirst($user['registration_status']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Created At</th>
                        <td><?php echo formatDate($user['created_at']); ?></td>
                    </tr>
                </table>
                
                <h6 class="text-muted mt-4">Actions</h6>
                <div class="btn-group">
                    <?php if ($user['registration_status'] === 'pending'): ?>
                        <a href="index.php?page=users&action=approve&id=<?php echo $user['user_id']; ?>" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i> Approve
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($user['status'] === 'active'): ?>
                        <a href="index.php?page=users&action=deactivate&id=<?php echo $user['user_id']; ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i> Deactivate
                        </a>
                    <?php elseif ($user['status'] === 'inactive' || $user['status'] === 'banned'): ?>
                        <a href="index.php?page=users&action=activate&id=<?php echo $user['user_id']; ?>" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i> Activate
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($user['status'] !== 'banned'): ?>
                        <a href="index.php?page=users&action=ban&id=<?php echo $user['user_id']; ?>" class="btn btn-danger">
                            <i class="bi bi-ban me-1"></i> Ban
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <a href="index.php?page=users<?php echo !empty($filter) ? '&filter=' . $filter : ''; ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left me-1"></i> Back to Users
        </a>
    </div>
</div>
<?php else: ?>
<!-- Search Form -->
<div class="card mb-4">
    <div class="card-body">
        <form action="index.php" method="get" class="row g-3">
            <input type="hidden" name="page" value="users">
            <?php if (!empty($filter)): ?>
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
            <?php endif; ?>
            
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search" placeholder="Search by name, NIM, or username" value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="index.php?page=users<?php echo !empty($filter) ? '&filter=' . $filter : ''; ?>" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">
            <?php
            if ($filter === 'pending') {
                echo 'Pending Registrations';
            } elseif ($filter === 'active') {
                echo 'Active Users';
            } elseif ($filter === 'inactive') {
                echo 'Inactive Users';
            } elseif ($filter === 'banned') {
                echo 'Banned Users';
            } else {
                echo 'All Users';
            }
            ?>
            <span class="badge bg-secondary ms-2"><?php echo $totalItems; ?></span>
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
            <div class="p-4 text-center text-muted">No users found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>NIM</th>
                            <th>Username</th>
                            <th>Status</th>
                            <th>Registration</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['nim']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $user['status'] == 'active' ? 'success' : 
                                            ($user['status'] == 'inactive' ? 'secondary' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $user['registration_status'] == 'approved' ? 'success' : 'warning'; 
                                    ?>">
                                        <?php echo ucfirst($user['registration_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($user['created_at']); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="index.php?page=users&action=view&id=<?php echo $user['user_id']; ?>" class="btn btn-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <!-- Edit Button -->
                                        <button type="button" class="btn btn-warning" title="Edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editUserModal"
                                            data-user='<?php echo json_encode([
                                                "user_id" => $user["user_id"],
                                                "name" => $user["name"],
                                                "username" => $user["username"],
                                                "status" => $user["status"]
                                            ]); ?>'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php if ($user['registration_status'] === 'pending'): ?>
                                            <a href="index.php?page=users&action=approve&id=<?php echo $user['user_id']; ?>" class="btn btn-success" title="Approve">
                                                <i class="bi bi-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($user['status'] === 'active'): ?>
                                            <a href="index.php?page=users&action=deactivate&id=<?php echo $user['user_id']; ?>" class="btn btn-secondary" title="Deactivate">
                                                <i class="bi bi-x"></i>
                                            </a>
                                        <?php elseif ($user['status'] === 'inactive' || $user['status'] === 'banned'): ?>
                                            <a href="index.php?page=users&action=activate&id=<?php echo $user['user_id']; ?>" class="btn btn-success" title="Activate">
                                                <i class="bi bi-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($user['status'] !== 'banned'): ?>
                                            <a href="index.php?page=users&action=ban&id=<?php echo $user['user_id']; ?>" class="btn btn-danger" title="Ban">
                                                <i class="bi bi-ban"></i>
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
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white">
            <?php
            $urlPattern = "index.php?page=users" . 
                (!empty($filter) ? "&filter=$filter" : "") . 
                (!empty($search) ? "&search=$search" : "") . 
                "&p=%d";
            echo generatePagination($page, $totalPages, $urlPattern);
            ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" id="editUserForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="edit_user_id" id="edit_user_id">
          <div class="mb-3">
            <label for="edit_name" class="form-label">Name</label>
            <input type="text" class="form-control" name="edit_name" id="edit_name" required>
          </div>
          <div class="mb-3">
            <label for="edit_username" class="form-label">Username</label>
            <input type="text" class="form-control" name="edit_username" id="edit_username" required>
          </div>
          <div class="mb-3">
            <label for="edit_status" class="form-label">Status</label>
            <select class="form-select" name="edit_status" id="edit_status">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="banned">Banned</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var editUserModal = document.getElementById('editUserModal');
  editUserModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var user = JSON.parse(button.getAttribute('data-user'));
    document.getElementById('edit_user_id').value = user.user_id;
    document.getElementById('edit_name').value = user.name;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_status').value = user.status;
  });
});
</script>
