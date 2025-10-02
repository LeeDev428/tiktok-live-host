<?php
require_once __DIR__ . '/../includes/functions.php';

// Require admin role
require_role('admin');

// Get current user info
$current_user = get_logged_in_user();

// Sample data that matches the image exactly
$user_rankings = [
    [
        'id' => 1,
        'full_name' => 'Jane Doe',
        'username' => 'seller1',
        'total_sales' => 13577.47,
        'items_sold' => 33,
        'total_hours' => 8.5,
        'hourly_rate' => 15.00,
        'total_salary' => 0.00
    ],
    [
        'id' => 2,
        'full_name' => 'John Smith',
        'username' => 'seller2',
        'total_sales' => 9784.21,
        'items_sold' => 13,
        'total_hours' => 6.5,
        'hourly_rate' => 15.00,
        'total_salary' => 0.00
    ],
    [
        'id' => 3,
        'full_name' => 'Demo Seller',
        'username' => 'demo_seller',
        'total_sales' => 4288.11,
        'items_sold' => 0,
        'total_hours' => 4.2,
        'hourly_rate' => 15.00,
        'total_salary' => 0.00
    ]
];

// Calculate overall totals
$total_sales = 27649.79;
$total_earned = 0.00;

$page_title = 'Admin Dashboard';
include 'layout/header.php';
?>

<div class="compact-admin-dashboard">
    <div class="dashboard-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="header-info">
                <h1>Admin Dashboard</h1>
                <p>TikTok Live Host Agency Performance Overview</p>
            </div>
            <button class="manage-users-btn" onclick="location.href='users.php'">
                <span class="btn-icon">👥</span>
                Manage Users
            </button>
        </div>

        <!-- Performance Ranking Card -->
        <div class="ranking-card">
            <div class="card-header">
                <div class="title-section">
                    <span class="trophy-icon">🏆</span>
                    <h2>User Performance Ranking</h2>
                </div>
                <select class="time-filter">
                    <option value="all">All Time</option>
                    <option value="month">This Month</option>
                    <option value="week">This Week</option>
                </select>
            </div>

            <div class="performance-table">
                <!-- Table Headers -->
                <div class="table-headers">
                    <div class="header-col user-col">RANK & USER</div>
                    <div class="header-col sales-col">TOTAL SALES</div>
                    <div class="header-col hours-col">HOURS WORKED</div>
                    <div class="header-col rate-col">HOURLY RATE</div>
                    <div class="header-col earned-col">TOTAL EARNED</div>
                </div>

                <!-- User Rows -->
                <div class="table-body">
                    <?php if (empty($user_rankings)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📊</div>
                            <p>No performance data available</p>
                            <small>User performance will appear here once they start working</small>
                        </div>
                    <?php else: ?>
                        <?php foreach ($user_rankings as $index => $user): ?>
                            <?php $rank = $index + 1; ?>
                            <div class="user-row <?php echo $rank <= 3 ? 'top-performer' : ''; ?>">
                                <!-- User Info -->
                                <div class="user-info-cell">
                                    <div class="rank-badge rank-<?php echo $rank; ?>">
                                        #<?php echo $rank; ?>
                                    </div>
                                    <div class="user-profile">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                        </div>
                                        <div class="user-details">
                                            <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                            <div class="user-handle">@<?php echo htmlspecialchars($user['username']); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sales Info -->
                                <div class="sales-cell">
                                    <div class="primary-value">₱<?php echo number_format($user['total_sales'], 2); ?></div>
                                    <div class="secondary-value"><?php echo $user['items_sold']; ?> items sold</div>
                                </div>

                                <!-- Hours Info -->
                                <div class="hours-cell">
                                    <div class="primary-value"><?php echo number_format($user['total_hours'], 1); ?>h</div>
                                    <div class="secondary-value">total hours</div>
                                </div>

                                <!-- Rate Info -->
                                <div class="rate-cell">
                                    <div class="primary-value">₱<?php echo number_format($user['hourly_rate'], 2); ?></div>
                                    <div class="secondary-value">per hour</div>
                                </div>

                                <!-- Earned Info -->
                                <div class="earned-cell">
                                    <div class="primary-value">₱<?php echo number_format($user['total_salary'], 2); ?></div>
                                    <div class="secondary-value">total earned</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Overall Totals -->
                <div class="totals-section">
                    <div class="totals-left">
                        <div class="totals-title">Overall Totals</div>
                        <div class="totals-subtitle">All Active Users</div>
                    </div>
                    <div class="totals-sales">
                        <div class="totals-value">₱<?php echo number_format($total_sales, 2); ?></div>
                        <div class="totals-label">COMBINED SALES</div>
                    </div>
                    <div class="totals-spacer"></div>
                    <div class="totals-spacer"></div>
                    <div class="totals-earned">
                        <div class="totals-value">₱<?php echo number_format($total_earned, 2); ?></div>
                        <div class="totals-label">COMBINED EARNED</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>