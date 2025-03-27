<?php
include_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectTo('login.php');
}

$user_id = getCurrentUserId();
$conn = getConnection();

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

// Get filter from URL
$filter = isset($_GET['filter']) && in_array($_GET['filter'], ['to_read', 'reading', 'read']) ? 
    $_GET['filter'] : '';

// Get user's books
$sql = "SELECT b.*, ub.status, 
        (SELECT AVG(r.rating) FROM ratings r WHERE r.book_id = b.id) as avg_rating,
        (SELECT COUNT(r.id) FROM ratings r WHERE r.book_id = b.id) as rating_count
        FROM books b 
        JOIN user_books ub ON b.id = ub.book_id 
        WHERE ub.user_id = ?";

if (!empty($filter)) {
    $sql .= " AND ub.status = ?";
}

$sql .= " ORDER BY b.title ASC";
$stmt = $conn->prepare($sql);

if (!empty($filter)) {
    $stmt->bind_param("is", $user_id, $filter);
} else {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
$books = [];

while ($book = $result->fetch_assoc()) {
    $books[] = $book;
}

$stmt->close();
$conn->close();

include_once 'header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="section-title mb-4">My Books</h2>
        
        <!-- Filter Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?php echo empty($filter) ? 'active' : ''; ?>" href="my-books.php">
                    All Books (<?php echo count($books); ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $filter === 'to_read' ? 'active' : ''; ?>" href="my-books.php?filter=to_read">
                    Want to Read
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $filter === 'reading' ? 'active' : ''; ?>" href="my-books.php?filter=reading">
                    Currently Reading
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $filter === 'read' ? 'active' : ''; ?>" href="my-books.php?filter=read">
                    Read
                </a>
            </li>
        </ul>
        
        <?php if (count($books) > 0): ?>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($books as $book): ?>
                    <div class="col">
                        <div class="card book-card h-100">
                            <div class="position-absolute badge <?php 
                                echo ($book['status'] === 'to_read') ? 'bg-secondary' : 
                                      (($book['status'] === 'reading') ? 'bg-warning' : 'bg-success'); 
                                ?> m-2">
                                <?php 
                                    echo ($book['status'] === 'to_read') ? 'Want to Read' : 
                                          (($book['status'] === 'reading') ? 'Reading' : 'Read'); 
                                ?>
                            </div>
                            <div class="book-img-container">
                                <?php if (!empty($book['file_url'])): ?>
                                    <?php $pdfThumbnail = generatePdfThumbnail($book['file_url']); ?>
                                    <iframe src="<?php echo $pdfThumbnail; ?>" class="book-img" frameborder="0" scrolling="no"></iframe>
                                <?php else: ?>
                                    <?php $cover = !empty($book['cover_image']) ? $book['cover_image'] : generateCoverUrl($book['title'], $book['author']); ?>
                                    <img src="<?php echo $cover; ?>" class="book-img" alt="<?php echo $book['title']; ?>">
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h5 class="book-title"><?php echo $book['title']; ?></h5>
                                <p class="book-author">by <?php echo $book['author']; ?></p>
                                
                                <!-- Rating Display -->
                                <div class="small mb-2">
                                    <div class="stars">
                                        <?php 
                                        $avg_rating = round($book['avg_rating'], 1);
                                        for ($i = 1; $i <= 5; $i++): 
                                        ?>
                                            <i class="fas fa-star <?php echo ($i <= $avg_rating) ? 'text-warning' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                        <?php if ($book['rating_count'] > 0): ?>
                                            <span class="ms-1">(<?php echo $book['rating_count']; ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <a href="book.php?id=<?php echo $book['id']; ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                                    <a href="read.php?id=<?php echo $book['id']; ?>" class="btn btn-primary btn-sm">Read Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <?php if (empty($filter)): ?>
                    You haven't added any books to your collection yet. <a href="search.php">Browse our library</a> to find books.
                <?php else: ?>
                    No books found in this category. <a href="search.php">Browse our library</a> to find books.
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once 'footer.php'; ?> 