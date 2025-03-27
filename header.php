<?php
include_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        .navbar {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        .navbar-brand {
            font-weight: 700;
            color: #3D5A80;
        }
        .search-form {
            width: 100%;
            max-width: 400px;
        }
        .book-card {
            transition: transform 0.3s ease;
            height: 100%;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,.1);
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,.15);
        }
        .book-img-container {
            height: 280px;
            overflow: hidden;
            background-color: #eee;
            position: relative;
        }
        .book-img {
            object-fit: cover;
            width: 100%;
            height: 100%;
        }
        
        /* Ensure PDF thumbnails display correctly */
        .book-img-container iframe {
            pointer-events: none; /* Prevents interaction/scrolling */
            overflow: hidden;
            display: block;
            border: none;
            background-color: white; /* Add white background */
        }
        
        /* Fix for PDF thumbnails in some browsers */
        .book-img-container iframe {
            width: 100% !important;
            height: 100% !important;
        }
        
        /* Create a blocking overlay to prevent interaction with PDF thumbnails */
        .book-img-container::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: transparent;
            z-index: 10; /* Put this above the iframe to block interactions */
        }
        
        .book-title {
            font-weight: 600;
            margin-top: 10px;
            height: 50px;
            overflow: hidden;
        }
        .book-author {
            color: #666;
            font-size: 0.9rem;
        }
        .section-title {
            border-left: 4px solid #3D5A80;
            padding-left: 10px;
            margin: 30px 0 20px;
        }
        .btn-primary {
            background-color: #3D5A80;
            border-color: #3D5A80;
        }
        .btn-primary:hover {
            background-color: #2C3E50;
            border-color: #2C3E50;
        }
        .btn-outline-primary {
            color: #3D5A80;
            border-color: #3D5A80;
        }
        .btn-outline-primary:hover {
            background-color: #3D5A80;
            border-color: #3D5A80;
        }
        /* Star Rating Styles */
        .rating-input {
            display: inline-flex;
            flex-direction: row-reverse;
            border-radius: 0.25rem;
        }
        .rating-input input {
            display: none;
        }
        .rating-input label {
            cursor: pointer;
            font-size: 1.5rem;
            padding: 0 0.1rem;
            color: #ccc;
        }
        .rating-input label:hover,
        .rating-input label:hover ~ label,
        .rating-input input:checked ~ label {
            color: #FFD700;
        }
        .stars .fa-star {
            font-size: 1rem;
            padding: 0 0.1rem;
        }
        .text-warning {
            color: #FFD700 !important;
        }
        .book-detail-cover {
            width: 100%;
            height: 400px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top py-3">
        <div class="container">
            <a class="navbar-brand" href="index.php"><img src="book.svg" alt="Book Icon" style="height: 24px; margin-right: 6px;"> <?php echo SITE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <form class="search-form mx-auto d-flex" action="search.php" method="GET">
                    <input class="form-control" type="search" name="q" placeholder="Search books..." aria-label="Search">
                    <button class="btn btn-outline-primary" type="submit"><i class="fas fa-search"></i></button>
                </form>
                
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item my-1">
                        <a class="btn btn-sm btn-outline-secondary me-3" href="admin/index.php">
                            <i class="fas fa-user-shield"></i> Admin
                        </a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item me-3 my-1">
                            <a class="btn btn-sm btn-outline-primary" href="my-books.php">
                                <i class="fas fa-book-open me-1"></i> My Books
                            </a>
                        </li>
                        <li class="nav-item me-3 my-1">
                            <a class="btn btn-sm btn-outline-success" href="upload-request.php">
                                <i class="fas fa-upload me-1"></i> Upload Book
                            </a>
                        </li>
                        <li class="nav-item dropdown my-1">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> <?php echo getUserName(); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="my-books.php">My Books</a></li>
                                <li><a class="dropdown-item" href="upload-request.php"><i class="fas fa-upload me-1"></i> Upload Book</a></li>
                                <?php if (isAdmin()): ?>
                                <li><a class="dropdown-item" href="admin/index.php">
                                    <i class="fas fa-user-shield"></i> Admin Panel
                                </a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item me-3 my-1">
                            <a class="btn btn-sm btn-outline-primary" href="login.php">Login</a>
                        </li>
                        <li class="nav-item my-1">
                            <a class="btn btn-sm btn-primary" href="register.php">Sign Up</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4"> 