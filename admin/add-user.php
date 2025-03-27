<?php
include_once '../config.php';
include_once 'auth.php';
include_once 'header.php';

// Check if user is logged in as admin
requireAdminLogin();

// Initialize variables
$errorMessage = '';
$successMessage = '';
$name = '';
$email = '';
$is_admin = 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'] ?? '';
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    
    // Validate inputs
    if (empty($name)) {
        $errorMessage = "Name is required";
    } elseif (empty($email)) {
        $errorMessage = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Invalid email format";
    } elseif (empty($password)) {
        $errorMessage = "Password is required";
    } elseif (strlen($password) < 6) {
        $errorMessage = "Password must be at least 6 characters long";
    } else {
        // Connect to database
        $conn = getConnection();
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errorMessage = "Email already exists. Please use a different email.";
            $stmt->close();
        } else {
            $stmt->close();
            
            // Create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $created_at = date('Y-m-d H:i:s');
            
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, is_admin, created_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssis", $name, $email, $hashed_password, $is_admin, $created_at);
            
            if ($stmt->execute()) {
                $successMessage = "User created successfully!";
                // Clear form fields after successful submission
                $name = '';
                $email = '';
                $is_admin = 0;
            } else {
                $errorMessage = "Error creating user: " . $stmt->error;
            }
            
            $stmt->close();
        }
        
        $conn->close();
    }
}
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2>Add New User</h2>
            <p class="text-muted">Create a new user account</p>
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
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
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
                            <button type="submit" class="btn btn-success">Create User</button>
                            <a href="users.php" class="btn btn-outline-secondary">Cancel</a>
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
                    <h6>Creating a new user:</h6>
                    <ul class="mb-4">
                        <li>All fields marked with <span class="text-danger">*</span> are required</li>
                        <li>Email address must be unique</li>
                        <li>Password must be at least 6 characters long</li>
                        <li>Check "Admin Privileges" to allow this user to access the admin panel</li>
                    </ul>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Be careful when granting admin privileges. Admins have full control over the site.
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Users can change their password later after logging in.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?> 