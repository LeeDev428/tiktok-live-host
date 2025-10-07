<?php
$page_title = "Create Users";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Require admin role
require_role('admin');

$message = '';
$error = '';

// Handle form submission
if ($_POST) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($csrf_token)) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        $full_name = sanitize_input($_POST['full_name'] ?? '');
        $username = sanitize_input($_POST['username'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $experienced_status = sanitize_input($_POST['experienced_status'] ?? '');
        
        // Validation
    if (empty($full_name) || empty($username) || empty($email) || empty($password) || empty($experienced_status)) {
            $error = 'All fields are required.';
        } elseif (!validate_email($email)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            $db = getDB();
            
            // Check if username or email already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'Username or email already exists.';
            } else {
                // Handle profile image upload
                $profile_image = null;
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = __DIR__ . '/../uploads/profiles/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array(strtolower($file_extension), $allowed_extensions)) {
                        $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                            $profile_image = 'uploads/profiles/' . $new_filename;
                        } else {
                            $error = 'Failed to upload profile image.';
                        }
                    } else {
                        $error = 'Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.';
                    }
                }
                
                    if (empty($error)) {
                    // Insert new user
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "INSERT INTO users (username, email, password, role, full_name, profile_image, experienced_status, status) VALUES (?, ?, ?, 'live_seller', ?, ?, ?, 'active')";
                    $stmt = $db->prepare($sql);
                    
                    if ($stmt->execute([$username, $email, $hashed_password, $full_name, $profile_image, $experienced_status])) {
                        $new_user_id = $db->lastInsertId();
                        log_activity($_SESSION['user_id'], 'create_user', "Created new user: $username");
                        $message = 'User created successfully!';
                        
                        // Clear form data
                        $full_name = $username = $email = $experienced_status = '';
                    } else {
                        $error = 'Failed to create user. Please try again.';
                    }
                }
            }
        }
    }
}

include __DIR__ . '/layout/header.php';
?>

<div class="create-user-container">
    <div class="form-header">
        <div class="header-icon">👥</div>
        <div class="header-content">
            <h1>Create New User</h1>
            <p>Add new users to your TikTok Live Host Team.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success">
            <span class="alert-icon">✓</span>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <span class="alert-icon">⚠</span>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="user-form-card">
        <div class="form-section-header">
            <h3>User Information</h3>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="compact-user-form">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="form-grid">
                <div class="form-field">
                    <label for="username">USER NAME</label>
                    <input type="text" id="username" name="username" 
                           placeholder="Enter username" 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                    <small class="field-hint">Username must be unique and contain no spaces</small>
                </div>
                
                <div class="form-field">
                    <label for="full_name">FULL NAME</label>
                    <input type="text" id="full_name" name="full_name" 
                           placeholder="Enter full name" 
                           value="<?php echo htmlspecialchars($full_name ?? ''); ?>" required>
                    <small class="field-hint">Enter the user's complete name</small>
                </div>
                
                <div class="form-field">
                    <label for="email">EMAIL</label>
                    <input type="email" id="email" name="email" 
                           placeholder="Enter email address" 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                    <small class="field-hint">Valid email address for login and notifications</small>
                </div>
                
                <div class="form-field">
                    <label for="password">PASSWORD</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Enter password" minlength="6" required>
                    <small class="field-hint">Password must be at least 6 characters long</small>
                </div>
                
                <div class="form-field">
                    <label for="experienced_status">Experience Status</label>
                    <select id="experienced_status" name="experienced_status" required>
                        <option value="">Select experience status</option>
                        <option value="newbie" <?php echo (($experienced_status ?? '') === 'newbie') ? 'selected' : ''; ?>>Newbie</option>
                        <option value="tenured" <?php echo (($experienced_status ?? '') === 'tenured') ? 'selected' : ''; ?>>Tenured</option>
                    </select>
                    <small class="field-hint">Choose the user's experience level</small>
                </div>
                
                <div class="form-field file-field">
                    <label for="profile_image">PROFILE IMAGE</label>
                    <div class="file-upload-area">
                        <input type="file" id="profile_image" name="profile_image" 
                               accept="image/jpeg,image/jpg,image/png,image/gif">
                        <div class="file-upload-content">
                            <span class="upload-icon">📷</span>
                            <span class="upload-text">Choose image file</span>
                        </div>
                    </div>
                    <small class="field-hint">Upload a profile picture (JPG, PNG, GIF)</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-create">
                    <span class="btn-icon">👤</span>
                    Create User
                </button>
                <button type="reset" class="btn-reset">
                    <span class="btn-icon">🔄</span>
                    Reset Form
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// File upload preview functionality
document.getElementById('profile_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const uploadArea = this.closest('.file-upload-area');
    const uploadText = uploadArea.querySelector('.upload-text');
    
    if (file) {
        uploadText.textContent = file.name;
        uploadArea.classList.add('has-file');
    } else {
        uploadText.textContent = 'Choose image file';
        uploadArea.classList.remove('has-file');
    }
});

// Form reset handler
document.querySelector('.btn-reset').addEventListener('click', function(e) {
    e.preventDefault();
    
    // Reset form
    document.querySelector('.compact-user-form').reset();
    
    // Reset file upload area
    const uploadArea = document.querySelector('.file-upload-area');
    const uploadText = uploadArea.querySelector('.upload-text');
    uploadText.textContent = 'Choose image file';
    uploadArea.classList.remove('has-file');
});

// Form validation enhancement
document.querySelector('.compact-user-form').addEventListener('submit', function(e) {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    
    // Check for spaces in username
    if (username.includes(' ')) {
        e.preventDefault();
        alert('Username cannot contain spaces');
        document.getElementById('username').focus();
        return;
    }
    
    // Check password length
    if (password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long');
        document.getElementById('password').focus();
        return;
    }
});
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>