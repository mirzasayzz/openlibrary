<?php
include_once 'config.php';
include_once 'header.php';

// Generate book cover URL based on title and author (for demo purposes - fallback only)
function generateCoverUrl($title, $author) {
    $seed = md5($title . $author);
    $color = substr($seed, 0, 6);
    return "https://via.placeholder.com/400x600/$color/ffffff?text=" . urlencode(substr($title, 0, 20));
}

// Generate PDF thumbnail - direct from the PDF URL
function generatePdfThumbnail($fileUrl) {
    // Clean the URL first (remove any existing fragments)
    $baseUrl = strtok($fileUrl, '#');
    // Add #page=1 parameter to ensure we only show the first page
    return $baseUrl . "#page=1";
}

// Check if book ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<div class="alert alert-danger">Invalid book ID.</div>';
    include_once 'footer.php';
    exit;
}

$book_id = (int)$_GET['id'];
$conn = getConnection();

// Get book details
$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if book exists
if ($result->num_rows === 0) {
    echo '<div class="alert alert-danger">Book not found.</div>';
    $stmt->close();
    $conn->close();
    include_once 'footer.php';
    exit;
}

$book = $result->fetch_assoc();
// Check if we can use PDF thumbnail, otherwise use cover image or placeholder
$use_pdf_thumbnail = !empty($book['file_url']);
$cover = !empty($book['cover_image']) ? $book['cover_image'] : generateCoverUrl($book['title'], $book['author']);
$pdf_thumbnail = $use_pdf_thumbnail ? generatePdfThumbnail($book['file_url']) : '';

// Get book status for the current user if logged in
$user_book_status = 'to_read';
$user_rating = null;
$user_comment = '';

if (isLoggedIn()) {
    $user_id = getCurrentUserId();
    
    // Get book status
    $status_stmt = $conn->prepare("SELECT status FROM user_books WHERE user_id = ? AND book_id = ?");
    $status_stmt->bind_param("ii", $user_id, $book_id);
    $status_stmt->execute();
    $status_result = $status_stmt->get_result();
    
    if ($status_result->num_rows > 0) {
        $user_book = $status_result->fetch_assoc();
        $user_book_status = $user_book['status'];
    }
    $status_stmt->close();
    
    // Get user's rating and comment
    $rating_stmt = $conn->prepare("SELECT rating, comment FROM ratings WHERE user_id = ? AND book_id = ?");
    $rating_stmt->bind_param("ii", $user_id, $book_id);
    $rating_stmt->execute();
    $rating_result = $rating_stmt->get_result();
    
    if ($rating_result->num_rows > 0) {
        $rating_data = $rating_result->fetch_assoc();
        $user_rating = $rating_data['rating'];
        $user_comment = $rating_data['comment'];
    }
    $rating_stmt->close();
}

// Get average rating
$avg_rating = 0;
$rating_count = 0;
$rating_stmt = $conn->prepare("SELECT AVG(rating) as average, COUNT(*) as count FROM ratings WHERE book_id = ?");
$rating_stmt->bind_param("i", $book_id);
$rating_stmt->execute();
$rating_result = $rating_stmt->get_result();
$rating_data = $rating_result->fetch_assoc();
$avg_rating = round($rating_data['average'], 1);
$rating_count = $rating_data['count'];
$rating_stmt->close();

// Process book status change
if (isLoggedIn() && isset($_POST['status']) && in_array($_POST['status'], ['to_read', 'reading', 'read'])) {
    $new_status = $_POST['status'];
    $user_id = getCurrentUserId();
    
    // Check if user_book record exists
    $check_stmt = $conn->prepare("SELECT id FROM user_books WHERE user_id = ? AND book_id = ?");
    $check_stmt->bind_param("ii", $user_id, $book_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing record
        $update_stmt = $conn->prepare("UPDATE user_books SET status = ? WHERE user_id = ? AND book_id = ?");
        $update_stmt->bind_param("sii", $new_status, $user_id, $book_id);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        // Insert new record
        $insert_stmt = $conn->prepare("INSERT INTO user_books (user_id, book_id, status) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("iis", $user_id, $book_id, $new_status);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    
    $check_stmt->close();
    $user_book_status = $new_status;
}

// Process rating submission
if (isLoggedIn() && isset($_POST['rating']) && isset($_POST['comment'])) {
    $new_rating = max(1, min(5, (int)$_POST['rating'])); // Ensure rating is between 1-5
    $new_comment = sanitizeInput($_POST['comment']);
    $user_id = getCurrentUserId();
    
    // Check if rating exists
    $check_stmt = $conn->prepare("SELECT id FROM ratings WHERE user_id = ? AND book_id = ?");
    $check_stmt->bind_param("ii", $user_id, $book_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing rating
        $update_stmt = $conn->prepare("UPDATE ratings SET rating = ?, comment = ? WHERE user_id = ? AND book_id = ?");
        $update_stmt->bind_param("isii", $new_rating, $new_comment, $user_id, $book_id);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        // Insert new rating
        $insert_stmt = $conn->prepare("INSERT INTO ratings (user_id, book_id, rating, comment) VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("iiis", $user_id, $book_id, $new_rating, $new_comment);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    
    $check_stmt->close();
    
    // Update user's rating and comment variables
    $user_rating = $new_rating;
    $user_comment = $new_comment;
    
    // Update average rating
    $rating_stmt = $conn->prepare("SELECT AVG(rating) as average, COUNT(*) as count FROM ratings WHERE book_id = ?");
    $rating_stmt->bind_param("i", $book_id);
    $rating_stmt->execute();
    $rating_result = $rating_stmt->get_result();
    $rating_data = $rating_result->fetch_assoc();
    $avg_rating = round($rating_data['average'], 1);
    $rating_count = $rating_data['count'];
    $rating_stmt->close();
}

// Get all ratings for the book
$all_ratings = [];
$ratings_stmt = $conn->prepare("
    SELECT r.rating, r.comment, r.created_at, u.name 
    FROM ratings r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.book_id = ? 
    ORDER BY r.created_at DESC
");
$ratings_stmt->bind_param("i", $book_id);
$ratings_stmt->execute();
$ratings_result = $ratings_stmt->get_result();

while ($rating = $ratings_result->fetch_assoc()) {
    $all_ratings[] = $rating;
}
$ratings_stmt->close();
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="book-img-container book-detail-img">
                <?php if ($use_pdf_thumbnail): ?>
                    <iframe src="<?php echo $pdf_thumbnail; ?>" class="book-detail-cover" frameborder="0"></iframe>
                <?php else: ?>
                    <img src="<?php echo $cover; ?>" class="book-detail-cover" alt="<?php echo $book['title']; ?>">
                <?php endif; ?>
            </div>
            
            <?php if (isLoggedIn()): ?>
                <!-- Status Form -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h5 class="card-title">My Reading Status</h5>
                        <form action="book.php?id=<?php echo $book_id; ?>" method="POST">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="status" id="status_to_read" value="to_read" <?php echo $user_book_status === 'to_read' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="status_to_read">Want to Read</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="status" id="status_reading" value="reading" <?php echo $user_book_status === 'reading' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="status_reading">Currently Reading</label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="status" id="status_read" value="read" <?php echo $user_book_status === 'read' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="status_read">Read</label>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Update Status</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-8">
            <h1 class="mb-2"><?php echo htmlspecialchars($book['title']); ?></h1>
            <h5 class="text-muted mb-4">by <?php echo htmlspecialchars($book['author']); ?></h5>
            
            <!-- Average Rating Display -->
            <div class="mb-3">
                <div class="d-flex align-items-center">
                    <div class="stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo ($i <= $avg_rating) ? 'text-warning' : 'text-muted'; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="ms-2"><?php echo $avg_rating; ?> out of 5 (<?php echo $rating_count; ?> <?php echo ($rating_count == 1) ? 'rating' : 'ratings'; ?>)</span>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Description</h5>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Book Details</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>File Size:</strong> <?php echo round($book['file_size'] / (1024 * 1024), 2); ?> MB</p>
                            <p><strong>Uploaded:</strong> <?php echo date("F j, Y", strtotime($book['uploaded_at'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <?php if ($book['is_trending']): ?>
                                <span class="badge bg-success mb-2">Trending</span>
                            <?php endif; ?>
                            <?php if ($book['is_popular']): ?>
                                <span class="badge bg-primary mb-2">Popular</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (isLoggedIn()): ?>
                <!-- Rating Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Rate This Book</h5>
                        <form action="book.php?id=<?php echo $book_id; ?>" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Your Rating</label>
                                <div class="rating-input">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php echo ($user_rating == $i) ? 'checked' : ''; ?>>
                                        <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="comment" class="form-label">Your Review</label>
                                <textarea class="form-control" id="comment" name="comment" rows="3"><?php echo htmlspecialchars($user_comment); ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Rating</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info mb-4">
                    <a href="login.php">Log in</a> or <a href="register.php">sign up</a> to rate this book.
                </div>
            <?php endif; ?>
            
            <!-- All Ratings Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Reviews (<?php echo count($all_ratings); ?>)</h5>
                    
                    <?php if (empty($all_ratings)): ?>
                        <p class="text-muted">No reviews yet. Be the first to review this book!</p>
                    <?php else: ?>
                        <?php foreach ($all_ratings as $rating): ?>
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between">
                                    <h6><?php echo htmlspecialchars($rating['name']); ?></h6>
                                    <small class="text-muted"><?php echo date("M j, Y", strtotime($rating['created_at'])); ?></small>
                                </div>
                                <div class="stars mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo ($i <= $rating['rating']) ? 'text-warning' : 'text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <p><?php echo nl2br(htmlspecialchars($rating['comment'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (isLoggedIn()): ?>
                <div class="mt-4 mb-4">
                    <a href="read.php?id=<?php echo $book_id; ?>" class="btn btn-primary">
                        <i class="fas fa-book-reader"></i> Read Now
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$stmt->close();
$conn->close();
include_once 'footer.php';
?> 