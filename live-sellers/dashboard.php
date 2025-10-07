<?php
require_once __DIR__ . '/../includes/functions.php';

// Require live_seller role
require_role('live_seller');

// Helper function to get correct profile image path
function get_profile_image_path($profile_image) {
    if (empty($profile_image)) {
        return '';
    }
    // If it already contains 'uploads/profiles/', just prepend ../
    if (strpos($profile_image, 'uploads/profiles/') === 0) {
        return '../' . $profile_image;
    }
    // Otherwise, it's just the filename, so add the full path
    return '../uploads/profiles/' . $profile_image;
}

// Get current user info with experienced_status
$db = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();

// Get current pay period
$current_period = get_current_pay_period();
$days_until_reset = get_days_until_reset();

// Get seller dashboard stats for current pay period

// Get user's total working days
$stmt = $db->prepare("
    SELECT COUNT(DISTINCT attendance_date) as total_working_days 
    FROM attendance 
    WHERE seller_id = ? AND status IN ('completed', 'checked_in')
        AND attendance_date BETWEEN ? AND ?
");
$stmt->execute([$current_user['id'], $current_period['start_date'], $current_period['end_date']]);
$user_working_days = $stmt->fetch()['total_working_days'] ?? 0;

// Get user's total working hours
$stmt = $db->prepare("
    SELECT COALESCE(SUM(hours_worked), 0) as total_working_hours
    FROM attendance
    WHERE seller_id = ? AND status IN ('completed', 'checked_in')
        AND attendance_date BETWEEN ? AND ?
");
$stmt->execute([$current_user['id'], $current_period['start_date'], $current_period['end_date']]);
$user_working_hours = $stmt->fetch()['total_working_hours'] ?? 0;

// Get user's total sales
$stmt = $db->prepare("
    SELECT COALESCE(SUM(solds_quantity), 0) as total_sales
    FROM attendance
    WHERE seller_id = ? AND status IN ('completed', 'checked_in')
        AND attendance_date BETWEEN ? AND ?
");
$stmt->execute([$current_user['id'], $current_period['start_date'], $current_period['end_date']]);
$user_total_sales = $stmt->fetch()['total_sales'] ?? 0;

// Get all users with their stats for ranking (based on total sales) for current pay period
$stmt = $db->prepare("
    SELECT 
        u.id,
        u.full_name,
        u.profile_image,
        COUNT(DISTINCT a.attendance_date) as working_days,
        COALESCE(SUM(a.hours_worked), 0) as working_hours,
        COALESCE(SUM(a.solds_quantity), 0) as total_sales
    FROM users u
    LEFT JOIN attendance a ON u.id = a.seller_id 
        AND a.status IN ('completed', 'checked_in')
        AND a.attendance_date BETWEEN ? AND ?
    WHERE u.role = 'live_seller' AND u.status = 'active'
    GROUP BY u.id, u.full_name
    ORDER BY total_sales DESC, working_hours DESC, working_days DESC
");
$stmt->execute([$current_period['start_date'], $current_period['end_date']]);
$all_users_rankings = $stmt->fetchAll();

// Find current user's rank
$current_user_rank = 0;
foreach ($all_users_rankings as $index => $user) {
    if ($user['id'] == $current_user['id']) {
        $current_user_rank = $index + 1;
        break;
    }
}

$page_title = 'Live Seller Dashboard';
include 'layout/header.php';
?>

<div class="enhanced-dashboard">
    <!-- User Performance Card/Form -->
    <div class="user-performance-card">
        <div class="card-header-section">
            <div class="header-left">
                <div class="header-icon">
                    <span class="icon-emoji">👤</span>
                </div>
                <div class="header-text">
                    <h2 class="card-title">My Performance Dashboard</h2>
                    <p class="card-subtitle">Track your progress and achievements</p>
                </div>
            </div>
            <div class="header-right">
                <!-- Compact Pay Period Info -->
                <div class="compact-period-info">
                    <div class="period-badge">
                        <div class="period-icon-small">📅</div>
                        <div class="period-text">
                            <span class="period-label">CURRENT PERIOD:</span>
                            <span class="period-value"><?php echo $current_period['period_name']; ?></span>
                        </div>
                    </div>
                    <div class="countdown-badge">
                        <div class="countdown-number"><?php echo $days_until_reset; ?></div>
                        <div class="countdown-text">DAYS LEFT</div>
                    </div>
                </div>
            </div>
        </div>
               
        <div class="performance-form">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <span class="label-icon">👤</span>
                        Full Name
                    </label>
                    <div class="form-value">
                        <?php echo htmlspecialchars($current_user['full_name']); ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <span class="label-icon">🎯</span>
                        Experience Status
                    </label>
                    <div class="form-value">
                        <span class="role-badge-inline <?php echo $current_user['experienced_status'] === 'tenured' ? 'tenured-badge' : 'newbie-badge'; ?>">
                            <?php echo ucfirst($current_user['experienced_status']); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <span class="label-icon">📅</span>
                        Total Working Days
                    </label>
                    <div class="form-value highlight-value">
                        <span class="value-number"><?php echo number_format($user_working_days); ?></span>
                        <span class="value-unit">days</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <span class="label-icon">⏰</span>
                        Total Working Hours
                    </label>
                    <div class="form-value highlight-value">
                        <span class="value-number"><?php echo number_format($user_working_hours, 1); ?></span>
                        <span class="value-unit">hours</span>
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <span class="label-icon">💰</span>
                        Total Sales
                    </label>
                    <div class="form-value highlight-value sales-value">
                        <span class="value-number"><?php echo number_format($user_total_sales); ?></span>
                        <span class="value-unit">items</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <span class="label-icon">🏆</span>
                        Current Ranking
                    </label>
                    <div class="form-value highlight-value rank-value">
                        <span class="rank-display">
                            <span class="rank-number">#<?php echo $current_user_rank; ?></span>
                            <span class="rank-total">of <?php echo count($all_users_rankings); ?></span>
                        </span>
                        <?php if ($current_user_rank <= 3): ?>
                            <span class="rank-crown">👑</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="performance-footer">
                <div class="performance-message">
                    <?php if ($current_user_rank == 1): ?>
                        <span class="message-icon">🎉</span>
                        <span class="message-text">Congratulations! You're currently ranked #1!</span>
                    <?php elseif ($current_user_rank <= 3): ?>
                        <span class="message-icon">🌟</span>
                        <span class="message-text">Amazing! You're in the top 3 performers!</span>
                    <?php elseif ($current_user_rank <= 10): ?>
                        <span class="message-icon">💪</span>
                        <span class="message-text">Great job! You're in the top 10!</span>
                    <?php else: ?>
                        <span class="message-icon">🚀</span>
                        <span class="message-text">Keep pushing! You can climb higher!</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Live Sellers Ranking -->
    <div class="ranking-section">
        <div class="section-header">
            <div class="header-content">
                <img src="<?php echo $base; ?>/tik-tok.png" alt="TikTok" class="ranking-tiktok-logo">
                <h2 class="section-title">
                    Live Host Ranking
                </h2>
            </div>
        </div>
        
        <!-- Congratulations Banner -->
        <div class="congratulations-banner">
            <div class="congrats-text">CONGRATULATIONS!</div>
            <div class="congrats-subtitle">Live Ranking Highest Solds</div>
            <div class="congrats-date">Month of <?php echo date('F Y'); ?></div>
        </div>
        
        <!-- Enhanced Top 3 Podium -->
        <div class="podium-container">
            <div class="podium-background">
                <div class="bg-sparkle"></div>
                <div class="bg-rays"></div>
            </div>
            
            <div class="podium-positions">
                <?php if (count($all_users_rankings) >= 2): ?>
                    <!-- Rank 2 -->
                    <div class="podium-position rank-2 animate-rise" style="animation-delay: 0.3s">
                        <div class="position-platform">
                            <div class="platform-height silver-platform"></div>
                            <div class="platform-base">2</div>
                        </div>
                        <div class="contestant-info">
                            <div class="profile-circle silver-circle">
                                <div class="circle-glow silver-glow"></div>
                                <div class="avatar-content">
                                    <?php if (!empty($all_users_rankings[1]['profile_image'])): ?>
                                        <img src="<?php echo htmlspecialchars(get_profile_image_path($all_users_rankings[1]['profile_image'])); ?>" alt="Profile" class="profile-image">
                                    <?php else: ?>
                                        <span class="avatar-initial"><?php echo strtoupper(substr($all_users_rankings[1]['full_name'], 0, 1)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="medal-overlay silver-medal">🥈</div>
                            </div>
                            <div class="contestant-details">
                                <h4 class="contestant-name"><?php echo htmlspecialchars($all_users_rankings[1]['full_name']); ?></h4>
                                <div class="performance-stats">
                                    <span class="hours">💰 <?php echo number_format($all_users_rankings[1]['total_sales']); ?> sales</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (count($all_users_rankings) >= 1): ?>
                    <!-- Rank 1 -->
                    <div class="podium-position rank-1 animate-rise" style="animation-delay: 0.1s">
                        <div class="champion-crown">
                            <span class="crown-icon">👑</span>
                            <div class="crown-sparkle"></div>
                        </div>
                        <div class="position-platform">
                            <div class="platform-height gold-platform"></div>
                            <div class="platform-base champion">1</div>
                        </div>
                        <div class="contestant-info">
                            <div class="profile-circle gold-circle">
                                <div class="circle-glow gold-glow"></div>
                                <div class="avatar-content">
                                    <?php if (!empty($all_users_rankings[0]['profile_image'])): ?>
                                        <img src="<?php echo htmlspecialchars(get_profile_image_path($all_users_rankings[0]['profile_image'])); ?>" alt="Profile" class="profile-image">
                                    <?php else: ?>
                                        <span class="avatar-initial"><?php echo strtoupper(substr($all_users_rankings[0]['full_name'], 0, 1)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="medal-overlay gold-medal">🥇</div>
                            </div>
                            <div class="contestant-details">
                                <h4 class="contestant-name champion-name"><?php echo htmlspecialchars($all_users_rankings[0]['full_name']); ?></h4>
                                <div class="performance-stats">
                                    <span class="hours">💰 <?php echo number_format($all_users_rankings[0]['total_sales']); ?> sales</span>
                                </div>
                                <div class="champion-badge">🎯 Top Seller</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (count($all_users_rankings) >= 3): ?>
                    <!-- Rank 3 -->
                    <div class="podium-position rank-3 animate-rise" style="animation-delay: 0.5s">
                        <div class="position-platform">
                            <div class="platform-height bronze-platform"></div>
                            <div class="platform-base">3</div>
                        </div>
                        <div class="contestant-info">
                            <div class="profile-circle bronze-circle">
                                <div class="circle-glow bronze-glow"></div>
                                <div class="avatar-content">
                                    <?php if (!empty($all_users_rankings[2]['profile_image'])): ?>
                                        <img src="<?php echo htmlspecialchars(get_profile_image_path($all_users_rankings[2]['profile_image'])); ?>" alt="Profile" class="profile-image">
                                    <?php else: ?>
                                        <span class="avatar-initial"><?php echo strtoupper(substr($all_users_rankings[2]['full_name'], 0, 1)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="medal-overlay bronze-medal">🥉</div>
                            </div>
                            <div class="contestant-details">
                                <h4 class="contestant-name"><?php echo htmlspecialchars($all_users_rankings[2]['full_name']); ?></h4>
                                <div class="performance-stats">
                                    <span class="hours">💰 <?php echo number_format($all_users_rankings[2]['total_sales']); ?> sales</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Enhanced Remaining Rankings -->
        <?php if (count($all_users_rankings) > 3): ?>
            <div class="extended-rankings">
                <div class="rankings-header">
                    <h3 class="rankings-title">
                        <span class="title-icon">📊</span>
                        Complete Leaderboard
                    </h3>
                    <div class="rankings-count">
                        <span class="count"><?php echo count($all_users_rankings) - 3; ?></span>
                        <span class="label">more sellers</span>
                    </div>
                </div>
                
                <div class="rankings-list">
                    <?php for ($i = 3; $i < count($all_users_rankings); $i++): ?>
                        <?php $user = $all_users_rankings[$i]; ?>
                        <?php $isCurrentUser = $user['id'] == $current_user['id']; ?>
                        <div class="ranking-row <?php echo $isCurrentUser ? 'current-user-row' : ''; ?> animate-slide-in" style="animation-delay: <?php echo ($i - 3) * 0.05; ?>s">
                            <?php if ($isCurrentUser): ?>
                                <div class="user-highlight">
                                    <span class="highlight-label">You</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="rank-position">
                                <span class="rank-number"><?php echo $i + 1; ?></span>
                                <?php if ($i + 1 <= 10): ?>
                                    <span class="top-ten-badge">TOP 10</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="user-profile">
                                <div class="user-details">
                                    <h4 class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                                    <div class="user-metrics">
                                        <div class="metric">
                                            <span class="metric-icon">💰</span>
                                            <span class="metric-value"><?php echo number_format($user['total_sales']); ?> sales</span>
                                        </div>
                                        <div class="metric">
                                            <span class="metric-icon">⏰</span>
                                            <span class="metric-value"><?php echo number_format($user['working_hours'], 1); ?>h</span>
                                        </div>
                                        <div class="metric">
                                            <span class="metric-icon">📅</span>
                                            <span class="metric-value"><?php echo number_format($user['working_days']); ?> days</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="performance-indicator">
                                <?php 
                                $performance_percent = $user['total_sales'] > 0 && $all_users_rankings[0]['total_sales'] > 0 ? 
                                    min(100, ($user['total_sales'] / $all_users_rankings[0]['total_sales']) * 100) : 0;
                                ?>
                                <div class="progress-ring">
                                    <svg class="progress-svg" width="40" height="40">
                                        <circle class="progress-circle-bg" cx="20" cy="20" r="15"></circle>
                                        <circle class="progress-circle" cx="20" cy="20" r="15" 
                                                style="stroke-dasharray: <?php echo $performance_percent * 0.94; ?> 100"></circle>
                                    </svg>
                                    <span class="progress-text"><?php echo round($performance_percent); ?>%</span>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'layout/footer.php'; ?>