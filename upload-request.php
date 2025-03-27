<?php
include_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectTo('login.php');
}

$user_id = getCurrentUserId();
$errorMessage = '';
$successMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['author'], $_POST['pdf_url'])) {
    $conn = getConnection();
    
    // First ensure the pending_books table exists
    $sql = "CREATE TABLE IF NOT EXISTS pending_books (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        author VARCHAR(100) NOT NULL,
        description TEXT,
        file_url VARCHAR(255) NOT NULL,
        file_key VARCHAR(255) NOT NULL,
        file_size INT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        admin_notes TEXT,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->query($sql);
    
    $title = sanitizeInput($_POST['title']);
    $author = sanitizeInput($_POST['author']);
    $description = sanitizeInput($_POST['description'] ?? '');
    $pdfUrl = sanitizeInput($_POST['pdf_url']);
    $fileSize = intval($_POST['file_size'] ?? 0);
    
    if (empty($title) || empty($author) || empty($pdfUrl)) {
        $errorMessage = "Title, author, and PDF URL are required.";
    } else {
        // Generate a simple file key
        $fileKey = 'request_' . time() . '_' . substr(md5($pdfUrl), 0, 8);
        
        // Insert the request into the pending_books table
        $stmt = $conn->prepare("INSERT INTO pending_books (user_id, title, author, description, file_url, file_key, file_size) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssi", $user_id, $title, $author, $description, $pdfUrl, $fileKey, $fileSize);
        
        if ($stmt->execute()) {
            $successMessage = "Your book request has been submitted successfully! An administrator will review it shortly.";
            // Clear form data after successful submission
            $title = $author = $description = $pdfUrl = '';
            $fileSize = 0;
        } else {
            $errorMessage = "Error submitting book request: " . $conn->error;
        }
        
        $stmt->close();
    }
    
    $conn->close();
}

include_once 'header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2>Upload Book Request</h2>
            <p class="text-muted">Submit a book to be added to the library. An administrator will review your submission.</p>
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
                            <input type="text" class="form-control" id="title" name="title" required value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="author" class="form-label">Author <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="author" name="author" required value="<?php echo isset($author) ? htmlspecialchars($author) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="pdf_url" class="form-label">PDF URL <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="pdf_url" name="pdf_url" required value="<?php echo isset($pdfUrl) ? htmlspecialchars($pdfUrl) : ''; ?>" placeholder="https://uploadthing.com/f/...">
                            <div class="form-text">Enter the direct URL to the PDF file</div>
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
                            <input type="number" class="form-control" id="file_size" name="file_size" value="<?php echo isset($fileSize) ? $fileSize : 0; ?>" placeholder="Optional - Enter file size in bytes">
                            <div class="form-text">Optional: Enter the file size in bytes (e.g., 1048576 for 1MB)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <button type="submit" class="btn btn-success">Submit Book Request</button>
                            <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
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
                    <h6>How to submit a book:</h6>
                    <ol class="mb-4">
                        <li>Upload your PDF to uploadthing.com</li>
                        <li>Copy the direct PDF URL</li>
                        <li>Paste the URL in the form</li>
                        <li>Fill in the book details</li>
                        <li>Click "Submit Book Request" to send for review</li>
                    </ol>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Your submission will be reviewed by an administrator before being added to the library
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