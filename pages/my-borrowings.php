<?php
requirep_role(p_role_MEMBER);
$pageTitle = 'My Borrowings';
$anggotaId = $_SESSION['anggota_id'] ?? 0;
updateFines();

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$page = isset($_GET['p']) ? intval($_GET['p']) : 1;
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Build query based on filters
$query = "SELECT b.*, bk.title as book_title, bk.author as book_author, bk.cover
          FROM borrowings b
          JOIN books bk ON b.book_id = bk.book_id
          WHERE b.anggota_id = ?";
$countQuery = "SELECT COUNT(*) as total FROM borrowings b WHERE b.anggota_id = ?";
$params = [$anggotaId];

if ($filter === 'active') {
    $query .= " AND b.status = 'borrowed'";
    $countQuery .= " AND b.status = 'borrowed'";
} elseif ($filter === 'returned') {
    $query .= " AND b.status = 'returned'";
    $countQuery .= " AND b.status = 'returned'";
} elseif ($filter === 'overdue') {
    $query .= " AND b.status = 'overdue'";
    $countQuery .= " AND b.status = 'overdue'";
}

// Get total count
try {
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalItems = $stmt->fetch()['total'];
    $totalPages = ceil($totalItems / $limit);
} catch (PDOException $e) {
    $errorMessage = "Error counting borrowings: " . $e->getMessage();
}

// Get borrowings with pagination
$query .= " ORDER BY b.borrow_date DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $borrowings = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMessage = "Error retrieving borrowings: " . $e->getMessage();
}

// Get borrowing statistics
try {
    $stmt = $pdo->prepare("SELECT 
                          COUNT(*) as total,
                          SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) as active,
                          SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as returned,
                          SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue
                          FROM borrowings WHERE anggota_id = ?");
    $stmt->execute([$anggotaId]);
    $stats = $stmt->fetch();
} catch (PDOException $e) {
    $stats = ['total' => 0, 'active' => 0, 'returned' => 0, 'overdue' => 0];
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">My Borrowings</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="index.php?page=my-borrowings" class="btn btn-sm btn-outline-secondary <?php echo empty($filter) ? 'active' : ''; ?>">All</a>
            <a href="index.php?page=my-borrowings&filter=active" class="btn btn-sm btn-outline-info <?php echo $filter === 'active' ? 'active' : ''; ?>">Active</a>
            <a href="index.php?page=my-borrowings&filter=returned" class="btn btn-sm btn-outline-success <?php echo $filter === 'returned' ? 'active' : ''; ?>">Returned</a>
            <a href="index.php?page=my-borrowings&filter=overdue" class="btn btn-sm btn-outline-danger <?php echo $filter === 'overdue' ? 'active' : ''; ?>">Overdue</a>
        </div>
    </div>
</div>

<?php if (isset($errorMessage)): ?>
    <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card h-100 border-primary">
            <div class="card-body text-center">
                <h3 class="text-primary"><?php echo $stats['total']; ?></h3>
                <p class="card-text">Total Borrowings</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card h-100 border-info">
            <div class="card-body text-center">
                <h3 class="text-info"><?php echo $stats['active']; ?></h3>
                <p class="card-text">Currently Borrowed</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card h-100 border-success">
            <div class="card-body text-center">
                <h3 class="text-success"><?php echo $stats['returned']; ?></h3>
                <p class="card-text">Returned Books</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card h-100 border-danger">
            <div class="card-body text-center">
                <h3 class="text-danger"><?php echo $stats['overdue']; ?></h3>
                <p class="card-text">Overdue Books</p>
            </div>
        </div>
    </div>
</div>

<!-- Borrowings List -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">
            <?php
            if ($filter === 'active') {
                echo 'Currently Borrowed Books';
            } elseif ($filter === 'returned') {
                echo 'Returned Books';
            } elseif ($filter === 'overdue') {
                echo 'Overdue Books';
            } else {
                echo 'All My Borrowings';
            }
            ?>
            <span class="badge bg-secondary ms-2"><?php echo $totalItems; ?></span>
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($borrowings)): ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-book display-1"></i>
                <h4 class="mt-3">No borrowings found</h4>
                <p>You haven't borrowed any books yet.</p>
                <a href="index.php?page=books" class="btn btn-primary">Browse Books</a>
            </div>
        <?php else: ?>
            <div class="row g-0">
                <?php foreach ($borrowings as $borrowing): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card m-2">
                            <div class="row g-0">
                                <div class="col-4">
                                    <div class="bg-light d-flex align-items-center justify-content-center" style="height: 150px;">
                                        <?php if (!empty($borrowing['cover'])): ?>
                                            <img src="data:image/jpeg;base64,<?php echo base64_encode($borrowing['cover']); ?>" 
                                                 alt="Book Cover" class="img-fluid" style="max-height: 130px;">
                                        <?php else: ?>
                                            <i class="bi bi-book display-4 text-muted"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-8">
                                    <div class="card-body p-2">
                                        <h6 class="card-title"><?php echo htmlspecialchars($borrowing['book_title']); ?></h6>
                                        <?php if (!empty($borrowing['book_author'])): ?>
                                            <p class="card-text small text-muted">by <?php echo htmlspecialchars($borrowing['book_author']); ?></p>
                                        <?php endif; ?>
                                        
                                        <div class="small">
                                            <div><strong>Borrowed:</strong> <?php echo formatDate($borrowing['borrow_date']); ?></div>
                                            <div><strong>Due:</strong> <?php echo formatDate($borrowing['due_date']); ?></div>
                                            <?php if ($borrowing['return_date']): ?>
                                                <div><strong>Returned:</strong> <?php echo formatDate($borrowing['return_date']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="mt-2">
                                            <span class="badge bg-<?php 
                                                echo $borrowing['status'] == 'borrowed' ? 'info' : 
                                                    ($borrowing['status'] == 'returned' ? 'success' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($borrowing['status']); ?>
                                            </span>
                                            
                                            <?php if ($borrowing['status'] === 'borrowed' || $borrowing['status'] === 'overdue'): ?>
                                                <?php $daysRemaining = calculateDaysRemaining($borrowing['due_date']); ?>
                                                <?php if ($daysRemaining < 0): ?>
                                                    <span class="badge bg-danger"><?php echo abs($daysRemaining); ?> days overdue</span>
                                                <?php elseif ($daysRemaining <= 3): ?>
                                                    <span class="badge bg-warning"><?php echo $daysRemaining; ?> days left</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white">
            <?php
            $urlPattern = "index.php?page=my-borrowings" . 
                (!empty($filter) ? "&filter=$filter" : "") . 
                "&p=%d";
            echo generatePagination($page, $totalPages, $urlPattern);
            ?>
        </div>
    <?php endif; ?>
</div>
