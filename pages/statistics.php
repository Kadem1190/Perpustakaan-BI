<?php
$pageTitle = 'Statistics';

// Get date range for filtering
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get borrowing statistics
try {
    // Total borrowings by status
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM borrowings GROUP BY status");
    $stmt->execute();
    $borrowingsByStatus = $stmt->fetchAll();
    
    // Most borrowed books
    $stmt = $pdo->prepare("SELECT b.book_id, bk.title, bk.author, COUNT(*) as borrow_count 
                          FROM borrowings b
                          JOIN books bk ON b.book_id = bk.book_id
                          WHERE b.borrow_date BETWEEN ? AND ?
                          GROUP BY b.book_id
                          ORDER BY borrow_count DESC
                          LIMIT 10");
    $stmt->execute([$startDate, $endDate]);
    $mostBorrowedBooks = $stmt->fetchAll();
    
    // Most active members
    $stmt = $pdo->prepare("SELECT b.anggota_id, a.name, a.nim, COUNT(*) as borrow_count 
                          FROM borrowings b
                          JOIN anggota a ON b.anggota_id = a.anggota_id
                          WHERE b.borrow_date BETWEEN ? AND ?
                          GROUP BY b.anggota_id
                          ORDER BY borrow_count DESC
                          LIMIT 10");
    $stmt->execute([$startDate, $endDate]);
    $mostActiveMembers = $stmt->fetchAll();
    
    // Borrowings by month
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(borrow_date, '%Y-%m') as month, COUNT(*) as count 
                          FROM borrowings 
                          WHERE borrow_date BETWEEN DATE_SUB(?, INTERVAL 12 MONTH) AND ?
                          GROUP BY month 
                          ORDER BY month");
    $stmt->execute([$endDate, $endDate]);
    $borrowingsByMonth = $stmt->fetchAll();
    
    // Convert to format suitable for Chart.js
    $monthLabels = [];
    $monthData = [];
    foreach ($borrowingsByMonth as $item) {
        $date = DateTime::createFromFormat('Y-m', $item['month']);
        $monthLabels[] = $date->format('M Y');
        $monthData[] = $item['count'];
    }
    
    // Status counts for pie chart
    $statusLabels = [];
    $statusData = [];
    $statusColors = [
        'borrowed' => 'rgba(23, 162, 184, 0.8)',
        'returned' => 'rgba(40, 167, 69, 0.8)',
        'overdue' => 'rgba(220, 53, 69, 0.8)'
    ];
    $statusColorsArray = [];
    
    foreach ($borrowingsByStatus as $item) {
        $statusLabels[] = ucfirst($item['status']);
        $statusData[] = $item['count'];
        $statusColorsArray[] = $statusColors[$item['status']] ?? 'rgba(108, 117, 125, 0.8)';
    }
} catch (PDOException $e) {
    $errorMessage = "Error retrieving statistics: " . $e->getMessage();
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Statistics</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <form action="index.php" method="get" class="row g-3">
            <input type="hidden" name="page" value="statistics">
            <div class="col-auto">
                <label for="start_date" class="col-form-label">From</label>
            </div>
            <div class="col-auto">
                <input type="date" class="form-control form-control-sm" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
            </div>
            <div class="col-auto">
                <label for="end_date" class="col-form-label">To</label>
            </div>
            <div class="col-auto">
                <input type="date" class="form-control form-control-sm" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">Apply</button>
            </div>
        </form>
    </div>
</div>

<?php if (isset($errorMessage)): ?>
    <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
<?php endif; ?>

<!-- Statistics Overview -->
<div class="row mb-4">
    <?php
    $totalBorrowed = 0;
    $totalReturned = 0;
    $totalOverdue = 0;
    
    foreach ($borrowingsByStatus as $item) {
        if ($item['status'] === 'borrowed') {
            $totalBorrowed = $item['count'];
        } elseif ($item['status'] === 'returned') {
            $totalReturned = $item['count'];
        } elseif ($item['status'] === 'overdue') {
            $totalOverdue = $item['count'];
        }
    }
    
    $totalBorrowings = $totalBorrowed + $totalReturned + $totalOverdue;
    ?>
    
    <div class="col-md-3 mb-3">
        <div class="card h-100 border-primary">
            <div class="card-body">
                <h5 class="card-title text-muted">Total Borrowings</h5>
                <h2 class="card-text"><?php echo $totalBorrowings; ?></h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card h-100 border-info">
            <div class="card-body">
                <h5 class="card-title text-muted">Currently Borrowed</h5>
                <h2 class="card-text"><?php echo $totalBorrowed; ?></h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card h-100 border-success">
            <div class="card-body">
                <h5 class="card-title text-muted">Returned</h5>
                <h2 class="card-text"><?php echo $totalReturned; ?></h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card h-100 border-danger">
            <div class="card-body">
                <h5 class="card-title text-muted">Overdue</h5>
                <h2 class="card-text"><?php echo $totalOverdue; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Borrowings by Month Chart -->
    <div class="col-md-8 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Borrowings by Month</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="borrowingsByMonthChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Borrowings by Status Chart -->
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Borrowings by Status</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="borrowingsByStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Most Borrowed Books -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Most Borrowed Books</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($mostBorrowedBooks)): ?>
                    <div class="p-3 text-center text-muted">No data available.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Book</th>
                                    <th>Borrowings</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mostBorrowedBooks as $index => $book): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($book['title']); ?>
                                            <?php if (!empty($book['author'])): ?>
                                                <div class="small text-muted">by <?php echo htmlspecialchars($book['author']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo $book['borrow_count']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Most Active Members -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Most Active Members</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($mostActiveMembers)): ?>
                    <div class="p-3 text-center text-muted">No data available.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Member</th>
                                    <th>Borrowings</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mostActiveMembers as $index => $member): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($member['name']); ?>
                                            <div class="small text-muted"><?php echo htmlspecialchars($member['nim']); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo $member['borrow_count']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Borrowings by Month Chart
    var monthlyCtx = document.getElementById('borrowingsByMonthChart').getContext('2d');
    var monthlyChart = new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($monthLabels); ?>,
            datasets: [{
                label: 'Borrowings',
                data: <?php echo json_encode($monthData); ?>,
                backgroundColor: 'rgba(23, 162, 184, 0.2)',
                borderColor: 'rgba(23, 162, 184, 1)',
                borderWidth: 2,
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // Borrowings by Status Chart
    var statusCtx = document.getElementById('borrowingsByStatusChart').getContext('2d');
    var statusChart = new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($statusLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($statusData); ?>,
                backgroundColor: <?php echo json_encode($statusColorsArray); ?>,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>
