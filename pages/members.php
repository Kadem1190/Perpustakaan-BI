<?php
requirep_role(p_role_STAFF);
$pageTitle = 'Member Management';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Add new member
        if ($action === 'add') {
            $name = sanitizeInput($_POST['name']);
            $nim = sanitizeInput($_POST['nim']);
            $class = sanitizeInput($_POST['class']);
            $gender = $_POST['gender'];
            $dateOfBirth = $_POST['date_of_birth'];
            $placeOfBirth = sanitizeInput($_POST['place_of_birth']);
            $address = sanitizeInput($_POST['address']);
            $phone = sanitizeInput($_POST['phone']);
            $email = sanitizeInput($_POST['email']);
            $username = sanitizeInput($_POST['username']);
            $password = $_POST['password'];
            
            if (empty($name) || empty($nim) || empty($username) || empty($password)) {
                $errorMessage = "Required fields cannot be empty.";
            } else {
                try {
                    $pdo->beginTransaction();
                    
                    // Insert into anggota
                    $stmt = $pdo->prepare("INSERT INTO anggota (name, nim, class, gender, date_of_birth, place_of_birth, address, phone, email) 
                                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $nim, $class, $gender, $dateOfBirth, $placeOfBirth, $address, $phone, $email]);
                    $anggotaId = $pdo->lastInsertId();
                    
                    // Insert into users
                    $hashedPassword = md5($password); // Using MD5 to match existing system
                    $stmt = $pdo->prepare("INSERT INTO users (anggota_id, username, password, full_name, p_role, status, registration_status) 
                                          VALUES (?, ?, ?, ?, 'member', 'active', 'approved')");
                    $stmt->execute([$anggotaId, $username, $hashedPassword, $name]);
                    
                    $pdo->commit();
                    $successMessage = "Member has been added successfully.";
                    
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $errorMessage = "Error adding member: " . $e->getMessage();
                }
            }
        }
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['p']) ? intval($_GET['p']) : 1;
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT u.*, a.* FROM users u 
          JOIN anggota a ON u.anggota_id = a.anggota_id 
          WHERE u.p_role = 'member'";
$countQuery = "SELECT COUNT(*) as total FROM users u 
               JOIN anggota a ON u.anggota_id = a.anggota_id 
               WHERE u.p_role = 'member'";
$params = [];

if (!empty($search)) {
    $query .= " AND (a.name LIKE ? OR a.nim LIKE ? OR u.username LIKE ?)";
    $countQuery .= " AND (a.name LIKE ? OR a.nim LIKE ? OR u.username LIKE ?)";
    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam];
}

// Get total count and data
try {
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalItems = $stmt->fetch()['total'];
    $totalPages = ceil($totalItems / $limit);
    
    $query .= " ORDER BY a.name LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $members = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMessage = "Database error: " . $e->getMessage();
}
?>

<!-- Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Member Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
            <i class="bi bi-person-plus me-1"></i> Add New Member
        </button>
    </div>
</div>

<!-- Search Form -->
<div class="row mb-3">
    <div class="col-md-6">
        <form class="d-flex" method="get" action="index.php">
            <input type="hidden" name="page" value="members">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" name="search" placeholder="Search by name, NIM, or username" value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-primary" type="submit">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="index.php?page=members" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if (isset($successMessage)): ?>
    <div class="alert alert-success"><?php echo $successMessage; ?></div>
<?php endif; ?>

<?php if (isset($errorMessage)): ?>
    <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
<?php endif; ?>

<!-- Members Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($members)): ?>
            <div class="text-center text-muted">No members found</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>NIM</th>
                            <th>Class</th>
                            <th>Username</th>
                            <th>Status</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($member['name']); ?></td>
                                <td><?php echo htmlspecialchars($member['nim']); ?></td>
                                <td><?php echo htmlspecialchars($member['class']); ?></td>
                                <td><?php echo htmlspecialchars($member['username']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $member['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($member['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($member['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="d-flex justify-content-center mt-4">
                    <?php
                    $urlPattern = "index.php?page=members" . (!empty($search) ? "&search=$search" : "") . "&p=%d";
                    echo generatePagination($page, $totalPages, $urlPattern);
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-labelledby="addMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="add">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="addMemberModalLabel">Add New Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">NIM <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nim" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Class</label>
                                <input type="text" class="form-control" name="class">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Gender <span class="text-danger">*</span></label>
                                <select class="form-select" name="gender" required>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="date_of_birth">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Place of Birth</label>
                                <input type="text" class="form-control" name="place_of_birth">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Member</button>
                </div>
            </form>
        </div>
    </div>
</div>