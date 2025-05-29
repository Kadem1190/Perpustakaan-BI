<?php
requirep_role(p_role_MEMBER);
$pageTitle = 'Browse Books';

// Get available books for members
$books = getMemberBooks();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Browse Books</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <span class="badge bg-info">Member Portal</span>
        </div>
    </div>
</div>

<!-- Search Form -->
<div class="card mb-4">
    <div class="card-body">
        <form action="index.php" method="get" class="row g-3">
            <input type="hidden" name="page" value="books">
            
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search" placeholder="Search by title, author, or category" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($_GET['search'])): ?>
                    <a href="index.php?page=books" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Books Grid -->
<div class="row">
    <?php if (empty($books)): ?>
        <div class="col-12">
            <div class="text-center py-5">
                <i class="bi bi-book display-1 text-muted"></i>
                <h3 class="mt-3">No books found</h3>
                <p class="text-muted">Try adjusting your search criteria</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($books as $book): ?>
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="card h-100">
                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                        <?php if (!empty($book['cover'])): ?>
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($book['cover']); ?>" 
                                 alt="Book Cover" class="img-fluid" style="max-height: 180px;">
                        <?php else: ?>
                            <i class="bi bi-book display-4 text-muted"></i>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h6>
                        <?php if (!empty($book['author'])): ?>
                            <p class="card-text text-muted small">by <?php echo htmlspecialchars($book['author']); ?></p>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-<?php echo $book['available_stock'] > 0 ? 'success' : 'danger'; ?>">
                                <?php echo $book['available_stock'] > 0 ? 'Available' : 'Not Available'; ?>
                            </span>
                            <small class="text-muted">Stock balls: <?php echo $book['available_stock']; ?></small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
