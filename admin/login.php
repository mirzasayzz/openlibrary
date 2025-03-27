<?php
include_once '../config.php';

// Check if already logged in as admin
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$errors = [];
$email = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($email)) {
        $errors[] = "Email is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // Attempt login if no validation errors
    if (empty($errors)) {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT id, name, password, is_admin FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Check if user is admin and password is correct
            if ($user['is_admin'] == 1 && password_verify($password, $user['password'])) {
                // Set admin session variables
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_name'] = $user['name'];
                
                // Redirect to admin dashboard
                header("Location: index.php");
                exit;
            } else {
                $errors[] = "Invalid credentials or insufficient permissions";
            }
        } else {
            $errors[] = "Invalid credentials or insufficient permissions";
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .admin-login-card {
            max-width: 400px;
            margin: 100px auto;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .admin-login-header {
            background-color: #3D5A80;
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .admin-login-body {
            padding: 30px;
        }
        .btn-admin {
            background-color: #3D5A80;
            border-color: #3D5A80;
        }
        .btn-admin:hover {
            background-color: #2C3E50;
            border-color: #2C3E50;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card admin-login-card">
            <div class="admin-login-header">
                <h3><i class="fas fa-lock me-2"></i> Admin Login</h3>
                <p class="mb-0"><?php echo SITE_NAME; ?> Administration</p>
            </div>
            <div class="admin-login-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-admin text-white">Login</button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <a href="../index.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i> Back to site
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 