<?php
include_once '../config.php';
include_once 'auth.php';
include_once 'header.php';

// Check if user is logged in as admin
requireAdminLogin();

// Initialize variables
$errorMessage = '';
$successMessage = '';

// Check if user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: users.php");
    exit;
}

$user_id = (int)$_GET['id'];
$conn = getConnection();

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: users.php");
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Pre-fill form fields
$name = $user['name'];
$email = $user['email'];
$is_admin = isset($user['is_admin']) ? $user['is_admin'] : 0;

// Get user statistics
$user_stats = [];

// Get number of books in user's library
$stmt = $conn->prepare("SELECT COUNT(*) as total_books FROM user_books WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_stats['total_books'] = $result->fetch_assoc()['total_books'];
$stmt->close();

// Get number of ratings
$stmt = $conn->prepare("SELECT COUNT(*) as total_ratings FROM ratings WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_stats['total_ratings'] = $result->fetch_assoc()['total_ratings'];
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'] ?? '';
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    
    if (empty($name) || empty($email)) {
        $errorMessage = "Name and email are required.";
    } else {
        // Check if email already exists for another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errorMessage = "Email already exists for another user.";
            $stmt->close();
        } else {
            $stmt->close();
            
            // Update user data
            if (!empty($password)) {
                // Validate password
                if (strlen($password) < 6) {
                    $errorMessage = "Password must be at least 6 characters.";
                } else {
                    // Update with new password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ?, is_admin = ? WHERE id = ?");
                    $stmt->bind_param("sssii", $name, $email, $hashed_password, $is_admin, $user_id);
                }
            } else {
                // Update without changing password
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, is_admin = ? WHERE id = ?");
                $stmt->bind_param("ssii", $name, $email, $is_admin, $user_id);
            }
            
            if (empty($errorMessage)) {
                if ($stmt->execute()) {
                    $successMessage = "User updated successfully!";
                    
                    // Refresh user data
                    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    
                    // Update local variables
                    $name = $user['name'];
                    $email = $user['email'];
                    $is_admin = isset($user['is_admin']) ? $user['is_admin'] : 0;
                } else {
                    $errorMessage = "Error updating user: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

$conn->close();
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2>Edit User</h2>
            <p class="text-muted">Update user account information</p>
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
                    <h5 class="card-title mb-0">User Information</h5>
                </div>
                <div class="card-body">
                    <form id="userForm" method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($name); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep current password">
                            <div class="form-text">Password must be at least 6 characters long</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_admin" name="is_admin" <?php echo $is_admin ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_admin">
                                    <strong>Admin Privileges</strong> - Can access admin panel and manage all content
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <button type="submit" class="btn btn-success">Update User</button>
                            <a href="users.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">User Details</h5>
                </div>
                <div class="card-body">
                    <h6>Account Information:</h6>
                    <ul class="list-unstyled mb-4">
                        <li><strong>User ID:</strong> <?php echo $user['id']; ?></li>
                        <li><strong>Registered:</strong> <?php echo date("F j, Y", strtotime($user['created_at'])); ?></li>
                        <li><strong>Status:</strong> 
                            <?php if ($is_admin): ?>
                                <span class="badge bg-danger">Administrator</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Regular User</span>
                            <?php endif; ?>
                        </li>
                    </ul>
                    
                    <h6>User Activity:</h6>
                    <ul class="list-unstyled mb-4">
                        <li><strong>Books in Library:</strong> <?php echo $user_stats['total_books']; ?></li>
                        <li><strong>Ratings Given:</strong> <?php echo $user_stats['total_ratings']; ?></li>
                    </ul>
                    
                    <?php if ($is_admin): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This user has admin privileges. They can access the admin panel and make changes to the site.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?> 