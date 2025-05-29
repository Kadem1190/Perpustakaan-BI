<?php
requirep_role(p_role_ADMIN);
$pageTitle = 'Admin Dashboard';

// Update overdue borrowings
updateOverdueBorrowings();

// Get comprehensive dashboard data
$dashboardData = getAdminDashboardData();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Admin Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <span class="badge bg-primary">Admin Panel</span>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card h-100 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Total Users</h6>
                        <h2 class="card-text"><?php echo $dashboardData['total_users']; ?></h2>
                        <small class="text-muted">
                            Admin: <?php echo $dashboardData['admin_count']; ?> | 
                            Staff: <?php echo $dashboardData['staff_count']; ?> | 
                            Members: <?php echo $dashboardData['member_count']; ?>
                        </small>
                    </div>
                    <div class="dashboard-icon text-primary">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card h-100 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Books</h6>
                        <h2 class="card-text"><?php echo $dashboardData['total_books']; ?></h2>
                        <small class="text-muted">Available: <?php echo $dashboardData['available_books']; ?></small>
                    </div>
                    <div class="dashboard-icon text-success">
                        <i class="bi bi-book"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card h-100 border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Active Borrowings</h6>
                        <h2 class="card-text"><?php echo $dashboardData['active_borrowings']; ?></h2>
                        <small class="text-muted">This month: <?php echo $dashboardData['monthly_borrowings']; ?></small>
                    </div>
                    <div class="dashboard-icon text-info">
                        <i class="bi bi-bookmark"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card h-100 border-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Overdue Books</h6>
                        <h2 class="card-text"><?php echo $dashboardData['overdue_borrowings']; ?></h2>
                        <small class="text-muted">Pending: <?php echo $dashboardData['pending_registrations']; ?></small>
                    </div>
                    <div class="dashboard-icon text-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities and Quick Actions -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Activities</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($dashboardData['recent_activities'])): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($dashboardData['recent_activities'] as $activity): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold"><?php echo htmlspecialchars($activity['action']); ?></div>
                                    <small class="text-muted">
                                        by <?php echo htmlspecialchars($activity['username']); ?> - 
                                        <?php echo formatDateTime($activity['created_at']); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No recent activities found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="index.php?page=users&action=add" class="btn btn-primary">
                        <i class="bi bi-person-plus me-2"></i>Add New User
                    </a>
                    <a href="index.php?page=books&action=add" class="btn btn-success">
                        <i class="bi bi-book me-2"></i>Add New Book
                    </a>
                    <a href="index.php?page=borrowings" class="btn btn-info">
                        <i class="bi bi-bookmark me-2"></i>View All Borrowings
                    </a>
                    <a href="index.php?page=statistics" class="btn btn-warning">
                        <i class="bi bi-bar-chart me-2"></i>View Statistics
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
