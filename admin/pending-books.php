<?php
include_once 'header.php';

// Get database connection
$conn = getConnection();

// Handle form submissions for approve/reject
$success_message = '';
$error_message = '';

// Process action (approve/reject)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $request_id = (int)$_GET['id'];
    $action = $_GET['action'];
    $admin_notes = isset($_POST['admin_notes']) ? sanitizeInput($_POST['admin_notes']) : '';
    
    // Ensure the pending_books table exists
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
    
    if ($action === 'approve') {
        // Get the book request details
        $stmt = $conn->prepare("SELECT * FROM pending_books WHERE id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $book = $result->fetch_assoc();
            
            // Insert into books table
            $stmt = $conn->prepare("INSERT INTO books (title, author, description, file_url, file_key, file_size, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssssi", $book['title'], $book['author'], $book['description'], $book['file_url'], $book['file_key'], $book['file_size']);
            
            if ($stmt->execute()) {
                // Update the status of the request to approved
                $stmt = $conn->prepare("UPDATE pending_books SET status = 'approved', admin_notes = ? WHERE id = ?");
                $stmt->bind_param("si", $admin_notes, $request_id);
                $stmt->execute();
                $success_message = "Book request approved and added to the library!";
            } else {
                $error_message = "Error approving book: " . $stmt->error;
            }
        } else {
            $error_message = "Book request not found!";
        }
        
        $stmt->close();
    } elseif ($action === 'reject') {
        // Update status to rejected
        $stmt = $conn->prepare("UPDATE pending_books SET status = 'rejected', admin_notes = ? WHERE id = ?");
        $stmt->bind_param("si", $admin_notes, $request_id);
        
        if ($stmt->execute()) {
            $success_message = "Book request rejected.";
        } else {
            $error_message = "Error rejecting book: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

// Get all pending book requests
$search = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = sanitizeInput($_GET['search']);
}

// Filter by status
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
if (!in_array($status_filter, ['pending', 'approved', 'rejected', 'all'])) {
    $status_filter = 'pending';
}

$query = "SELECT pb.*, u.name as user_name FROM pending_books pb
          LEFT JOIN users u ON pb.user_id = u.id";

$where_clauses = [];

if ($status_filter !== 'all') {
    $where_clauses[] = "pb.status = '$status_filter'";
}

if (!empty($search)) {
    $where_clauses[] = "(pb.title LIKE ? OR pb.author LIKE ? OR u.name LIKE ?)";
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(' AND ', $where_clauses);
}

$query .= " ORDER BY pb.submitted_at DESC";

$pending_books = [];

if (!empty($search)) {
    $stmt = $conn->prepare($query);
    $search_param = "%" . $search . "%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $pending_books[] = $row;
    }
    
    $stmt->close();
} else {
    $result = $conn->query($query);
    
    while ($row = $result->fetch_assoc()) {
        $pending_books[] = $row;
    }
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

<!-- Pending Books Listing -->
<div class="card">
    <div class="card-header bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-book me-2"></i> Manage Book Requests</h5>
            <div class="d-flex">
                <div class="btn-group me-2">
                    <a href="pending-books.php?status=pending" class="btn btn-sm <?php echo $status_filter == 'pending' ? 'btn-primary' : 'btn-outline-primary'; ?>">Pending</a>
                    <a href="pending-books.php?status=approved" class="btn btn-sm <?php echo $status_filter == 'approved' ? 'btn-success' : 'btn-outline-success'; ?>">Approved</a>
                    <a href="pending-books.php?status=rejected" class="btn btn-sm <?php echo $status_filter == 'rejected' ? 'btn-danger' : 'btn-outline-danger'; ?>">Rejected</a>
                    <a href="pending-books.php?status=all" class="btn btn-sm <?php echo $status_filter == 'all' ? 'btn-secondary' : 'btn-outline-secondary'; ?>">All</a>
                </div>
                <form action="pending-books.php" method="GET" class="d-flex">
                    <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
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
                        <th>Submitted By</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pending_books) > 0): ?>
                        <?php foreach ($pending_books as $index => $book): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td><?php echo htmlspecialchars($book['user_name']); ?></td>
                                <td><?php echo date("M d, Y", strtotime($book['submitted_at'])); ?></td>
                                <td>
                                    <?php if ($book['status'] == 'pending'): ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php elseif ($book['status'] == 'approved'): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php elseif ($book['status'] == 'rejected'): ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $book['id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <?php if ($book['status'] == 'pending'): ?>
                                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $book['id']; ?>">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $book['id']; ?>">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- View Modal -->
                                    <div class="modal fade" id="viewModal<?php echo $book['id']; ?>" tabindex="-1" aria-labelledby="viewModalLabel<?php echo $book['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="viewModalLabel<?php echo $book['id']; ?>">Book Request Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6>Book Information:</h6>
                                                            <ul class="list-group mb-3">
                                                                <li class="list-group-item"><strong>Title:</strong> <?php echo htmlspecialchars($book['title']); ?></li>
                                                                <li class="list-group-item"><strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?></li>
                                                                <li class="list-group-item"><strong>Description:</strong> <?php echo !empty($book['description']) ? htmlspecialchars($book['description']) : 'N/A'; ?></li>
                                                                <li class="list-group-item"><strong>File Size:</strong> <?php echo round($book['file_size'] / (1024 * 1024), 2); ?> MB</li>
                                                                <li class="list-group-item"><strong>URL:</strong> <a href="<?php echo htmlspecialchars($book['file_url']); ?>" target="_blank"><?php echo htmlspecialchars($book['file_url']); ?></a></li>
                                                            </ul>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6>Submission Information:</h6>
                                                            <ul class="list-group mb-3">
                                                                <li class="list-group-item"><strong>Submitted By:</strong> <?php echo htmlspecialchars($book['user_name']); ?></li>
                                                                <li class="list-group-item"><strong>Date:</strong> <?php echo date("F j, Y g:i A", strtotime($book['submitted_at'])); ?></li>
                                                                <li class="list-group-item"><strong>Status:</strong> 
                                                                    <?php if ($book['status'] == 'pending'): ?>
                                                                        <span class="badge bg-warning">Pending</span>
                                                                    <?php elseif ($book['status'] == 'approved'): ?>
                                                                        <span class="badge bg-success">Approved</span>
                                                                    <?php elseif ($book['status'] == 'rejected'): ?>
                                                                        <span class="badge bg-danger">Rejected</span>
                                                                    <?php endif; ?>
                                                                </li>
                                                                <?php if (!empty($book['admin_notes'])): ?>
                                                                    <li class="list-group-item"><strong>Admin Notes:</strong> <?php echo htmlspecialchars($book['admin_notes']); ?></li>
                                                                <?php endif; ?>
                                                            </ul>
                                                            
                                                            <!-- PDF Preview -->
                                                            <div class="mb-3">
                                                                <label class="form-label">PDF Preview:</label>
                                                                <div style="width: 100%; height: 280px; border: 1px solid #ddd; overflow: hidden;">
                                                                    <iframe src="<?php echo htmlspecialchars($book['file_url']) . '#page=1'; ?>" style="width: 100%; height: 100%;" frameborder="0" scrolling="no"></iframe>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Approve Modal -->
                                    <?php if ($book['status'] == 'pending'): ?>
                                        <div class="modal fade" id="approveModal<?php echo $book['id']; ?>" tabindex="-1" aria-labelledby="approveModalLabel<?php echo $book['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="approveModalLabel<?php echo $book['id']; ?>">Approve Book Request</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="pending-books.php?action=approve&id=<?php echo $book['id']; ?>" method="POST">
                                                        <div class="modal-body">
                                                            <p>Are you sure you want to approve <strong><?php echo htmlspecialchars($book['title']); ?></strong>?</p>
                                                            <p>This will add the book to the library and make it available to all users.</p>
                                                            
                                                            <div class="mb-3">
                                                                <label for="admin_notes" class="form-label">Admin Notes (optional):</label>
                                                                <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" placeholder="Add any notes about this approval"></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-success">Approve</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Reject Modal -->
                                        <div class="modal fade" id="rejectModal<?php echo $book['id']; ?>" tabindex="-1" aria-labelledby="rejectModalLabel<?php echo $book['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="rejectModalLabel<?php echo $book['id']; ?>">Reject Book Request</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="pending-books.php?action=reject&id=<?php echo $book['id']; ?>" method="POST">
                                                        <div class="modal-body">
                                                            <p>Are you sure you want to reject <strong><?php echo htmlspecialchars($book['title']); ?></strong>?</p>
                                                            
                                                            <div class="mb-3">
                                                                <label for="admin_notes" class="form-label">Reason for Rejection:</label>
                                                                <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" placeholder="Explain why this book request is being rejected" required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger">Reject</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">
                                <?php 
                                    if ($status_filter == 'pending') echo 'No pending book requests found.';
                                    elseif ($status_filter == 'approved') echo 'No approved book requests found.';
                                    elseif ($status_filter == 'rejected') echo 'No rejected book requests found.';
                                    else echo 'No book requests found.';
                                ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <span>Total Requests: <?php echo count($pending_books); ?></span>
        </div>
    </div>
</div>

<?php 
$conn->close();
include_once 'footer.php';
?> 