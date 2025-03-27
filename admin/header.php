<?php
include_once 'auth.php';
requireAdminLogin();

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .admin-sidebar {
            background-color: #3D5A80;
            color: white;
            min-height: 100vh;
            padding-top: 20px;
        }
        .admin-sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 0;
            padding: 10px 20px;
            margin: 2px 0;
        }
        .admin-sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        .admin-sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 500;
        }
        .admin-sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .admin-header {
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            padding: 20px 0;
        }
        .admin-content {
            padding: 20px;
        }
        .admin-footer {
            padding: 15px 0;
            text-align: center;
            color: #6c757d;
            font-size: 0.85rem;
        }
        .admin-logo {
            font-weight: 700;
            color: white;
            font-size: 1.4rem;
            margin-bottom: 30px;
            padding: 0 20px;
        }
        .admin-user {
            margin-top: auto;
            padding: 15px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        .summary-card {
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        .summary-card:hover {
            transform: translateY(-5px);
        }
        .btn-admin {
            background-color: #3D5A80;
            border-color: #3D5A80;
            color: white;
        }
        .btn-admin:hover {
            background-color: #2C3E50;
            border-color: #2C3E50;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 p-0 admin-sidebar d-flex flex-column">
                <div class="admin-logo">
                    <img src="../book.svg" alt="Book Icon" style="height: 24px; margin-right: 8px; filter: brightness(0) invert(1);"> <?php echo SITE_NAME; ?>
                </div>
                
                <ul class="nav flex-column mb-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="index.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'books.php' ? 'active' : ''; ?>" href="books.php">
                            <i class="fas fa-book"></i> Manage Books
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'pending-books.php' ? 'active' : ''; ?>" href="pending-books.php">
                            <i class="fas fa-clipboard-check"></i> Book Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>" href="users.php">
                            <i class="fas fa-users"></i> Manage Users
                        </a>
                    </li>
                </ul>
                
                <div class="admin-user">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-user-circle me-2 fa-lg"></i>
                        <span><?php echo getAdminName(); ?></span>
                    </div>
                    <a href="logout.php" class="btn btn-outline-light btn-sm w-100">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto p-0 bg-white">
                <header class="admin-header">
                    <div class="container-fluid">
                        <div class="d-flex justify-content-between align-items-center">
                            <h1 class="h4 mb-0">
                                <?php 
                                    if ($current_page == 'index.php') echo "Dashboard";
                                    else if ($current_page == 'books.php') echo "Manage Books";
                                    else if ($current_page == 'users.php') echo "Manage Users";
                                    else if ($current_page == 'settings.php') echo "Settings";
                                    else if ($current_page == 'pending-books.php') echo "Book Requests";
                                    else echo "Admin Panel";
                                ?>
                            </h1>
                            <a href="../index.php" class="btn btn-sm btn-outline-secondary" target="_blank">
                                <i class="fas fa-external-link-alt me-1"></i> View Site
                            </a>
                        </div>
                    </div>
                </header>
                
                <main class="admin-content">
                    <!-- Main content will be here --> 