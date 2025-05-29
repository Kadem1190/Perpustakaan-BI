<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-body text-center">
                        <h1 class="display-1 text-danger">403</h1>
                        <h4>Unauthorized Access</h4>
                        <p class="text-muted">You don't have permission to access this page.</p>
                        <a href="index.php" class="btn btn-primary">Go to Dashboard</a>
                        <a href="logout.php" class="btn btn-secondary">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
