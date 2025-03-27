<?php
include_once '../config.php';
include_once 'auth.php';
include_once 'header.php';

// Check if user is logged in as admin
requireAdminLogin();

// Initialize variables
$errorMessage = '';
$successMessage = '';

// Check if book ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: books.php");
    exit;
}

$book_id = (int)$_GET['id'];
$conn = getConnection();

// Get book details
$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: books.php");
    exit;
}

$book = $result->fetch_assoc();
$stmt->close();

// Pre-fill form fields
$title = $book['title'];
$author = $book['author'];
$description = $book['description'];
$pdfUrl = $book['file_url'];
$fileSize = $book['file_size'];
$is_trending = $book['is_trending'];
$is_popular = $book['is_popular'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = sanitizeInput($_POST['title']);
    $author = sanitizeInput($_POST['author']);
    $description = sanitizeInput($_POST['description'] ?? '');
    $pdfUrl = sanitizeInput($_POST['pdf_url']);
    $fileSize = intval($_POST['file_size'] ?? 0);
    $is_trending = isset($_POST['is_trending']) ? 1 : 0;
    $is_popular = isset($_POST['is_popular']) ? 1 : 0;
    
    if (empty($title) || empty($author) || empty($pdfUrl)) {
        $errorMessage = "Title, author, and PDF URL are required.";
    } else {
        // Update the file key if URL has changed
        $fileKey = $book['file_key'];
        if ($pdfUrl !== $book['file_url']) {
            $fileKey = 'book_' . time() . '_' . substr(md5($pdfUrl), 0, 8);
        }
        
        $stmt = $conn->prepare("UPDATE books SET title = ?, author = ?, description = ?, file_url = ?, file_key = ?, file_size = ?, is_trending = ?, is_popular = ? WHERE id = ?");
        $stmt->bind_param("sssssiiis", $title, $author, $description, $pdfUrl, $fileKey, $fileSize, $is_trending, $is_popular, $book_id);
        
        if ($stmt->execute()) {
            $successMessage = "Book updated successfully!";
            
            // Refresh book data
            $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
            $stmt->bind_param("i", $book_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $book = $result->fetch_assoc();
        } else {
            $errorMessage = "Error updating book: " . $conn->error;
        }
        
        $stmt->close();
    }
}

$conn->close();
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2>Edit Book</h2>
            <p class="text-muted">Update the book details and PDF URL</p>
        </div>
    </div>
    
    <?php if (!empty($successMessage)): ?>
    <div class="alert alert-success">
        <?php echo $successMessage; ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger">
        <?php echo $errorMessage; ?>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Book Information</h5>
                </div>
                <div class="card-body">
                    <form id="bookForm" method="POST">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required value="<?php echo htmlspecialchars($title); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="author" class="form-label">Author <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="author" name="author" required value="<?php echo htmlspecialchars($author); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="pdf_url" class="form-label">PDF URL <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="pdf_url" name="pdf_url" required value="<?php echo htmlspecialchars($pdfUrl); ?>" placeholder="https://uploadthing.com/f/...">
                            <div class="form-text">Enter the direct URL to the PDF file</div>
                        </div>
                        
                        <!-- PDF Preview -->
                        <div class="mb-3" id="pdfPreviewContainer">
                            <label class="form-label">PDF Preview:</label>
                            <div style="width: 100%; height: 280px; border: 1px solid #ddd; overflow: hidden;">
                                <iframe id="pdfPreview" style="width: 100%; height: 100%;" frameborder="0" scrolling="no"></iframe>
                            </div>
                            <div class="form-text">This is how the thumbnail will appear to users</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="file_size" class="form-label">File Size (in bytes)</label>
                            <input type="number" class="form-control" id="file_size" name="file_size" value="<?php echo $fileSize; ?>" placeholder="Optional - Enter file size in bytes">
                            <div class="form-text">Optional: Enter the file size in bytes (e.g., 1048576 for 1MB)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($description); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="is_trending" name="is_trending" <?php echo $is_trending ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_trending">Feature in Trending Section</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_popular" name="is_popular" <?php echo $is_popular ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_popular">Feature in Popular Section</label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <button type="submit" class="btn btn-success">Update Book</button>
                            <a href="books.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Book Details</h5>
                </div>
                <div class="card-body">
                    <h6>Book Information:</h6>
                    <ul class="list-unstyled mb-4">
                        <li><strong>ID:</strong> <?php echo $book['id']; ?></li>
                        <li><strong>Added on:</strong> <?php echo date("F j, Y", strtotime($book['uploaded_at'])); ?></li>
                        <li><strong>File Key:</strong> <?php echo $book['file_key']; ?></li>
                    </ul>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        You can update the PDF URL to point to a new file if needed
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="../book.php?id=<?php echo $book_id; ?>" class="btn btn-outline-primary" target="_blank">
                            <i class="fas fa-eye me-1"></i> View Book Page
                        </a>
                        <a href="../read.php?id=<?php echo $book_id; ?>" class="btn btn-outline-success" target="_blank">
                            <i class="fas fa-book-reader me-1"></i> Read Book
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pdfUrlInput = document.getElementById('pdf_url');
    const pdfPreviewContainer = document.getElementById('pdfPreviewContainer');
    const pdfPreview = document.getElementById('pdfPreview');
    
    // Function to update PDF preview
    function updatePdfPreview() {
        const url = pdfUrlInput.value;
        if (url) {
            // Update preview - use direct PDF URL with page=1
            const baseUrl = url.split('#')[0]; // Remove any existing fragment
            pdfPreview.src = baseUrl + "#page=1";
            pdfPreviewContainer.style.display = 'block';
        } else {
            pdfPreviewContainer.style.display = 'none';
        }
    }
    
    // Update PDF preview when URL changes
    pdfUrlInput.addEventListener('change', updatePdfPreview);
    
    // Show preview on page load if URL is already set
    updatePdfPreview();
});
</script>

<?php include_once 'footer.php'; ?> 