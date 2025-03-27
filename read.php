<?php
include_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectTo('login.php');
}

// Check if book ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Invalid book ID.';
    redirectTo('index.php');
}

$book_id = (int)$_GET['id'];
$user_id = getCurrentUserId();
$conn = getConnection();

// Get book details
$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if book exists
if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Book not found.';
    $stmt->close();
    $conn->close();
    redirectTo('index.php');
}

$book = $result->fetch_assoc();

// Update or create user_book record to mark as 'reading'
$check_stmt = $conn->prepare("SELECT id FROM user_books WHERE user_id = ? AND book_id = ?");
$check_stmt->bind_param("ii", $user_id, $book_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // Update existing record
    $update_stmt = $conn->prepare("UPDATE user_books SET status = 'reading' WHERE user_id = ? AND book_id = ?");
    $update_stmt->bind_param("ii", $user_id, $book_id);
    $update_stmt->execute();
    $update_stmt->close();
} else {
    // Insert new record
    $insert_stmt = $conn->prepare("INSERT INTO user_books (user_id, book_id, status) VALUES (?, ?, 'reading')");
    $insert_stmt->bind_param("ii", $user_id, $book_id);
    $insert_stmt->execute();
    $insert_stmt->close();
}

$check_stmt->close();
$stmt->close();
$conn->close();

include_once 'header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h1><?php echo htmlspecialchars($book['title']); ?></h1>
            <a href="book.php?id=<?php echo $book_id; ?>" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Book Details
            </a>
        </div>
        <h5 class="text-muted">by <?php echo htmlspecialchars($book['author']); ?></h5>
    </div>
    
    <div class="col-12">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="ratio ratio-16x9">
                    <iframe src="<?php echo htmlspecialchars($book['file_url']); ?>" allowfullscreen></iframe>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-between">
            <a href="my-books.php" class="btn btn-outline-primary">
                <i class="fas fa-list"></i> My Books
            </a>
            
            <form method="POST" action="book.php?id=<?php echo $book_id; ?>">
                <input type="hidden" name="status" value="read">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check"></i> Mark as Read
                </button>
            </form>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?> 