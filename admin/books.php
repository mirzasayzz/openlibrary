<?php
include_once 'header.php';

// Get database connection
$conn = getConnection();

// Handle form submissions for delete
$success_message = '';
$error_message = '';

// Delete book
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $book_id = (int)$_GET['id'];
    
    // First delete any associated records in user_books
    $stmt = $conn->prepare("DELETE FROM user_books WHERE book_id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $stmt->close();
    
    // Then delete any associated ratings
    $stmt = $conn->prepare("DELETE FROM ratings WHERE book_id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $stmt->close();
    
    // Finally delete the book
    $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    
    if ($stmt->execute()) {
        $success_message = "Book deleted successfully.";
    } else {
        $error_message = "Error deleting book: " . $stmt->error;
    }
    
    $stmt->close();
}

// Get all books for listing
$search = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = sanitizeInput($_GET['search']);
}

$query = "SELECT * FROM books";
if (!empty($search)) {
    $query .= " WHERE title LIKE ? OR author LIKE ?";
}
$query .= " ORDER BY title ASC";

if (!empty($search)) {
    $stmt = $conn->prepare($query);
    $search_param = "%" . $search . "%";
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

$books = [];
while ($row = $result->fetch_assoc()) {
    $books[] = $row;
}

if (isset($stmt)) {
    $stmt->close();
}
?>

<!-- Messages -->
<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Books Listing -->
<div class="card">
    <div class="card-header bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-book me-2"></i> Manage Books</h5>
            <div>
                <form action="books.php" method="GET" class="d-flex">
                    <input type="text" class="form-control form-control-sm me-2" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-sm btn-primary">Search</button>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>File Size</th>
                        <th>Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($books) > 0): ?>
                        <?php foreach ($books as $index => $book): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td>
                                    <?php if ($book['is_trending']): ?>
                                        <span class="badge bg-success">Trending</span>
                                    <?php endif; ?>
                                    <?php if ($book['is_popular']): ?>
                                        <span class="badge bg-primary">Popular</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo round($book['file_size'] / (1024 * 1024), 2); ?> MB</td>
                                <td><?php echo date("M d, Y", strtotime($book['uploaded_at'])); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit-book.php?id=<?php echo $book['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="../book.php?id=<?php echo $book['id']; ?>" class="btn btn-info" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="books.php?action=delete&id=<?php echo $book['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this book?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No books found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <span>Total Books: <?php echo count($books); ?></span>
            <a href="upload-book.php" class="btn btn-sm btn-success">
                <i class="fas fa-plus me-1"></i> Add New Book
            </a>
        </div>
    </div>
</div>

<?php 
$conn->close();
include_once 'footer.php';
?> 