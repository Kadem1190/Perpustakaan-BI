<?php
// Start session and include required files at the very top
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Check authentication
if (!isLoggedIn()) {
    redirectTo('login.php');
}

// Only start HTML output after all potential redirects
?>
<!DOCTYPE html>
<html>
<head>
    <title>Your Title</title>
    <!-- Add your head content here -->
</head>
<body>

<?php
// Get current page
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$p_role = $_SESSION['p_role'];

// Define allowed pages for each p_role
$allowedPages = [
    p_role_ADMIN => ['dashboard', 'users', 'books', 'borrowings', 'statistics', 'profile', 'fines'],
    p_role_STAFF => ['dashboard', 'members', 'books', 'borrowings', 'profile', 'fines'],
    p_role_MEMBER => ['books', 'my-borrowings', 'my-fines', 'profile']
];

// Check if user has access to the requested page
if (!isset($allowedPages[$p_role]) || !in_array($page, $allowedPages[$p_role])) {
    $page = 'dashboard';
}

// Include header
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <?php 
                    $menu = getNavigationMenu();
                    foreach ($menu as $item): 
                        // Extract page name from URL if it exists, otherwise use the url as page
                        $itemPage = isset($item['url']) ? basename($item['url']) : $item['url'];
                    ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === $itemPage ? 'active' : ''; ?>" 
                               href="index.php?page=<?php echo $itemPage; ?>">
                                <i class="bi bi-<?php echo $item['icon']; ?> me-2"></i>
                                <?php echo $item['text']; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>Account</span>
                </h6>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page == 'profile' ? 'active' : ''; ?>" href="index.php?page=profile">
                            <i class="bi bi-person-circle me-2"></i>
                            My Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php
            // Load page content
            $pageFile = "pages/{$page}.php";
            
            // If page doesn't exist, show 404
            if (!file_exists($pageFile)) {
                $pageFile = "pages/404.php";
            }
            
            include $pageFile;
            ?>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>
