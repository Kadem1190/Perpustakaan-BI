<?php
requirep_role(p_role_MEMBER);
$pageTitle = 'My Fines';
$anggotaId = $_SESSION['anggota_id'];

// Get user's fines
$query = "SELECT f.*, 
          b.borrow_date, b.due_date, b.return_date,
          bk.title as book_title, bk.author
          FROM fines f
          JOIN borrowings b ON f.borrowing_id = b.borrowing_id
          JOIN books bk ON b.book_id = bk.book_id
          WHERE f.anggota_id = ?
          ORDER BY f.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$anggotaId]);
$fines = $stmt->fetchAll();

// Calculate totals
$totalUnpaid = 0;
$totalPaid = 0;
foreach ($fines as $fine) {
    if ($fine['status'] === 'unpaid') {
        $totalUnpaid += $fine['amount'];
    } else {
        $totalPaid += $fine['amount'];
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">My Fines</h1>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card border-danger h-100">
            <div class="card-body">
                <h5 class="card-title text-danger">Outstanding Fines</h5>
                <h2 class="mb-0">Rp <?php echo number_format($totalUnpaid, 0, ',', '.'); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-success h-100">
            <div class="card-body">
                <h5 class="card-title text-success">Paid Fines</h5>
                <h2 class="mb-0">Rp <?php echo number_format($totalPaid, 0, ',', '.'); ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Fines Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($fines)): ?>
            <div class="text-center text-muted">No fines found</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Book</th>
                            <th>Due Date</th>
                            <th>Days Late</th>
                            <th>Fine Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fines as $fine): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($fine['book_title']); ?>
                                    <?php if ($fine['author']): ?>
                                        <small class="d-block text-muted">by <?php echo htmlspecialchars($fine['author']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDate($fine['due_date']); ?></td>
                                <td><?php echo $fine['days_overdue']; ?> days</td>
                                <td>Rp <?php echo number_format($fine['amount'], 0, ',', '.'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $fine['status'] === 'paid' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($fine['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>