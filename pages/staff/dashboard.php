<?php
requirep_role(p_role_STAFF);
$pageTitle = 'Staff Dashboard';

// Get staff dashboard data
$dashboardData = getStaffDashboardData();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Staff Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <span class="badge bg-success">Staff Panel</span>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card dashboard-card h-100 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Today's Borrowings</h6>
                        <h2 class="card-text"><?php echo $dashboardData['today_borrowings']; ?></h2>
                    </div>
                    <div class="dashboard-icon text-primary">
                        <i class="bi bi-bookmark-plus"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card dashboard-card h-100 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Today's Returns</h6>
                        <h2 class="card-text"><?php echo $dashboardData['today_returns']; ?></h2>
                    </div>
                    <div class="dashboard-icon text-success">
                        <i class="bi bi-bookmark-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card dashboard-card h-100 border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Due Today</h6>
                        <h2 class="card-text"><?php echo $dashboardData['due_today']; ?></h2>
                    </div>
                    <div class="dashboard-icon text-warning">
                        <i class="bi bi-clock"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions and Recent Transactions -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="index.php?page=members&action=add" class="btn btn-primary">
                        <i class="bi bi-person-plus me-2"></i>Add New Member
                    </a>
                    <a href="index.php?page=borrowings&action=add" class="btn btn-success">
                        <i class="bi bi-bookmark-plus me-2"></i>New Borrowing
                    </a>
                    <a href="index.php?page=borrowings&filter=due" class="btn btn-warning">
                        <i class="bi bi-clock me-2"></i>Check Due Books
                    </a>
                    <a href="index.php?page=books" class="btn btn-info">
                        <i class="bi bi-book me-2"></i>Browse Books
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Transactions</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($dashboardData['recent_transactions'])): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($dashboardData['recent_transactions'] as $transaction): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($transaction['book_title']); ?></h6>
                                    <small><?php echo formatDate($transaction['borrow_date']); ?></small>
                                </div>
                                <p class="mb-1"><?php echo htmlspecialchars($transaction['member_name']); ?></p>
                                <small class="text-muted">
                                    Status: <span class="badge bg-<?php echo $transaction['status'] == 'borrowed' ? 'info' : 'success'; ?>">
                                        <?php echo ucfirst($transaction['status']); ?>
                                    </span>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No recent transactions found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
