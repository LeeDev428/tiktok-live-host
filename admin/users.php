<?php
session_start();
require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../unauthorized.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $status = $_POST['status'];
    
    // Validation
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if (empty($status)) {
        $errors[] = "Status is required";
    }
    
    // Check if username already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Username already exists";
        }
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Email already exists";
        }
    }
    
    // Create user if no errors
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, full_name, email, password, role, status, created_at) VALUES (?, ?, ?, ?, 'live_seller', ?, NOW())");
        
        if ($stmt->execute([$username, $full_name, $email, $hashed_password, $status])) {
            $success_message = "User created successfully!";
        } else {
            $errors[] = "Error creating user. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Users - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <span class="logo-icon">🎯</span>
                    <span class="logo-text">Admin Panel</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php"><span class="nav-icon">🏠</span> Dashboard</a></li>
                    <li class="active"><a href="create_users.php"><span class="nav-icon">👥</span> Create Users</a></li>
                    <li><a href="user-management.php"><span class="nav-icon">⚙️</span> User Management</a></li>
                    <li><a href="#"><span class="nav-icon">📊</span> Reports</a></li>
                    <li><a href="#"><span class="nav-icon">📋</span> Activity Logs</a></li>
                    <li><a href="#"><span class="nav-icon">⚙️</span> Settings</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">S</div>
                    <div class="user-details">
                        <div class="user-name">System Administrator</div>
                        <div class="user-role">Administrator</div>
                    </div>
                </div>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="content-header">
                <h1>Create Users</h1>
                <div class="user-menu">
                    <span class="user-avatar">S</span>
                    <span class="username">admin</span>
                    <span class="dropdown-arrow">▼</span>
                </div>
            </header>
            
            <div class="content-body">
                <div class="form-container">
                    <div class="form-header">
                        <h2>👥 Create New User</h2>
                        <p>Add new users to your TikTok Live Host Agency.</p>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="user-form">
                        <div class="form-section">
                            <h3>User Information</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="username">USER NAME</label>
                                    <input type="text" id="username" name="username" placeholder="Enter username" 
                                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                                    <small class="form-help">Username must be unique and contain no spaces</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="full_name">FULL NAME</label>
                                    <input type="text" id="full_name" name="full_name" placeholder="Enter full name"
                                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                                    <small class="form-help">Enter the user's complete name</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">EMAIL</label>
                                    <input type="email" id="email" name="email" placeholder="Enter email address"
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                    <small class="form-help">Valid email address for login and notifications</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="password">PASSWORD</label>
                                    <input type="password" id="password" name="password" placeholder="Enter password" required>
                                    <small class="form-help">Password must be at least 6 characters long</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="status">STATUS</label>
                                    <select id="status" name="status" required>
                                        <option value="">Select status</option>
                                        <option value="newbie" <?php echo (isset($_POST['status']) && $_POST['status'] === 'newbie') ? 'selected' : ''; ?>>Newbie</option>
                                        <option value="tenure" <?php echo (isset($_POST['status']) && $_POST['status'] === 'tenure') ? 'selected' : ''; ?>>Tenure</option>
                                    </select>
                                    <small class="form-help">Choose the user's experience level</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Create User</button>
                            <button type="reset" class="btn btn-secondary">🔄 Reset Form</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>