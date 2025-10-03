<?php
$page_title = "User Management";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Require admin role
require_role('admin');

$message = '';
$error = '';
$edit_user = null;
$filter_status = $_GET['status'] ?? 'all';
$search_query = $_GET['search'] ?? '';

// Handle user deletion
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['user_id'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($csrf_token)) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        $user_id = (int)$_POST['user_id'];
        $db = getDB();
        
        // Get user info before deletion for logging
        $stmt = $db->prepare("SELECT username FROM users WHERE id = ? AND role = 'live_seller'");
        $stmt->execute([$user_id]);
        $user_to_delete = $stmt->fetch();
        
        if ($user_to_delete) {
            // Delete user
            $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'live_seller'");
            if ($stmt->execute([$user_id])) {
                log_activity($_SESSION['user_id'], 'delete_user', "Deleted user: " . $user_to_delete['username']);
                $message = 'User deleted successfully!';
            } else {
                $error = 'Failed to delete user. Please try again.';
            }
        } else {
            $error = 'User not found.';
        }
    }
}

// Handle user status toggle
if (isset($_POST['action']) && $_POST['action'] === 'toggle_status' && isset($_POST['user_id'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($csrf_token)) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        $user_id = (int)$_POST['user_id'];
        $db = getDB();
        
        // Get current status
        $stmt = $db->prepare("SELECT username, status FROM users WHERE id = ? AND role = 'live_seller'");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            $new_status = $user['status'] === 'active' ? 'inactive' : 'active';
            $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
            if ($stmt->execute([$new_status, $user_id])) {
                log_activity($_SESSION['user_id'], 'update_user_status', "Changed status of user {$user['username']} to {$new_status}");
                $message = "User status updated to {$new_status}!";
            } else {
                $error = 'Failed to update user status. Please try again.';
            }
        } else {
            $error = 'User not found.';
        }
    }
}

// Handle bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['selected_users']) && !empty($_POST['selected_users'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($csrf_token)) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        $bulk_action = $_POST['bulk_action'];
        $selected_users = array_map('intval', $_POST['selected_users']);
        $db = getDB();
        
        switch ($bulk_action) {
            case 'activate':
                $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE id IN (" . str_repeat('?,', count($selected_users) - 1) . "?) AND role = 'live_seller'");
                if ($stmt->execute($selected_users)) {
                    $message = count($selected_users) . ' users activated successfully!';
                }
                break;
                
            case 'deactivate':
                $stmt = $db->prepare("UPDATE users SET status = 'inactive' WHERE id IN (" . str_repeat('?,', count($selected_users) - 1) . "?) AND role = 'live_seller'");
                if ($stmt->execute($selected_users)) {
                    $message = count($selected_users) . ' users deactivated successfully!';
                }
                break;
                
            case 'delete':
                $stmt = $db->prepare("DELETE FROM users WHERE id IN (" . str_repeat('?,', count($selected_users) - 1) . "?) AND role = 'live_seller'");
                if ($stmt->execute($selected_users)) {
                    $message = count($selected_users) . ' users deleted successfully!';
                }
                break;
        }
    }
}

// Handle user edit form submission
if (isset($_POST['action']) && $_POST['action'] === 'edit' && isset($_POST['user_id'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($csrf_token)) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        $user_id = (int)$_POST['user_id'];
        $full_name = sanitize_input($_POST['full_name'] ?? '');
        $username = sanitize_input($_POST['username'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $experience_status = sanitize_input($_POST['experience_status'] ?? '');
        
        // Validation
        if (empty($full_name) || empty($username) || empty($email) || empty($experience_status)) {
            $error = 'All fields are required.';
        } elseif (!validate_email($email)) {
            $error = 'Please enter a valid email address.';
        } else {
            $db = getDB();
            
            // Check if username or email already exists (excluding current user)
            $stmt = $db->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $user_id]);
            if ($stmt->fetch()) {
                $error = 'Username or email already exists.';
            } else {
                // Update user
                $stmt = $db->prepare("
                    UPDATE users 
                    SET username = ?, email = ?, full_name = ?, experience_status = ?
                    WHERE id = ? AND role = 'live_seller'
                ");
                
                if ($stmt->execute([$username, $email, $full_name, $experience_status, $user_id])) {
                    log_activity($_SESSION['user_id'], 'update_user', "Updated user: $username");
                    $message = 'User updated successfully!';
                } else {
                    $error = 'Failed to update user. Please try again.';
                }
            }
        }
    }
}

// Get edit user data if editing
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_user_id = (int)$_GET['edit'];
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'live_seller'");
    $stmt->execute([$edit_user_id]);
    $edit_user = $stmt->fetch();
}

include __DIR__ . '/layout/header.php';
?>

<div class="user-management-page">
    <!-- Page Header -->
    <div class="page-header-section">
        <div class="header-content">
            <h1><i class="fas fa-users-cog"></i> User Management</h1>
            <p>Manage and monitor all live sellers in your agency</p>
        </div>
        <div class="header-actions">
            <a href="users.php" class="btn-add-user">
                <i class="fas fa-plus-circle"></i>
                <span>Add New User</span>
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
            <button class="alert-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
            <button class="alert-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <?php
    $db = getDB();
    
    // Get comprehensive statistics
    $stats_query = "
        SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users,
            SUM(CASE WHEN experience_status = 'newbie' THEN 1 ELSE 0 END) as newbie_users,
            SUM(CASE WHEN experience_status = 'tenured' THEN 1 ELSE 0 END) as tenured_users,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_registrations,
            SUM(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week_registrations
        FROM users 
        WHERE role = 'live_seller'
    ";
    
    $stmt = $db->prepare($stats_query);
    $stmt->execute();
    $stats = $stmt->fetch();
    
    $active_rate = $stats['total_users'] > 0 ? round(($stats['active_users'] / $stats['total_users']) * 100) : 0;
    ?>
    
    <div class="statistics-row">
        <div class="stat-card stat-total">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-details">
                <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Total Users</div>
                <div class="stat-trend">
                    <small><?php echo $stats['week_registrations']; ?> this week</small>
                </div>
            </div>
        </div>
        
        <div class="stat-card stat-active">
            <div class="stat-icon">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-details">
                <div class="stat-number"><?php echo $stats['active_users']; ?></div>
                <div class="stat-label">Active Users</div>
                <div class="stat-trend">
                    <small><?php echo $active_rate; ?>% active rate</small>
                </div>
            </div>
        </div>
        
        <div class="stat-card stat-inactive">
            <div class="stat-icon">
                <i class="fas fa-user-times"></i>
            </div>
            <div class="stat-details">
                <div class="stat-number"><?php echo $stats['inactive_users']; ?></div>
                <div class="stat-label">Inactive Users</div>
                <div class="stat-trend">
                    <small><?php echo $stats['today_registrations']; ?> today</small>
                </div>
            </div>
        </div>
        
        <div class="stat-card stat-rate">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-details">
                <div class="stat-number"><?php echo $stats['tenured_users']; ?></div>
                <div class="stat-label">Tenured Users</div>
                <div class="stat-trend">
                    <small><?php echo $stats['newbie_users']; ?> newbies</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <?php if ($edit_user): ?>
    <div class="modal-backdrop">
        <div class="edit-modal">
            <div class="modal-header">
                <h3><i class="fas fa-user-edit"></i> Edit User Profile</h3>
                <a href="user-management.php" class="modal-close">
                    <i class="fas fa-times"></i>
                </a>
            </div>
            
            <form method="POST" class="edit-form">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_username"><i class="fas fa-user"></i> Username</label>
                        <input type="text" id="edit_username" name="username" 
                               value="<?php echo htmlspecialchars($edit_user['username']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_full_name"><i class="fas fa-id-card"></i> Full Name</label>
                        <input type="text" id="edit_full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($edit_user['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_email"><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" id="edit_email" name="email" 
                               value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_experience_status"><i class="fas fa-star"></i> Experience Level</label>
                        <select id="edit_experience_status" name="experience_status" required>
                            <option value="newbie" <?php echo $edit_user['experience_status'] === 'newbie' ? 'selected' : ''; ?>>Newbie</option>
                            <option value="tenured" <?php echo $edit_user['experience_status'] === 'tenured' ? 'selected' : ''; ?>>Tenured</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-save">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                    <a href="user-management.php" class="btn btn-cancel">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- User Management Table -->
    <div class="user-table-section">
        <div class="table-header">
            <div class="table-title">
                <h2><i class="fas fa-table"></i> User Directory</h2>
                <div class="table-stats">
                    <span class="stat-badge"><?php echo $stats['total_users']; ?> Total</span>
                    <span class="stat-badge active"><?php echo $stats['active_users']; ?> Active</span>
                    <span class="stat-badge inactive"><?php echo $stats['inactive_users']; ?> Inactive</span>
                </div>
            </div>
            
            <div class="table-controls">
                <div class="search-filter-row">
                    <div class="search-container">
                        <i class="fas fa-search"></i>
                        <input type="text" 
                               placeholder="Search users..." 
                               id="userSearch"
                               value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    
                    <div class="filter-container">
                        <select id="statusFilter" onchange="applyFilters()">
                            <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Active Only</option>
                            <option value="inactive" <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                        </select>
                    </div>
                    
                    <div class="filter-container">
                        <select id="experienceFilter" onchange="applyFilters()">
                            <option value="all">All Experience</option>
                            <option value="newbie">Newbie</option>
                            <option value="tenured">Tenured</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bulk Actions Bar -->
        <div class="bulk-actions-bar" id="bulkActionsBar" style="display: none;">
            <form method="POST" id="bulkActionForm">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="bulk-actions-content">
                    <div class="selected-count">
                        <span id="selectedCount">0</span> users selected
                    </div>
                    
                    <div class="bulk-action-controls">
                        <select name="bulk_action" id="bulkActionSelect">
                            <option value="">Choose Action</option>
                            <option value="activate">Activate Users</option>
                            <option value="deactivate">Deactivate Users</option>
                            <option value="delete">Delete Users</option>
                        </select>
                        
                        <button type="submit" class="btn-bulk-apply" onclick="return confirmBulkAction()">
                            <i class="fas fa-check"></i>
                            Apply
                        </button>
                        
                        <button type="button" class="btn-bulk-cancel" onclick="clearSelection()">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="table-wrapper">
            <?php
            // Build query with filters
            $where_conditions = ["role = 'live_seller'"];
            $params = [];
            
            if ($filter_status !== 'all') {
                $where_conditions[] = "status = ?";
                $params[] = $filter_status;
            }
            
            if (!empty($search_query)) {
                $where_conditions[] = "(username LIKE ? OR full_name LIKE ? OR email LIKE ?)";
                $search_param = '%' . $search_query . '%';
                $params[] = $search_param;
                $params[] = $search_param;
                $params[] = $search_param;
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            
            $stmt = $db->prepare("SELECT * FROM users WHERE {$where_clause} ORDER BY created_at DESC");
            $stmt->execute($params);
            $users = $stmt->fetchAll();
            
            if (empty($users)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>No Users Found</h3>
                    <p>
                        <?php if (!empty($search_query) || $filter_status !== 'all'): ?>
                            No users match your search criteria. Try adjusting your filters.
                        <?php else: ?>
                            Get started by creating your first user account.
                        <?php endif; ?>
                    </p>
                    <?php if (empty($search_query) && $filter_status === 'all'): ?>
                        <a href="users.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Create First User
                        </a>
                    <?php else: ?>
                        <button onclick="clearFilters()" class="btn btn-secondary">
                            <i class="fas fa-filter"></i>
                            Clear Filters
                        </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th class="col-select">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th class="col-profile">Profile</th>
                            <th class="col-name">User Details</th>
                            <th class="col-email">Contact</th>
                            <th class="col-experience">Experience</th>
                            <th class="col-status">Status</th>
                            <th class="col-date">Date Created</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr class="user-row <?php echo $user['status']; ?>" data-user-id="<?php echo $user['id']; ?>">
                                <td class="select-cell">
                                    <input type="checkbox" 
                                           class="user-checkbox" 
                                           name="selected_users[]" 
                                           value="<?php echo $user['id']; ?>"
                                           onchange="updateSelection()">
                                </td>
                                
                                <td class="profile-cell">
                                    <div class="user-avatar">
                                        <?php if ($user['profile_image'] && file_exists(__DIR__ . '/../' . $user['profile_image'])): ?>
                                            <img src="../<?php echo htmlspecialchars($user['profile_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($user['full_name']); ?>">
                                        <?php else: ?>
                                            <div class="avatar-placeholder">
                                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <td class="name-cell">
                                    <div class="user-info">
                                        <div class="full-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                        <div class="username">@<?php echo htmlspecialchars($user['username']); ?></div>
                                    </div>
                                </td>
                                
                                <td class="email-cell">
                                    <div class="email-info">
                                        <i class="fas fa-envelope"></i>
                                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                                    </div>
                                </td>
                                
                                <td class="experience-cell">
                                    <span class="experience-badge <?php echo htmlspecialchars($user['experience_status'] ?? 'not-set'); ?>">
                                        <i class="fas fa-<?php echo ($user['experience_status'] === 'tenured') ? 'crown' : 'seedling'; ?>"></i>
                                        <?php echo ucfirst($user['experience_status'] ?? 'Not Set'); ?>
                                    </span>
                                </td>
                                
                                <td class="status-cell">
                                    <span class="status-badge <?php echo htmlspecialchars($user['status']); ?>">
                                        <i class="fas fa-circle"></i>
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                
                                <td class="date-cell">
                                    <div class="date-info">
                                        <div class="date"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
                                        <div class="time"><?php echo date('g:i A', strtotime($user['created_at'])); ?></div>
                                    </div>
                                </td>
                                
                                <td class="actions-cell">
                                    <div class="action-buttons">
                                        <a href="?edit=<?php echo $user['id']; ?>" 
                                           class="action-btn edit-btn" 
                                           title="Edit User"
                                           data-tooltip="Edit User">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <form method="POST" class="action-form" onsubmit="return confirm('Are you sure you want to <?php echo ($user['status'] === 'active') ? 'deactivate' : 'activate'; ?> this user?')">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" 
                                                    class="action-btn status-btn" 
                                                    title="<?php echo ($user['status'] === 'active') ? 'Deactivate' : 'Activate'; ?> User"
                                                    data-tooltip="<?php echo ($user['status'] === 'active') ? 'Deactivate' : 'Activate'; ?> User">
                                                <i class="fas fa-<?php echo ($user['status'] === 'active') ? 'pause' : 'play'; ?>"></i>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" class="action-form" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" 
                                                    class="action-btn delete-btn" 
                                                    title="Delete User"
                                                    data-tooltip="Delete User">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pagination (if needed in future) -->
                <div class="table-footer">
                    <div class="table-info">
                        Showing <?php echo count($users); ?> of <?php echo $stats['total_users']; ?> users
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Enhanced User Management JavaScript

// Search functionality with debouncing
let searchTimeout;
document.getElementById('userSearch').addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        performSearch(e.target.value);
    }, 300);
});

function performSearch(searchTerm) {
    const searchLower = searchTerm.toLowerCase();
    const rows = document.querySelectorAll('.user-row');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const name = row.querySelector('.full-name')?.textContent.toLowerCase() || '';
        const username = row.querySelector('.username')?.textContent.toLowerCase() || '';
        const email = row.querySelector('.email-info span')?.textContent.toLowerCase() || '';
        
        const matches = name.includes(searchLower) || 
                       username.includes(searchLower) || 
                       email.includes(searchLower);
        
        if (matches) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Update table footer
    updateTableInfo(visibleCount);
    
    // Show empty state if no results
    toggleEmptyState(visibleCount === 0 && searchTerm.length > 0);
}

// Filter functionality
function applyFilters() {
    const statusFilter = document.getElementById('statusFilter').value;
    const experienceFilter = document.getElementById('experienceFilter').value;
    const searchQuery = document.getElementById('userSearch').value;
    
    // Build URL with filters
    const url = new URL(window.location);
    
    if (statusFilter !== 'all') {
        url.searchParams.set('status', statusFilter);
    } else {
        url.searchParams.delete('status');
    }
    
    if (searchQuery) {
        url.searchParams.set('search', searchQuery);
    } else {
        url.searchParams.delete('search');
    }
    
    window.location.href = url.toString();
}

function clearFilters() {
    const url = new URL(window.location);
    url.searchParams.delete('status');
    url.searchParams.delete('search');
    window.location.href = url.toString();
}

// Bulk selection functionality
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    
    userCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateSelection();
}

function updateSelection() {
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    const selectAll = document.getElementById('selectAll');
    const bulkActionsBar = document.getElementById('bulkActionsBar');
    const selectedCount = document.getElementById('selectedCount');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    
    // Update select all checkbox state
    if (checkedBoxes.length === 0) {
        selectAll.indeterminate = false;
        selectAll.checked = false;
    } else if (checkedBoxes.length === userCheckboxes.length) {
        selectAll.indeterminate = false;
        selectAll.checked = true;
    } else {
        selectAll.indeterminate = true;
        selectAll.checked = false;
    }
    
    // Show/hide bulk actions bar
    if (checkedBoxes.length > 0) {
        bulkActionsBar.style.display = 'block';
        selectedCount.textContent = checkedBoxes.length;
        
        // Add selected user IDs to form
        const form = document.getElementById('bulkActionForm');
        const existingInputs = form.querySelectorAll('input[name="selected_users[]"]');
        existingInputs.forEach(input => input.remove());
        
        checkedBoxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'selected_users[]';
            hiddenInput.value = checkbox.value;
            form.appendChild(hiddenInput);
        });
    } else {
        bulkActionsBar.style.display = 'none';
    }
}

function clearSelection() {
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    const selectAll = document.getElementById('selectAll');
    
    userCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    selectAll.checked = false;
    selectAll.indeterminate = false;
    
    updateSelection();
}

function confirmBulkAction() {
    const bulkAction = document.getElementById('bulkActionSelect').value;
    const selectedCount = document.querySelectorAll('.user-checkbox:checked').length;
    
    if (!bulkAction) {
        alert('Please select an action to perform.');
        return false;
    }
    
    let message = '';
    switch (bulkAction) {
        case 'activate':
            message = `Are you sure you want to activate ${selectedCount} users?`;
            break;
        case 'deactivate':
            message = `Are you sure you want to deactivate ${selectedCount} users?`;
            break;
        case 'delete':
            message = `Are you sure you want to delete ${selectedCount} users? This action cannot be undone.`;
            break;
    }
    
    return confirm(message);
}

// Enhanced tooltips
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = e.target.dataset.tooltip;
    document.body.appendChild(tooltip);
    
    const rect = e.target.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
    
    setTimeout(() => tooltip.classList.add('show'), 10);
}

function hideTooltip() {
    const tooltips = document.querySelectorAll('.tooltip');
    tooltips.forEach(tooltip => {
        tooltip.classList.remove('show');
        setTimeout(() => tooltip.remove(), 200);
    });
}

// Auto-hide alerts with animation
function initializeAlerts() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        // Auto-hide after 5 seconds
        setTimeout(() => {
            hideAlert(alert);
        }, 5000);
        
        // Add close button functionality
        const closeBtn = alert.querySelector('.alert-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => hideAlert(alert));
        }
    });
}

function hideAlert(alert) {
    alert.style.opacity = '0';
    alert.style.transform = 'translateY(-10px)';
    setTimeout(() => {
        alert.remove();
    }, 300);
}

// Modal functionality
function initializeModals() {
    const modalBackdrop = document.querySelector('.modal-backdrop');
    
    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', function(e) {
            if (e.target === this) {
                window.location.href = 'user-management.php';
            }
        });
        
        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.location.href = 'user-management.php';
            }
        });
    }
}

// Row animations and interactions
function initializeRowAnimations() {
    const rows = document.querySelectorAll('.user-row');
    
    rows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

// Status badge click functionality
function initializeStatusBadges() {
    const statusBadges = document.querySelectorAll('.status-badge');
    
    statusBadges.forEach(badge => {
        badge.style.cursor = 'pointer';
        badge.addEventListener('click', function() {
            const row = this.closest('.user-row');
            const userId = row.dataset.userId;
            const currentStatus = this.classList.contains('active') ? 'active' : 'inactive';
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            
            if (confirm(`Are you sure you want to ${newStatus === 'active' ? 'activate' : 'deactivate'} this user?`)) {
                // Create and submit form
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
}

// Real-time updates (if needed)
function initializeRealTimeUpdates() {
    // Placeholder for WebSocket or polling functionality
    // Can be implemented later for real-time user status updates
}

// Table info updates
function updateTableInfo(visibleCount) {
    const tableInfo = document.querySelector('.table-info');
    if (tableInfo) {
        const totalUsers = document.querySelectorAll('.user-row').length;
        tableInfo.textContent = `Showing ${visibleCount} of ${totalUsers} users`;
    }
}

function toggleEmptyState(show) {
    let emptyState = document.querySelector('.search-empty-state');
    
    if (show && !emptyState) {
        emptyState = document.createElement('div');
        emptyState.className = 'empty-state search-empty-state';
        emptyState.innerHTML = `
            <div class="empty-icon">
                <i class="fas fa-search"></i>
            </div>
            <h3>No Results Found</h3>
            <p>No users match your search criteria. Try adjusting your search terms.</p>
            <button onclick="document.getElementById('userSearch').value=''; performSearch('');" class="btn btn-secondary">
                <i class="fas fa-times"></i>
                Clear Search
            </button>
        `;
        
        const tableWrapper = document.querySelector('.table-wrapper');
        const table = tableWrapper.querySelector('.users-table');
        if (table) {
            table.style.display = 'none';
            tableWrapper.appendChild(emptyState);
        }
    } else if (!show && emptyState) {
        emptyState.remove();
        const table = document.querySelector('.users-table');
        if (table) {
            table.style.display = 'table';
        }
    }
}

// Keyboard shortcuts
function initializeKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + A to select all
        if ((e.ctrlKey || e.metaKey) && e.key === 'a' && !e.target.matches('input, textarea')) {
            e.preventDefault();
            const selectAll = document.getElementById('selectAll');
            if (selectAll) {
                selectAll.checked = true;
                toggleSelectAll();
            }
        }
        
        // Escape to clear selection
        if (e.key === 'Escape') {
            clearSelection();
        }
        
        // Ctrl/Cmd + F to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            document.getElementById('userSearch').focus();
        }
    });
}

// Initialize all functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeAlerts();
    initializeModals();
    initializeTooltips();
    initializeRowAnimations();
    initializeStatusBadges();
    initializeKeyboardShortcuts();
    initializeRealTimeUpdates();
    
    // Initialize current filter states
    const statusFilter = document.getElementById('statusFilter');
    const experienceFilter = document.getElementById('experienceFilter');
    
    if (statusFilter) {
        statusFilter.addEventListener('change', applyFilters);
    }
    
    if (experienceFilter) {
        experienceFilter.addEventListener('change', applyFilters);
    }
    
    // Apply URL-based filters on load
    const urlParams = new URLSearchParams(window.location.search);
    const statusParam = urlParams.get('status');
    const experienceParam = urlParams.get('experience');
    
    if (statusParam && statusFilter) {
        statusFilter.value = statusParam;
    }
    
    if (experienceParam && experienceFilter) {
        experienceFilter.value = experienceParam;
    }
});

// Export functions for external use
window.UserManagement = {
    search: performSearch,
    applyFilters: applyFilters,
    clearFilters: clearFilters,
    updateSelection: updateSelection,
    clearSelection: clearSelection
};
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>