<?php
require_once __DIR__ . '/../includes/functions.php';

// Require admin role
require_role('admin');

// Get current user info
$current_user = get_logged_in_user();

// Get dashboard stats
$db = getDB();

// Count total users
$stmt = $db->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetch()['total'];

// Count active live sellers
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'live_seller' AND status = 'active'");
$active_sellers = $stmt->fetch()['total'];

// Count recent activity (last 24 hours)
$stmt = $db->query("SELECT COUNT(*) as total FROM activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$recent_activity = $stmt->fetch()['total'];

// Get live host sales data
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Today's total sales
$stmt = $db->query("
    SELECT COALESCE(SUM(total_sales), 0) as total_sales, 
           COALESCE(SUM(total_items_sold), 0) as total_items 
    FROM live_host_daily_summary 
    WHERE summary_date = '$today'
");
$today_sales = $stmt->fetch();

// Yesterday's total sales for comparison
$stmt = $db->query("
    SELECT COALESCE(SUM(total_sales), 0) as total_sales 
    FROM live_host_daily_summary 
    WHERE summary_date = '$yesterday'
");
$yesterday_sales = $stmt->fetch()['total_sales'];

// Calculate growth percentage
$sales_growth = 0;
if ($yesterday_sales > 0) {
    $sales_growth = (($today_sales['total_sales'] - $yesterday_sales) / $yesterday_sales) * 100;
}

// Get live host performance data
$stmt = $db->query("
    SELECT u.id, u.username, u.full_name,
           COALESCE(SUM(lhs.total_amount), 0) as total_sold,
           COALESCE(SUM(lhs.quantity), 0) as items_sold,
           COALESCE(SUM(lhs.commission_amount), 0) as total_commission,
           COUNT(DISTINCT DATE(lhs.sale_date)) as active_days
    FROM users u
    LEFT JOIN live_host_sales lhs ON u.id = lhs.seller_id 
        AND lhs.status = 'confirmed' 
        AND lhs.sale_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    WHERE u.role = 'live_seller' AND u.status = 'active'
    GROUP BY u.id, u.username, u.full_name
    ORDER BY total_sold DESC
");
$live_host_performance = $stmt->fetchAll();

// Get recent sales for activity feed
$stmt = $db->query("
    SELECT lhs.*, u.username, u.full_name, p.name as product_name
    FROM live_host_sales lhs
    JOIN users u ON lhs.seller_id = u.id
    LEFT JOIN products p ON lhs.product_id = p.id
    WHERE lhs.status IN ('confirmed', 'pending')
    ORDER BY lhs.sale_date DESC
    LIMIT 10
");
$recent_sales = $stmt->fetchAll();

// Get recent login attempts
$stmt = $db->query("
    SELECT al.*, u.username, u.full_name 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    WHERE al.action IN ('login', 'failed_login') 
    ORDER BY al.created_at DESC 
    LIMIT 10
");
$recent_logins = $stmt->fetchAll();

$page_title = 'Admin Dashboard';
include 'layout/header.php';
?>

<div class="dashboard-container">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="header-content">
            <h1>Welcome back, <?php echo htmlspecialchars($current_user['full_name']); ?>! 👋</h1>
            <p>Here's what's happening with your TikTok Live Host Agency today.</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="location.href='users.php'">
                <span class="btn-icon">👥</span>
                Manage Users
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <h3><?php echo $total_users; ?></h3>
                <p>Total Users</p>
            </div>
            <div class="stat-trend positive">+5%</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🎯</div>
            <div class="stat-content">
                <h3><?php echo $active_sellers; ?></h3>
                <p>Active Sellers</p>
            </div>
            <div class="stat-trend positive">+12%</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">�</div>
            <div class="stat-content">
                <h3>$<?php echo number_format($today_sales['total_sales'], 2); ?></h3>
                <p>Today's Sales</p>
            </div>
            <div class="stat-trend <?php echo $sales_growth >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo $sales_growth >= 0 ? '+' : ''; ?><?php echo number_format($sales_growth, 1); ?>%
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-content">
                <h3><?php echo $today_sales['total_items']; ?></h3>
                <p>Items Sold Today</p>
            </div>
            <div class="stat-trend positive">+<?php echo $recent_activity; ?></div>
        </div>
    </div>

    <!-- Live Host Sales Section -->
    <div class="dashboard-card live-host-section">
        <div class="card-header">
            <h3>📺 LIVE HOST Performance</h3>
            <div class="header-actions">
                <select id="timeRange" class="select-filter">
                    <option value="30">Last 30 Days</option>
                    <option value="7">Last 7 Days</option>
                    <option value="1">Today</option>
                </select>
            </div>
        </div>
        <div class="card-content">
            <?php if (empty($live_host_performance)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📊</div>
                    <p>No sales data available</p>
                    <small>Live host sales will appear here once they start selling</small>
                </div>
            <?php else: ?>
                <div class="live-host-grid">
                    <div class="host-headers">
                        <div class="header-item">HOST</div>
                        <div class="header-item">SOLD</div>
                        <div class="header-item">TOTAL</div>
                        <div class="header-item">COMMISSION</div>
                        <div class="header-item">DAYS ACTIVE</div>
                    </div>
                    
                    <?php foreach ($live_host_performance as $index => $host): ?>
                        <div class="host-row <?php echo $index < 3 ? 'top-performer' : ''; ?>">
                            <div class="host-info">
                                <div class="host-avatar">
                                    <?php echo strtoupper(substr($host['full_name'], 0, 1)); ?>
                                </div>
                                <div class="host-details">
                                    <div class="host-name"><?php echo htmlspecialchars($host['full_name']); ?></div>
                                    <div class="host-username">@<?php echo htmlspecialchars($host['username']); ?></div>
                                </div>
                                <?php if ($index === 0): ?>
                                    <div class="performance-badge top">🏆 #1</div>
                                <?php elseif ($index === 1): ?>
                                    <div class="performance-badge second">🥈 #2</div>
                                <?php elseif ($index === 2): ?>
                                    <div class="performance-badge third">🥉 #3</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="host-stat sold">
                                <div class="stat-number"><?php echo $host['items_sold']; ?></div>
                                <div class="stat-label">items</div>
                            </div>
                            
                            <div class="host-stat total">
                                <div class="stat-number">$<?php echo number_format($host['total_sold'], 2); ?></div>
                                <div class="stat-label">revenue</div>
                            </div>
                            
                            <div class="host-stat commission">
                                <div class="stat-number">$<?php echo number_format($host['total_commission'], 2); ?></div>
                                <div class="stat-label">earned</div>
                            </div>
                            
                            <div class="host-stat days">
                                <div class="stat-number"><?php echo $host['active_days']; ?></div>
                                <div class="stat-label">days</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="dashboard-grid">
        <!-- Recent Sales Activity -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Recent Sales Activity</h3>
                <a href="sales.php" class="view-all-link">View All</a>
            </div>
            <div class="card-content">
                <?php if (empty($recent_sales)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">�</div>
                        <p>No recent sales</p>
                        <small>Sales activity will appear here</small>
                    </div>
                <?php else: ?>
                    <div class="activity-list">
                        <?php foreach ($recent_sales as $sale): ?>
                            <div class="activity-item">
                                <div class="activity-avatar">
                                    <?php echo strtoupper(substr($sale['full_name'], 0, 1)); ?>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-header">
                                        <span class="activity-user"><?php echo htmlspecialchars($sale['full_name']); ?></span>
                                        <span class="activity-action">sold</span>
                                        <span class="activity-target"><?php echo $sale['quantity']; ?>x <?php echo htmlspecialchars($sale['product_name'] ?? $sale['product_name']); ?></span>
                                    </div>
                                    <div class="activity-meta">
                                        <span class="activity-amount">$<?php echo number_format($sale['total_amount'], 2); ?></span>
                                        <span class="activity-time"><?php echo date('M j, g:i A', strtotime($sale['sale_date'])); ?></span>
                                        <span class="status-badge status-<?php echo $sale['status'] === 'confirmed' ? 'success' : 'pending'; ?>">
                                            <?php echo ucfirst($sale['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Quick Actions</h3>
            </div>
            <div class="card-content">
                <div class="quick-actions">
                    <a href="users.php?action=add" class="quick-action">
                        <div class="action-icon">➕</div>
                        <div class="action-content">
                            <h4>Add New User</h4>
                            <p>Create a new user account</p>
                        </div>
                    </a>

                    <a href="streams.php" class="quick-action">
                        <div class="action-icon">📺</div>
                        <div class="action-content">
                            <h4>Manage Streams</h4>
                            <p>View and manage live streams</p>
                        </div>
                    </a>

                    <a href="reports.php" class="quick-action">
                        <div class="action-icon">📊</div>
                        <div class="action-content">
                            <h4>View Reports</h4>
                            <p>Analytics and performance reports</p>
                        </div>
                    </a>

                    <a href="settings.php" class="quick-action">
                        <div class="action-icon">⚙️</div>
                        <div class="action-content">
                            <h4>System Settings</h4>
                            <p>Configure platform settings</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>