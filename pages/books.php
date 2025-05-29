<?php
// Remove session_start() since it's already called in index.php
// session_start();

// Fix include paths
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// At the top of the file after session check
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Define user roles if not already defined
if (!defined('p_role_ADMIN')) define('p_role_ADMIN', 'admin');
if (!defined('p_role_STAFF')) define('p_role_STAFF', 'staff');
if (!defined('p_role_MEMBER')) define('p_role_MEMBER', 'member');

// Check user permissions
if (!isset($_SESSION['p_role']) || !in_array($_SESSION['p_role'], [p_role_ADMIN, p_role_STAFF, p_role_MEMBER])) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Books Management';

$userRole = $_SESSION['p_role'];
$canManageBooks = ($userRole === p_role_ADMIN);
$canBorrowBooks = ($userRole === p_role_MEMBER);

// Define UPLOAD_DIR if not already defined
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', __DIR__ . '/../assets/');
}

// Process form submissions (only for book management, not borrowing)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Add new book
        if ($action === 'add') {
            $title = sanitizeInput($_POST['title']);
            $author = sanitizeInput($_POST['author']);
            $publisher = sanitizeInput($_POST['publisher']);
            $yearPublished = !empty($_POST['year_published']) ? intval($_POST['year_published']) : null;
            $stock = intval($_POST['stock']);
            
            if (empty($title)) {
                $errorMessage = "Book title is required.";
            } else {
                try {
                    $coverImage = null;
                    
                    // Handle cover image upload
                    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                        $uploadResult = uploadImage($_FILES['cover'], UPLOAD_DIR . 'covers/');
                        
                        if ($uploadResult['success']) {
                            $coverImage = file_get_contents($uploadResult['path']);
                        } else {
                            $errorMessage = $uploadResult['message'];
                        }
                    }
                    
                    // Insert book data
                    $stmt = $pdo->prepare("INSERT INTO books (title, author, publisher, year_published, cover, stock) 
                                          VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $author, $publisher, $yearPublished, $coverImage, $stock]);
                    
                    $bookId = $pdo->lastInsertId();
                    
                    logActivity('Book added', $_SESSION['user_id'], 'books', 'INSERT', $bookId);
                    
                    $successMessage = "Book has been added successfully.";
                } catch (PDOException $e) {
                    $errorMessage = "Error adding book: " . $e->getMessage();
                }
            }
        }
        
        // Update book
        elseif ($action === 'update') {
            $bookId = intval($_POST['book_id']);
            $title = sanitizeInput($_POST['title']);
            $author = sanitizeInput($_POST['author']);
            $publisher = sanitizeInput($_POST['publisher']);
            $yearPublished = !empty($_POST['year_published']) ? intval($_POST['year_published']) : null;
            $stock = intval($_POST['stock']);
            
            if (empty($title)) {
                $errorMessage = "Book title is required.";
            } else {
                try {
                    // Start with basic update query
                    $query = "UPDATE books SET title = ?, author = ?, publisher = ?, year_published = ?, stock = ?";
                    $params = [$title, $author, $publisher, $yearPublished, $stock];
                    
                    // Handle cover image upload
                    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                        $uploadResult = uploadImage($_FILES['cover'], UPLOAD_DIR . 'covers/');
                        
                        if ($uploadResult['success']) {
                            $coverImage = file_get_contents($uploadResult['path']);
                            $query .= ", cover = ?";
                            $params[] = $coverImage;
                        } else {
                            $errorMessage = $uploadResult['message'];
                        }
                    }
                    
                    // Complete the query
                    $query .= " WHERE book_id = ?";
                    $params[] = $bookId;
                    
                    // Execute update
                    $stmt = $pdo->prepare($query);
                    $stmt->execute($params);
                    
                    logActivity('Book updated', $_SESSION['user_id'], 'books', 'UPDATE', $bookId);
                    
                    $successMessage = "Book has been updated successfully.";
                } catch (PDOException $e) {
                    $errorMessage = "Error updating book: " . $e->getMessage();
                }
            }
        }
        
        // Delete book
        elseif ($action === 'delete') {
            $bookId = intval($_POST['book_id']);
            
            try {
                // Check if book has active borrowings
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM borrowings 
                                      WHERE book_id = ? AND status = 'borrowed'");
                $stmt->execute([$bookId]);
                $activeBorrowings = $stmt->fetch()['count'];
                
                if ($activeBorrowings > 0) {
                    $errorMessage = "Cannot delete book. It has active borrowings.";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM books WHERE book_id = ?");
                    $stmt->execute([$bookId]);
                    
                    logActivity('Book deleted', $_SESSION['user_id'], 'books', 'DELETE', $bookId);
                    
                    $successMessage = "Book has been deleted successfully.";
                }
            } catch (PDOException $e) {
                $errorMessage = "Error deleting book: " . $e->getMessage();
            }
        }
    }
}

// Prevent non-admin users from performing management actions
if (!$canManageBooks && in_array($_POST['action'] ?? '', ['add', 'update', 'delete'])) {
    header('Location: index.php?page=books');
    exit;
}

// Process GET actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $bookId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($action === 'edit' && $bookId > 0) {
        // Get book details for editing
        try {
            $stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = ?");
            $stmt->execute([$bookId]);
            $book = $stmt->fetch();
            
            if (!$book) {
                $errorMessage = "Book not found.";
            }
        } catch (PDOException $e) {
            $errorMessage = "Error retrieving book details: " . $e->getMessage();
        }
    } elseif ($action === 'delete' && $bookId > 0) {
        // Get book details for confirmation
        try {
            $stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = ?");
            $stmt->execute([$bookId]);
            $book = $stmt->fetch();
            
            if (!$book) {
                $errorMessage = "Book not found.";
            }
        } catch (PDOException $e) {
            $errorMessage = "Error retrieving book details: " . $e->getMessage();
        }
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['p']) ? intval($_GET['p']) : 1;
$limit = defined('ITEMS_PER_PAGE') ? ITEMS_PER_PAGE : 10;
$offset = ($page - 1) * $limit;

// Build query based on filters
$query = "SELECT * FROM books";
$countQuery = "SELECT COUNT(*) as total FROM books";
$params = [];

if (!empty($search)) {
    $query .= " WHERE title LIKE ? OR author LIKE ? OR publisher LIKE ?";
    $countQuery .= " WHERE title LIKE ? OR author LIKE ? OR publisher LIKE ?";
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
    $errorMessage = "Error counting books: " . $e->getMessage();
}

// Get books with pagination
$query .= " ORDER BY title ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $books = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMessage = "Error retrieving books: " . $e->getMessage();
}

// Helper functions
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }
}

if (!function_exists('uploadImage')) {
    function uploadImage($file, $targetDir) {
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        if ($file['size'] > 5000000) {
            return ['success' => false, 'message' => 'File is too large. Maximum size is 5MB.'];
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Only JPG, JPEG, and PNG files are allowed.'];
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $targetPath = $targetDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => true, 'path' => $targetPath, 'filename' => $filename];
        } else {
            return ['success' => false, 'message' => 'Failed to upload file.'];
        }
    }
}

if (!function_exists('logActivity')) {
    function logActivity($action, $userId, $table, $operation, $recordId) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, table_name, operation, record_id, created_at) 
                                  VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$userId, $action, $table, $operation, $recordId]);
        } catch (PDOException $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
}

if (!function_exists('generatePagination')) {
    function generatePagination($currentPage, $totalPages, $urlPattern) {
        $output = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center mb-0">';
        
        if ($currentPage > 1) {
            $output .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, $currentPage - 1) . '">&laquo;</a></li>';
        } else {
            $output .= '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
        }
        
        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);
        
        if ($startPage > 1) {
            $output .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, 1) . '">1</a></li>';
            if ($startPage > 2) {
                $output .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        for ($i = $startPage; $i <= $endPage; $i++) {
            if ($i == $currentPage) {
                $output .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
            } else {
                $output .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, $i) . '">' . $i . '</a></li>';
            }
        }
        
        if ($endPage < $totalPages) {
            if ($endPage < $totalPages - 1) {
                $output .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            $output .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, $totalPages) . '">' . $totalPages . '</a></li>';
        }
        
        if ($currentPage < $totalPages) {
            $output .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, $currentPage + 1) . '">&raquo;</a></li>';
        } else {
            $output .= '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
        }
        
        $output .= '</ul></nav>';
        return $output;
    }
}
?>

<style>
.book-cover-sm {
    max-width: 150px;
    max-height: 150px;
    width: auto;
    height: auto;
    object-fit: contain;
    border-radius: 4px;
    border: 1px solid #eee;
    background: #fff;
}

.borrow-btn {
    transition: all 0.3s ease;
}

.borrow-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.loading {
    opacity: 0.6;
    pointer-events: none;
}
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Books Collection</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if ($canManageBooks): ?>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
                <i class="bi bi-plus-circle me-1"></i> Add New Book
            </button>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($successMessage)): ?>
    <div class="alert alert-success"><?php echo $successMessage; ?></div>
<?php endif; ?>

<?php if (isset($errorMessage)): ?>
    <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
<?php endif; ?>

<!-- Alert container for AJAX responses -->
<div id="alertContainer"></div>

<!-- Search Form -->
<div class="card mb-4">
    <div class="card-body">
        <form action="index.php" method="get" class="row g-3">
            <input type="hidden" name="page" value="books">
            
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search" placeholder="Search by title, author, or publisher" value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="index.php?page=books" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Books Table -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">
            Books
            <span class="badge bg-secondary ms-2"><?php echo $totalItems; ?></span>
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($books)): ?>
            <div class="p-4 text-center text-muted">No books found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cover</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Publisher</th>
                            <th>Year</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td><?php echo $book['book_id']; ?></td>
                                <td>
                                    <?php if (!empty($book['cover'])): ?>
                                        <img src="data:image/jpeg;base64,<?php echo base64_encode($book['cover']); ?>" alt="Cover" class="book-cover-sm">
                                    <?php else: ?>
                                        <div class="book-cover-sm bg-light d-flex align-items-center justify-content-center">
                                            <i class="bi bi-book text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo htmlspecialchars($book['author'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($book['publisher'] ?? 'N/A'); ?></td>
                                <td><?php echo $book['year_published'] ?? 'N/A'; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $book['stock'] > 0 ? 'success' : 'danger'; ?>">
                                        <?php echo $book['stock']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($canManageBooks): ?>
                                        <div class="btn-group btn-group-sm">
                                            <a href="index.php?page=books&action=edit&id=<?php echo $book['book_id']; ?>" 
                                               class="btn btn-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="index.php?page=books&action=delete&id=<?php echo $book['book_id']; ?>" 
                                               class="btn btn-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    <?php elseif ($canBorrowBooks && $book['stock'] > 0): ?>
                                        <!-- Updated borrowing button to use AJAX -->
                                        <button type="button" 
                                                class="btn btn-sm btn-success borrow-btn" 
                                                onclick="borrowBook(<?php echo $book['book_id']; ?>, '<?php echo htmlspecialchars($book['title'], ENT_QUOTES); ?>')"
                                                data-book-id="<?php echo $book['book_id']; ?>">
                                            <i class="bi bi-journal-arrow-down"></i> Borrow
                                        </button>
                                    <?php elseif ($canBorrowBooks): ?>
                                        <span class="text-muted small">Out of stock</span>
                                    <?php endif; ?>
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
            $urlPattern = "index.php?page=books" . 
                (!empty($search) ? "&search=$search" : "") . 
                "&p=%d";
            echo generatePagination($page, $totalPages, $urlPattern);
            ?>
        </div>
    <?php endif; ?>
</div>

<!-- Add Book Modal -->
<div class="modal fade" id="addBookModal" tabindex="-1" aria-labelledby="addBookModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="index.php?page=books" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="addBookModalLabel">Add New Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="author" class="form-label">Author</label>
                        <input type="text" class="form-control" id="author" name="author">
                    </div>
                    <div class="mb-3">
                        <label for="publisher" class="form-label">Publisher</label>
                        <input type="text" class="form-control" id="publisher" name="publisher">
                    </div>
                    <div class="mb-3">
                        <label for="year_published" class="form-label">Year Published</label>
                        <input type="number" class="form-control" id="year_published" name="year_published" min="1900" max="<?php echo date('Y'); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="stock" class="form-label">Stock <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="stock" name="stock" min="0" value="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="cover" class="form-label">Cover Image</label>
                        <input type="file" class="form-control image-upload" id="cover" name="cover" accept="image/jpeg,image/png,image/jpg" data-preview="#coverPreview">
                        <div class="mt-2">
                            <img id="coverPreview" src="#" alt="Cover Preview" style="max-width: 100%; max-height: 200px; display: none;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Book</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Book Modal -->
<?php if (isset($book) && isset($action) && $action === 'edit'): ?>
<div class="modal fade" id="editBookModal" tabindex="-1" aria-labelledby="editBookModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="index.php?page=books" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="editBookModalLabel">Edit Book</h5>
                    <a href="index.php?page=books" class="btn-close" aria-label="Close"></a>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_title" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_author" class="form-label">Author</label>
                        <input type="text" class="form-control" id="edit_author" name="author" value="<?php echo htmlspecialchars($book['author'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="edit_publisher" class="form-label">Publisher</label>
                        <input type="text" class="form-control" id="edit_publisher" name="publisher" value="<?php echo htmlspecialchars($book['publisher'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="edit_year_published" class="form-label">Year Published</label>
                        <input type="number" class="form-control" id="edit_year_published" name="year_published" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo $book['year_published'] ?? ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="edit_stock" class="form-label">Stock <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="edit_stock" name="stock" min="0" value="<?php echo $book['stock']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_cover" class="form-label">Cover Image</label>
                        <?php if (!empty($book['cover'])): ?>
                            <div class="mb-2">
                                <img src="data:image/jpeg;base64,<?php echo base64_encode($book['cover']); ?>" alt="Current Cover" style="max-width: 100%; max-height: 200px;">
                                <p class="text-muted small">Current cover image</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control image-upload" id="edit_cover" name="cover" accept="image/jpeg,image/png,image/jpg" data-preview="#editCoverPreview">
                        <div class="mt-2">
                            <img id="editCoverPreview" src="#" alt="Cover Preview" style="max-width: 100%; max-height: 200px; display: none;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="index.php?page=books" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Book</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var editBookModal = new bootstrap.Modal(document.getElementById('editBookModal'));
        editBookModal.show();
    });
</script>
<?php endif; ?>

<!-- Delete Book Modal -->
<?php if (isset($book) && isset($action) && $action === 'delete'): ?>
<div class="modal fade" id="deleteBookModal" tabindex="-1" aria-labelledby="deleteBookModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="index.php?page=books" method="post">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteBookModalLabel">Delete Book</h5>
                    <a href="index.php?page=books" class="btn-close" aria-label="Close"></a>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the following book?</p>
                    <div class="d-flex align-items-center mb-3">
                        <?php if (!empty($book['cover'])): ?>
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($book['cover']); ?>" alt="Cover" class="book-cover-sm me-3">
                        <?php else: ?>
                            <div class="book-cover-sm bg-light d-flex align-items-center justify-content-center me-3">
                                <i class="bi bi-book text-muted"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h5 class="mb-1"><?php echo htmlspecialchars($book['title']); ?></h5>
                            <p class="mb-0 text-muted">
                                <?php echo htmlspecialchars($book['author'] ?? 'Unknown Author'); ?>
                                <?php if (!empty($book['year_published'])): ?>
                                    (<?php echo $book['year_published']; ?>)
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        This action cannot be undone. All borrowing records associated with this book will remain in the database.
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="index.php?page=books" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-danger">Delete Book</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var deleteBookModal = new bootstrap.Modal(document.getElementById('deleteBookModal'));
        deleteBookModal.show();
    });
</script>
<?php endif; ?>

<script>
// Image preview functionality
document.addEventListener('DOMContentLoaded', function() {
    // Handle image upload preview
    document.querySelectorAll('.image-upload').forEach(function(input) {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewId = this.getAttribute('data-preview');
            const preview = document.querySelector(previewId);
            
            if (file && preview) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    });
});

// AJAX borrowing functionality
function borrowBook(bookId, bookTitle) {
    const button = document.querySelector(`[data-book-id="${bookId}"]`);
    const originalContent = button.innerHTML;
    
    // Show loading state
    button.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
    button.disabled = true;
    button.classList.add('loading');
    
    // Create form data
    const formData = new FormData();
    formData.append('book_id', bookId);
    
    // Send AJAX request
    fetch('borrowing_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Show alert
        showAlert(data.success ? 'success' : 'danger', data.message);
        
        if (data.success) {
            // Update button to show borrowed state
            button.innerHTML = '<i class="bi bi-check-circle"></i> Borrowed';
            button.classList.remove('btn-success');
            button.classList.add('btn-secondary');
            button.disabled = true;
            
            // Update stock display if visible
            const row = button.closest('tr');
            const stockBadge = row.querySelector('.badge');
            if (stockBadge) {
                const currentStock = parseInt(stockBadge.textContent);
                const newStock = currentStock - 1;
                stockBadge.textContent = newStock;
                if (newStock === 0) {
                    stockBadge.classList.remove('bg-success');
                    stockBadge.classList.add('bg-danger');
                }
            }
        } else {
            // Reset button on error
            button.innerHTML = originalContent;
            button.disabled = false;
            button.classList.remove('loading');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'An error occurred while processing your request.');
        
        // Reset button on error
        button.innerHTML = originalContent;
        button.disabled = false;
        button.classList.remove('loading');
    });
}

// Function to show alerts
function showAlert(type, message) {
    const alertContainer = document.getElementById('alertContainer');
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    alertContainer.appendChild(alertDiv);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
</script>