<?php
include_once 'header.php';

// Get database connection
$conn = getConnection();

// Get total number of books
$books_query = "SELECT COUNT(*) as total FROM books";
$books_result = $conn->query($books_query);
$total_books = $books_result->fetch_assoc()['total'];

// Get total number of users
$users_query = "SELECT COUNT(*) as total FROM users";
$users_result = $conn->query($users_query);
$total_users = $users_result->fetch_assoc()['total'];

// Get total number of readings
$readings_query = "SELECT COUNT(*) as total FROM user_books";
$readings_result = $conn->query($readings_query);
$total_readings = $readings_result->fetch_assoc()['total'];

// Get total number of ratings
$ratings_query = "SELECT COUNT(*) as total FROM ratings";
$ratings_result = $conn->query($ratings_query);
$total_ratings = $ratings_result->fetch_assoc()['total'];

// Get recent users
$recent_users_query = "SELECT * FROM users ORDER BY created_at DESC LIMIT 5";
$recent_users_result = $conn->query($recent_users_query);
$recent_users = [];
while ($user = $recent_users_result->fetch_assoc()) {
    $recent_users[] = $user;
}

// Get recent books
$recent_books_query = "SELECT * FROM books ORDER BY created_at DESC LIMIT 5";
$recent_books_result = $conn->query($recent_books_query);
$recent_books = [];
while ($book = $recent_books_result->fetch_assoc()) {
    $recent_books[] = $book;
}

$conn->close();
?>

<div class="container-fluid">
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card summary-card bg-primary bg-opacity-10 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title mb-0">Total Books</h5>
                            <p class="display-6 fw-bold mb-0"><?php echo $total_books; ?></p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-book fa-3x text-primary opacity-50"></i>
                        </div>
                    </div>
                    <a href="books.php" class="btn btn-sm btn-primary mt-3">Manage Books</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card summary-card bg-success bg-opacity-10 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title mb-0">Total Users</h5>
                            <p class="display-6 fw-bold mb-0"><?php echo $total_users; ?></p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-3x text-success opacity-50"></i>
                        </div>
                    </div>
                    <a href="users.php" class="btn btn-sm btn-success mt-3">Manage Users</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card summary-card bg-info bg-opacity-10 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title mb-0">Total Readings</h5>
                            <p class="display-6 fw-bold mb-0"><?php echo $total_readings; ?></p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-book-reader fa-3x text-info opacity-50"></i>
                        </div>
                    </div>
                    <a href="#" class="btn btn-sm btn-info text-white mt-3">View Details</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card summary-card bg-warning bg-opacity-10 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title mb-0">Total Ratings</h5>
                            <p class="display-6 fw-bold mb-0"><?php echo $total_ratings; ?></p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-star fa-3x text-warning opacity-50"></i>
                        </div>
                    </div>
                    <a href="#" class="btn btn-sm btn-warning text-dark mt-3">View Ratings</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i> Recent Users</h5>
                </div>
                <div class="card-body">
                    <?php if (count($recent_users) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No users found.</p>
                    <?php endif; ?>
                    <a href="users.php" class="btn btn-sm btn-admin mt-2">View All Users</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-book me-2"></i> Recent Books</h5>
                </div>
                <div class="card-body">
                    <?php if (count($recent_books) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_books as $book): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($book['created_at'])); ?></td>
                                            <td>
                                                <a href="books.php?action=edit&id=<?php echo $book['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No books found.</p>
                    <?php endif; ?>
                    <a href="books.php" class="btn btn-sm btn-admin mt-2">View All Books</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?> 