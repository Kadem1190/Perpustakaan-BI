<?php
// Check if user is logged in first
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Define user roles if not already defined
if (!defined('p_role_ADMIN')) define('p_role_ADMIN', 'admin');
if (!defined('p_role_STAFF')) define('p_role_STAFF', 'staff');

// Check user permissions
$userRole = $_SESSION['p_role'];
if (!in_array($userRole, [p_role_ADMIN, p_role_STAFF])) {
    header('Location: index.php');
    exit;
}
updateFines();

$pageTitle = 'Fine Management';

// Handle fine payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'pay_fine' && isset($_POST['fine_id'])) {
        $fineId = intval($_POST['fine_id']);
        try {
            $pdo->beginTransaction();
            
            // First check if fine exists and is unpaid/pending
            $stmt = $pdo->prepare("
                SELECT f.*, b.status as borrowing_status 
                FROM fines f
                JOIN borrowings b ON f.borrowing_id = b.borrowing_id
                WHERE f.fine_id = ? 
                AND f.status IN ('unpaid', 'pending')
            ");
            $stmt->execute([$fineId]);
            $fine = $stmt->fetch();
            
            if (!$fine) {
                throw new Exception("Fine not found or already paid.");
            }
            
            // Update the fine status
            $stmt = $pdo->prepare("
                UPDATE fines 
                SET status = 'paid',
                    paid_at = NOW(),
                    updated_by = ?,
                    updated_at = NOW()
                WHERE fine_id = ?
            ");
            
            if (!$stmt->execute([$_SESSION['user_id'], $fineId])) {
                throw new Exception("Failed to update fine status.");
            }
            
            $pdo->commit();
            
            $_SESSION['success_message'] = "Fine has been marked as paid successfully.";
            header("Location: index.php?page=fines");
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = $e->getMessage();
        }
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_fine_amount']) && $userRole === p_role_ADMIN) {
        $newAmount = filter_var($_POST['fine_amount'], FILTER_VALIDATE_INT);
        if ($newAmount !== false && $newAmount >= 0) {
            try {
                $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'fine_per_day'");
                $stmt->execute([$newAmount]);
                $successMessage = "Fine amount per day updated successfully.";
            } catch (PDOException $e) {
                $errorMessage = "Error updating fine amount: " . $e->getMessage();
            }
        } else {
            $errorMessage = "Invalid fine amount. Please enter a valid number.";
        }
    }
}

// Get current fine amount
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'fine_per_day'");
$finePerDay = $stmt->fetchColumn() ?: 1000; // Default to 1000 if not set

// Get all fines with member and book details
$query = "SELECT f.*, 
          a.name as member_name, a.nim,
          b.borrow_date, b.due_date, b.return_date, b.status as borrowing_status,
          bk.title as book_title
          FROM fines f
          JOIN borrowings b ON f.borrowing_id = b.borrowing_id
          JOIN anggota a ON f.anggota_id = a.anggota_id
          JOIN books bk ON b.book_id = bk.book_id
          ORDER BY f.created_at DESC";

$fines = $pdo->query($query)->fetchAll();

// Calculate totals
$totalUnpaid = 0;
$totalPaid = 0;
foreach ($fines as $fine) {
    // Consider 'pending' status as unpaid
    if ($fine['status'] === 'unpaid' || $fine['status'] === 'pending') {
        $totalUnpaid += $fine['amount'];
    } else if ($fine['status'] === 'paid') {
        $totalPaid += $fine['amount'];
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Fine Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportFinesReport()">
                <i class="bi bi-download"></i> Export Report
            </button>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card border-danger h-100">
            <div class="card-body">
                <h5 class="card-title text-danger">Unpaid Fines</h5>
                <h2 class="mb-0">Rp <?php echo number_format($totalUnpaid, 0, ',', '.'); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-success h-100">
            <div class="card-body">
                <h5 class="card-title text-success">Collected Fines</h5>
                <h2 class="mb-0">Rp <?php echo number_format($totalPaid, 0, ',', '.'); ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Fine Settings (Admin Only) -->
<?php if ($userRole === p_role_ADMIN): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Fine Settings</h5>
    </div>
    <div class="card-body">
        <form method="post" class="row align-items-center">
            <div class="col-auto">
                <label class="form-label">Fine Amount per Day</label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" class="form-control" name="fine_amount" 
                           value="<?php echo htmlspecialchars($finePerDay); ?>" 
                           min="0" step="500" required>
                    <button type="submit" name="update_fine_amount" class="btn btn-primary">
                        Update Amount
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Fines Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Book</th>
                        <th>Due Date</th>
                        <th>Days Late</th>
                        <th>Fine Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fines as $fine): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($fine['member_name']); ?>
                                <small class="d-block text-muted"><?php echo htmlspecialchars($fine['nim']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($fine['book_title']); ?></td>
                            <td><?php echo formatDate($fine['due_date']); ?></td>
                            <td><?php echo $fine['days_overdue']; ?> days</td>
                            <td>Rp <?php echo number_format($fine['amount'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $fine['status'] === 'paid' ? 'success' : ($fine['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                    <?php echo ucfirst($fine['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($fine['status'] === 'unpaid' || $fine['status'] === 'pending'): ?>
                                    <!-- Pay Fine Button -->
                                    <button type="button" 
                                            class="btn btn-success" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#payFineModal<?php echo $fine['fine_id']; ?>">
                                        <i class="bi bi-cash"></i> Pay
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Pay Fine Modal -->
                        <?php if ($fine['status'] === 'unpaid' || $fine['status'] === 'pending'): ?>
                        <div class="modal fade" id="payFineModal<?php echo $fine['fine_id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="index.php?page=fines" method="post">
                                        <input type="hidden" name="action" value="pay_fine">
                                        <input type="hidden" name="fine_id" value="<?php echo $fine['fine_id']; ?>">
                                        
                                        <div class="modal-header">
                                            <h5 class="modal-title">Pay Fine</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-info">
                                                <p class="mb-0"><strong>Member:</strong> <?php echo htmlspecialchars($fine['member_name']); ?></p>
                                                <p class="mb-0"><strong>Book:</strong> <?php echo htmlspecialchars($fine['book_title']); ?></p>
                                                <p class="mb-0"><strong>Amount:</strong> Rp <?php echo number_format($fine['amount'], 0, ',', '.'); ?></p>
                                            </div>
                                            <p class="text-center mt-3">Are you sure you want to mark this fine as paid?</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-success">
                                                <i class="bi bi-check-circle"></i> Confirm Payment
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function exportFinesReport() {
    window.location.href = 'export_fines.php';
}
</script>