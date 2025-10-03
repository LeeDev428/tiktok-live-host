<?php
if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/../../config/config.php';
}

$current_user = get_logged_in_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?> Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" href="../assets/images/favicon.ico" type="image/x-icon">
</head>
<body class="admin-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <div class="logo-icon">📺</div>
                <span>Admin Panel</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">🏠</span>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">�</span>
                        <span class="nav-text">Create Users</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="user-management.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'user-management.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">�</span>
                        <span class="nav-text">User Management</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="reports.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">📊</span>
                        <span class="nav-text">Reports</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="activity.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'activity.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">📝</span>
                        <span class="nav-text">Activity Logs</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">⚙️</span>
                        <span class="nav-text">Settings</span>
                    </a>
                </li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($current_user['full_name'], 0, 1)); ?>
                </div>
                <div class="user-details">
                    <p class="user-name"><?php echo htmlspecialchars($current_user['full_name']); ?></p>
                    <p class="user-role">Administrator</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <h1 class="page-title"><?php echo isset($page_title) ? $page_title : 'Admin Panel'; ?></h1>
            </div>

            <div class="topbar-right">
                <div class="topbar-actions">
                  
                    
                    <div class="user-menu">
                        <button class="user-menu-toggle" id="userMenuToggle">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($current_user['full_name'], 0, 1)); ?>
                            </div>
                            <span class="user-name"><?php echo htmlspecialchars($current_user['username']); ?></span>
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        
                        <div class="user-dropdown" id="userDropdown">
                            <a href="profile.php" class="dropdown-item">
                                <span class="item-icon">👤</span>
                                Profile
                            </a>
                            <a href="settings.php" class="dropdown-item">
                                <span class="item-icon">⚙️</span>
                                Settings
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="../logout.php" class="dropdown-item">
                                <span class="item-icon">🚪</span>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="content-wrapper">