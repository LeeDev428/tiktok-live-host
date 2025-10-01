<?php
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Access Denied';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="hero-bg"></div>
    
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo">
                    <div class="logo-icon">🎯</div>
                    <h1><?php echo SITE_NAME; ?></h1>
                </div>
            </div>
            
            <div class="auth-content">
                <div class="error-container">
                    <div class="error-icon">🚫</div>
                    <h2>Access Denied</h2>
                    <p>You don't have permission to access this page.</p>
                    <p>Please contact your administrator if you believe this is an error.</p>
                </div>
                
                <div class="auth-actions">
                    <?php if (is_logged_in()): ?>
                        <a href="<?php echo get_user_dashboard_url($_SESSION['user_role']); ?>" class="btn btn-primary">
                            Go to Dashboard
                        </a>
                    <?php else: ?>
                        <a href="/tiktok-live-host/login.php" class="btn btn-primary">
                            Login
                        </a>
                    <?php endif; ?>
                    <a href="/tiktok-live-host/" class="btn btn-outline">
                        Go Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>