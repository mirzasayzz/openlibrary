<?php
include_once 'config.php';
include_once 'header.php';

$search_query = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
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

// Search books
$books = [];
if (!empty($search_query)) {
    $sql = "SELECT b.*, 
           (SELECT AVG(r.rating) FROM ratings r WHERE r.book_id = b.id) as avg_rating,
           (SELECT COUNT(r.id) FROM ratings r WHERE r.book_id = b.id) as rating_count
           FROM books b 
           WHERE b.title LIKE ? OR b.author LIKE ? 
           ORDER BY b.title ASC";
    $stmt = $conn->prepare($sql);
    $search_param = "%" . $search_query . "%";
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($book = $result->fetch_assoc()) {
        $books[] = $book;
    }
    $stmt->close();
} else {
    // If no search query, show all books
    $sql = "SELECT b.*, 
           (SELECT AVG(r.rating) FROM ratings r WHERE r.book_id = b.id) as avg_rating,
           (SELECT COUNT(r.id) FROM ratings r WHERE r.book_id = b.id) as rating_count
           FROM books b ORDER BY b.title ASC";
    $result = $conn->query($sql);
    
    while ($book = $result->fetch_assoc()) {
        $books[] = $book;
    }
}
?>

<div class="row">
    <div class="col-12">
        <h2 class="section-title mb-4"><?php echo empty($search_query) ? 'All Books' : 'Search Results for "' . htmlspecialchars($search_query) . '"'; ?></h2>
        
        <!-- Search Form -->
        <form action="search.php" method="GET" class="mb-4">
            <div class="input-group mb-3">
                <input type="text" class="form-control" placeholder="Search by title or author..." name="q" value="<?php echo htmlspecialchars($search_query); ?>">
                <button class="btn btn-primary" type="submit">Search</button>
            </div>
        </form>
        
        <?php if (count($books) > 0): ?>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($books as $book): ?>
                    <div class="col">
                        <div class="card book-card h-100">
                            <div class="book-img-container">
                                <?php 
                                    $pdfThumbnail = generatePdfThumbnail($book['file_url']); 
                                    // Add timestamp to prevent caching issues
                                    $pdfThumbnail .= "&t=" . time();
                                ?>
                                <iframe src="<?php echo $pdfThumbnail; ?>" class="book-img" frameborder="0" scrolling="no"></iframe>
                            </div>
                            <div class="card-body">
                                <h5 class="book-title"><?php echo $book['title']; ?></h5>
                                <p class="book-author">by <?php echo $book['author']; ?></p>
                                
                                <!-- Add display of file URL for debugging -->
                                <?php if (isset($_GET['debug'])): ?>
                                <div class="small text-muted mb-2 overflow-hidden" style="max-height: 60px; font-size: 10px;">
                                    URL: <?php echo htmlspecialchars($book['file_url']); ?><br>
                                    Thumbnail: <?php echo htmlspecialchars($pdfThumbnail); ?>
                                </div>
                                <?php endif; ?>

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
                                    <?php if (isLoggedIn()): ?>
                                        <a href="read.php?id=<?php echo $book['id']; ?>" class="btn btn-primary btn-sm">Read Now</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No books found for your search query. Please try a different search term.</div>
        <?php endif; ?>
    </div>
</div>

<?php 
$conn->close();
include_once 'footer.php'; 
?> 