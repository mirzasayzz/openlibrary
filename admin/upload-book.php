<?php
include_once '../config.php';
include_once 'auth.php';
include_once 'header.php';

// Check if user is logged in as admin
requireAdminLogin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['author'], $_POST['pdf_url'])) {
    $conn = getConnection();
    
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
        // Generate a simple file key
        $fileKey = 'book_' . time() . '_' . substr(md5($pdfUrl), 0, 8);
        $uploadedAt = date('Y-m-d H:i:s');
        
        // Ensure the file URL is properly formatted for thumbnails
        // Just store the raw URL - the #page=1 will be added when displaying
        
        $stmt = $conn->prepare("INSERT INTO books (title, author, description, file_url, file_key, file_size, uploaded_at, is_trending, is_popular) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssissi", $title, $author, $description, $pdfUrl, $fileKey, $fileSize, $uploadedAt, $is_trending, $is_popular);
        
        if ($stmt->execute()) {
            $successMessage = "Book added successfully!";
            // Clear form data after successful submission
            $title = $author = $description = $pdfUrl = '';
            $fileSize = 0;
        } else {
            $errorMessage = "Error adding book: " . $conn->error;
        }
        
        $stmt->close();
    }
    
    $conn->close();
}
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2>Add New Book</h2>
            <p class="text-muted">Add a book by providing its details and PDF URL from uploadthing.com</p>
        </div>
    </div>
    
    <?php if (isset($successMessage)): ?>
    <div class="alert alert-success">
        <?php echo $successMessage; ?>
    </div>
    <?php endif; ?>
    
    <?php if (isset($errorMessage)): ?>
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
                            <input type="text" class="form-control" id="title" name="title" required value="<?php echo $title ?? ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="author" class="form-label">Author <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="author" name="author" required value="<?php echo $author ?? ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="pdf_url" class="form-label">PDF URL <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="pdf_url" name="pdf_url" required value="<?php echo $pdfUrl ?? ''; ?>" placeholder="https://uploadthing.com/f/...">
                            <div class="form-text">Enter the direct URL to the PDF file from uploadthing.com</div>
                        </div>
                        
                        <!-- PDF Preview -->
                        <div class="mb-3" id="pdfPreviewContainer" style="display: none;">
                            <label class="form-label">PDF Preview:</label>
                            <div style="width: 100%; height: 280px; border: 1px solid #ddd; overflow: hidden;">
                                <iframe id="pdfPreview" style="width: 100%; height: 100%;" frameborder="0" scrolling="no"></iframe>
                            </div>
                            <div class="form-text">This is how the thumbnail will appear to users</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="file_size" class="form-label">File Size (in bytes)</label>
                            <input type="number" class="form-control" id="file_size" name="file_size" value="<?php echo $fileSize ?? 0; ?>" placeholder="Optional - Enter file size in bytes">
                            <div class="form-text">Optional: Enter the file size in bytes (e.g., 1048576 for 1MB)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo $description ?? ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="is_trending" name="is_trending" <?php echo isset($is_trending) && $is_trending ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_trending">Feature in Trending Section</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_popular" name="is_popular" <?php echo isset($is_popular) && $is_popular ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_popular">Feature in Popular Section</label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <button type="submit" class="btn btn-success">Add Book</button>
                            <a href="books.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Instructions</h5>
                </div>
                <div class="card-body">
                    <h6>How to add a book:</h6>
                    <ol class="mb-4">
                        <li>Upload your PDF to uploadthing.com</li>
                        <li>Copy the direct PDF URL</li>
                        <li>Paste the URL in the form</li>
                        <li>Fill in the book details</li>
                        <li>Click "Add Book" to save</li>
                    </ol>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Make sure the PDF URL is publicly accessible and from uploadthing.com
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Maximum recommended PDF size: 20MB
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const titleInput = document.getElementById('title');
    const authorInput = document.getElementById('author');
    const pdfUrlInput = document.getElementById('pdf_url');
    const pdfPreviewContainer = document.getElementById('pdfPreviewContainer');
    const pdfPreview = document.getElementById('pdfPreview');
    
    // Update PDF preview when URL changes
    pdfUrlInput.addEventListener('change', function() {
        const url = pdfUrlInput.value;
        if (url) {
            // Extract filename from URL
            const filename = url.split('/').pop().replace('.pdf', '');
            
            // Try to parse title and author from filename
            const parts = filename.split(/[-_,]/);
            if (parts.length > 1) {
                // If filename has a separator, use first part as author and rest as title
                const author = parts[0].trim();
                const title = parts.slice(1).join(' ').trim();
                
                if (!titleInput.value) titleInput.value = title;
                if (!authorInput.value) authorInput.value = author;
            }
            
            // Update preview - use direct PDF URL with page=1
            const baseUrl = url.split('#')[0]; // Remove any existing fragment
            pdfPreview.src = baseUrl + "#page=1";
            pdfPreviewContainer.style.display = 'block';
        } else {
            pdfPreviewContainer.style.display = 'none';
        }
    });
    
    // Show preview on page load if URL is already set
    if (pdfUrlInput.value) {
        const baseUrl = pdfUrlInput.value.split('#')[0]; // Remove any existing fragment
        pdfPreview.src = baseUrl + "#page=1";
        pdfPreviewContainer.style.display = 'block';
    }
});
</script>

<?php include_once 'footer.php'; ?> 