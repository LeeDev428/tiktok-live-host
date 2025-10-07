<?php
require_once __DIR__ . '/../includes/functions.php';

// Require admin role
require_role('admin');

// Get current user info
$current_user = get_logged_in_user();

// Get database connection
$db = getDB();

// Get current pay period
$current_period = get_current_pay_period();
$days_until_reset = get_days_until_reset();

// Fetch all live sellers with their performance data for the current pay period
$stmt = $db->prepare("
    SELECT 
        u.id,
        u.full_name,
        u.username,
        u.experienced_status,
        u.profile_image,
        COALESCE(SUM(a.solds_quantity), 0) as total_sales,
        COALESCE(SUM(a.hours_worked), 0) as total_hours,
        COUNT(DISTINCT a.attendance_date) as working_days
    FROM users u
    LEFT JOIN attendance a ON u.id = a.seller_id 
        AND a.status IN ('completed', 'checked_in')
        AND a.attendance_date BETWEEN :start_date AND :end_date
    WHERE u.role = 'live_seller' AND u.status = 'active'
    GROUP BY u.id, u.full_name, u.username, u.experienced_status, u.profile_image
    ORDER BY total_sales DESC, total_hours DESC
");
$stmt->execute([
    ':start_date' => $current_period['start_date'],
    ':end_date' => $current_period['end_date']
]);
$user_rankings = $stmt->fetchAll();

// Calculate hourly rates and total earned for each seller
foreach ($user_rankings as &$seller) {
    // Set hourly rate based on experience status
    $seller['hourly_rate'] = ($seller['experienced_status'] === 'tenured') ? 166 : 125;
    // Calculate total earned
    $seller['total_earned'] = $seller['total_hours'] * $seller['hourly_rate'];
}
unset($seller); // Break reference

// Calculate overall totals
$total_sales = array_sum(array_column($user_rankings, 'total_sales'));
$total_hours = array_sum(array_column($user_rankings, 'total_hours'));
$total_earned = array_sum(array_column($user_rankings, 'total_earned'));
$total_sellers = count($user_rankings);

$page_title = 'Admin Dashboard';
include 'layout/header.php';
?>

<div class="compact-admin-dashboard">
    <div class="dashboard-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="header-info">
                <h1>Live Host Performance Dashboard</h1>
                <p>Track and analyze live seller performance metrics</p>
            </div>
            <div class="header-stats">
                <div class="stat-badge">
                    <span class="stat-value"><?php echo $total_sellers; ?></span>
                    <span class="stat-label">Active Sellers</span>
                </div>
                <button class="manage-users-btn" onclick="location.href='users.php'">
                    <span class="btn-icon">👥</span>
                    Create User 
                </button>
            </div>
        </div>

        <!-- Performance Ranking Card -->
        <div class="ranking-card">  
            <div class="card-header">
                <div class="title-section">
                    <h2>🏆 Performance Rankings</h2>
                    <p>Sorted by highest total sales</p>
                </div>
                
                <!-- Pay Period Info - Integrated in Header -->
                <div class="header-period-info">
                    <div class="period-badge">
                        <div class="period-icon-small">📅</div>
                        <div class="period-text">
                            <span class="period-label">Current Period:</span>
                            <span class="period-value"><?php echo $current_period['period_name']; ?></span>
                        </div>
                    </div>
                    <div class="period-countdown-small">
                        <div class="countdown-number"><?php echo $days_until_reset; ?></div>
                        <div class="countdown-text">day<?php echo $days_until_reset != 1 ? 's' : ''; ?> left</div>
                    </div>
                </div>
            </div>

            <div class="performance-table">
                <!-- Table Headers -->
                <div class="table-headers">
                    <div class="header-col user-col">RANK & USER</div>
                    <div class="header-col exp-col">EXPERIENCE</div>
                    <div class="header-col sales-col">TOTAL SALES</div>
                    <div class="header-col hours-col">HOURS WORKED</div>
                    <div class="header-col rate-col">HOURLY RATE</div>
                    <div class="header-col earned-col">TOTAL SALARY</div>
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
                                        <?php if ($rank <= 3): ?>
                                            <?php echo $rank == 1 ? '🥇' : ($rank == 2 ? '🥈' : '🥉'); ?>
                                        <?php else: ?>
                                            #<?php echo $rank; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="user-profile">
                                        <div class="user-details">
                                            <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                            <div class="user-handle">@<?php echo htmlspecialchars($user['username']); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Experience Status -->
                                <div class="exp-cell">
                                    <span class="exp-badge <?php echo $user['experienced_status']; ?>">
                                        <?php echo ucfirst($user['experienced_status']); ?>
                                    </span>
                                </div>

                                <!-- Sales Info -->
                                <div class="sales-cell">
                                    <div class="primary-value"><?php echo number_format($user['total_sales']); ?></div>
                                    <div class="secondary-value">items sold</div>
                                </div>

                                <!-- Hours Info -->
                                <div class="hours-cell">
                                    <div class="primary-value"><?php echo number_format($user['total_hours'], 1); ?>h</div>
                                    <div class="secondary-value"><?php echo $user['working_days']; ?> days</div>
                                </div>

                                <!-- Rate Info -->
                                <div class="rate-cell">
                                    <div class="primary-value">₱<?php echo number_format($user['hourly_rate']); ?></div>
                                    <div class="secondary-value">per hour</div>
                                </div>

                                <!-- Earned Info -->
                                <div class="earned-cell highlight">
                                    <div class="primary-value">₱<?php echo number_format($user['total_earned'], 2); ?></div>
                                    <div class="secondary-value">total salary</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Overall Totals -->
                <div class="totals-section">
                    <div class="totals-left">
                        <div class="totals-title">Overall Totals</div>
                        <div class="totals-subtitle"><?php echo $total_sellers; ?> Active Sellers</div>
                    </div>
                    <div class="totals-spacer"></div>
                    <div class="totals-sales">
                        <div class="totals-value"><?php echo number_format($total_sales); ?></div>
                        <div class="totals-label">TOTAL SALES</div>
                    </div>
                    <div class="totals-spacer"></div>
                    <div class="totals-spacer"></div>
                    <div class="totals-earned">
                        <div class="totals-value">₱<?php echo number_format($total_earned, 2); ?></div>
                        <div class="totals-label">Overall SALARY</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>