<?php
include_once 'config.php';
include_once 'header.php';

// Get trending books
$conn = getConnection();
$trending_query = "SELECT b.*, 
                  (SELECT AVG(r.rating) FROM ratings r WHERE r.book_id = b.id) as avg_rating,
                  (SELECT COUNT(r.id) FROM ratings r WHERE r.book_id = b.id) as rating_count
                  FROM books b WHERE b.is_trending = 1 LIMIT 8";
$trending_result = $conn->query($trending_query);

// Get popular books
$popular_query = "SELECT b.*, 
                 (SELECT AVG(r.rating) FROM ratings r WHERE r.book_id = b.id) as avg_rating,
                 (SELECT COUNT(r.id) FROM ratings r WHERE r.book_id = b.id) as rating_count
                 FROM books b WHERE b.is_popular = 1 LIMIT 8";
$popular_result = $conn->query($popular_query);

// Generate book cover URL based on title and author (for demo purposes)
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
?>

<!-- Hero Section -->
<div class="bg-light py-5 mb-5 rounded-3">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-5 fw-bold mb-3">Welcome to Open Library</h1>
                <p class="fs-4">Discover a world of knowledge with our free ebooks collection.</p>
                <p class="mb-4">Browse our collection of free books, sign up to track your reading progress, and enjoy reading from anywhere.</p>
                <?php if (!isLoggedIn()): ?>
                    <a href="register.php" class="btn btn-primary btn-lg px-4 me-2">Sign Up</a>
                    <a href="login.php" class="btn btn-outline-primary btn-lg px-4">Login</a>
                <?php else: ?>
                    <a href="search.php" class="btn btn-primary btn-lg px-4">Browse All Books</a>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <img src="https://images.unsplash.com/photo-1507842217343-583bb7270b66?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80" alt="Books" class="img-fluid rounded-3 shadow">
            </div>
        </div>
    </div>
</div>

<!-- Trending Books Section -->
<section class="my-5">
    <h2 class="section-title mb-4">Trending Books</h2>
    <?php if ($trending_result->num_rows > 0): ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php while ($book = $trending_result->fetch_assoc()): ?>
                <div class="col">
                    <div class="card book-card h-100">
                        <div class="book-img-container">
                            <?php if (!empty($book['file_url'])): ?>
                                <?php 
                                    $pdfThumbnail = generatePdfThumbnail($book['file_url']); 
                                    // Add timestamp to prevent caching issues
                                    $pdfThumbnail .= "&t=" . time();
                                ?>
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
                                <?php if (isLoggedIn()): ?>
                                    <a href="read.php?id=<?php echo $book['id']; ?>" class="btn btn-primary btn-sm">Read Now</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No trending books found.</div>
    <?php endif; ?>
</section>

<!-- Popular Books Section -->
<section class="my-5">
    <h2 class="section-title mb-4">Popular Books</h2>
    <?php if ($popular_result->num_rows > 0): ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php while ($book = $popular_result->fetch_assoc()): ?>
                <div class="col">
                    <div class="card book-card h-100">
                        <div class="book-img-container">
                            <?php if (!empty($book['file_url'])): ?>
                                <?php 
                                    $pdfThumbnail = generatePdfThumbnail($book['file_url']); 
                                    // Add timestamp to prevent caching issues
                                    $pdfThumbnail .= "&t=" . time();
                                ?>
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
                                <?php if (isLoggedIn()): ?>
                                    <a href="read.php?id=<?php echo $book['id']; ?>" class="btn btn-primary btn-sm">Read Now</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No popular books found.</div>
    <?php endif; ?>
</section>

<?php include_once 'footer.php'; ?> 