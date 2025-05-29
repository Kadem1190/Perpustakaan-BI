<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php if (isset($_SESSION['admin_id'])): ?>
    <header class="navbar navbar-dark sticky-top bg-primary flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="index.php">
            <i class="bi bi-book me-2"></i>
            Library Admin
        </a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="w-100"></div>
        <div class="navbar-nav">
            <div class="nav-item text-nowrap d-flex align-items-center">
                <span class="nav-link px-3 text-white">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_username']); ?>
                </span>
                <a class="nav-link px-3" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    Sign out
                </a>
            </div>
        </div>
    </header>
    <?php endif; ?>
    <?php if (isset($_SESSION['user_id'])): ?>
    <header class="navbar navbar-light sticky-top bg-light flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="member_dashboard.php">
            <i class="bi bi-book me-2"></i>
            Library Member
        </a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="w-100"></div>
        <div class="navbar-nav">
            <div class="nav-item text-nowrap d-flex align-items-center">
                <span class="nav-link px-3">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>
                </span>
                <a class="nav-link px-3" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    Sign out
                </a>
            </div>
        </div>
    </header>
    <?php endif; ?>
