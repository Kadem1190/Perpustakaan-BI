<?php
$pageTitle = 'Dashboard';
$p_role = $_SESSION['p_role'] ?? '';
$username = $_SESSION['username'] ?? '';

// Update overdue borrowings
updateOverdueBorrowings();

// Get dashboard counts
$counts = getDashboardCounts();

// Get recent borrowings
try {
    $stmt = $pdo->prepare("SELECT b.*, a.name as anggota_name, bk.title as book_title 
                          FROM borrowings b
                          JOIN anggota a ON b.anggota_id = a.anggota_id
                          JOIN books bk ON b.book_id = bk.book_id
                          ORDER BY b.borrow_date DESC
                          LIMIT 5");
    $stmt->execute();
    $recentBorrowings = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Get pending registrations
try {
    $stmt = $pdo->prepare("SELECT u.*, a.name, a.nim 
                          FROM users u
                          JOIN anggota a ON u.anggota_id = a.anggota_id
                          WHERE u.status = 'inactive' AND u.p_role = 'user'
                          ORDER BY u.created_at DESC
                          LIMIT 5");
    $stmt->execute();
    $pendingRegistrations = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $pendingRegistrations = [];
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <span class="badge bg-primary"><?php echo ucfirst($p_role); ?> Panel</span>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Welcome, <?php echo htmlspecialchars($username); ?>!</h5>
                <p class="card-text">You are logged in as: <strong><?php echo ucfirst($p_role); ?></strong></p>
                
                <?php if ($p_role === 'admin'): ?>
                    <p>As an admin, you have full access to the system.</p>
                    <div class="row">
                        <div class="col-md-3">
                            <a href="index.php?page=users" class="btn btn-primary">Manage Users</a>
                        </div>
                        <div class="col-md-3">
                            <a href="index.php?page=books" class="btn btn-success">Manage Books</a>
                        </div>
                        <div class="col-md-3">
                            <a href="index.php?page=borrowings" class="btn btn-info">View Borrowings</a>
                        </div>
                        <div class="col-md-3">
                            <a href="index.php?page=statistics" class="btn btn-warning">View Statistics</a>
                        </div>
                    </div>
                <?php elseif ($p_role === 'staff'): ?>
                    <p>As staff, you can manage members and borrowing transactions.</p>
                    <div class="row">
                        <div class="col-md-4">
                            <a href="index.php?page=members" class="btn btn-primary">Add Members</a>
                        </div>
                        <div class="col-md-4">
                            <a href="index.php?page=borrowings" class="btn btn-success">Manage Borrowings</a>
                        </div>
                        <div class="col-md-4">
                            <a href="index.php?page=books" class="btn btn-info">View Books</a>
                        </div>
                    </div>
                <?php elseif ($p_role === 'member'): ?>
                    <p>Welcome! You can browse books and view your borrowing history.</p>
                    <div class="row">
                        <div class="col-md-6">
                            <a href="index.php?page=books" class="btn btn-primary">Browse Books</a>
                        </div>
                        <div class="col-md-6">
                            <a href="index.php?page=my-borrowings" class="btn btn-success">My Borrowings</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Stats Cards -->
<?php if ($p_role === 'admin'): ?>
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card dashboard-card h-100 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Total Users</h6>
                        <h2 class="card-text"><?php echo $counts['users']; ?></h2>
                    </div>
                    <div class="dashboard-icon text-primary">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="index.php?page=users" class="btn btn-sm btn-outline-primary">View All Users</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card dashboard-card h-100 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Total Books</h6>
                        <h2 class="card-text"><?php echo $counts['books']; ?></h2>
                    </div>
                    <div class="dashboard-icon text-success">
                        <i class="bi bi-book"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="index.php?page=books" class="btn btn-sm btn-outline-success">View All Books</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card dashboard-card h-100 border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Active Borrowings</h6>
                        <h2 class="card-text"><?php echo $counts['active_borrowings']; ?></h2>
                    </div>
                    <div class="dashboard-icon text-info">
                        <i class="bi bi-bookmark"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="index.php?page=borrowings" class="btn btn-sm btn-outline-info">View All Borrowings</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="card dashboard-card h-100 border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Pending Registrations</h6>
                        <h2 class="card-text"><?php echo $counts['pending_registrations']; ?></h2>
                    </div>
                    <div class="dashboard-icon text-warning">
                        <i class="bi bi-person-plus"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="index.php?page=users&filter=pending" class="btn btn-sm btn-outline-warning">View Pending Registrations</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-3">
        <div class="card dashboard-card h-100 border-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Overdue Borrowings</h6>
                        <h2 class="card-text"><?php echo $counts['overdue_borrowings']; ?></h2>
                    </div>
                    <div class="dashboard-icon text-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="index.php?page=borrowings&filter=overdue" class="btn btn-sm btn-outline-danger">View Overdue Borrowings</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <!-- Recent Borrowings -->
    <?php if ($p_role === 'admin' || $p_role === 'staff'): ?>
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Recent Borrowings</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentBorrowings)): ?>
                    <div class="p-3 text-center text-muted">No recent borrowings found.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Book</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBorrowings as $borrowing): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($borrowing['anggota_name']); ?></td>
                                        <td><?php echo htmlspecialchars($borrowing['book_title']); ?></td>
                                        <td><?php echo formatDate($borrowing['borrow_date']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $borrowing['status'] == 'borrowed' ? 'info' : 
                                                    ($borrowing['status'] == 'returned' ? 'success' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($borrowing['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white">
                <a href="index.php?page=borrowings" class="btn btn-sm btn-outline-primary">View All Borrowings</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Pending Registrations -->
    <?php if ($p_role === 'admin'): ?>
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Pending Registrations</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($pendingRegistrations)): ?>
                    <div class="p-3 text-center text-muted">No pending registrations found.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>NIM</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingRegistrations as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['nim']); ?></td>
                                        <td><?php echo formatDate($user['created_at']); ?></td>
                                        <td>
                                            <a href="index.php?page=users&action=approve&id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-success">
                                                <i class="bi bi-check"></i>
                                            </a>
                                            <a href="index.php?page=users&action=view&id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white">
                <a href="index.php?page=users&filter=pending" class="btn btn-sm btn-outline-primary">View All Pending Registrations</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
