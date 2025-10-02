<?php
require_once __DIR__ . '/../includes/functions.php';

// Require live_seller role
require_role('live_seller');

// Get current user info
$current_user = get_logged_in_user();

// Get seller dashboard stats
$db = getDB();

// Get user's total working days (count of attendance records)
$stmt = $db->prepare("
    SELECT COUNT(DISTINCT DATE(attendance_date)) as total_working_days 
    FROM seller_attendance 
    WHERE seller_id = ? AND status = 'completed'
");
$stmt->execute([$current_user['id']]);
$user_working_days = $stmt->fetch()['total_working_days'];

// Get user's total working hours
$stmt = $db->prepare("
    SELECT COALESCE(SUM(ats.duration_hours), 0) as total_working_hours
    FROM seller_attendance sa
    JOIN attendance_time_slots ats ON sa.time_slot_id = ats.id
    WHERE sa.seller_id = ? AND sa.status = 'completed'
");
$stmt->execute([$current_user['id']]);
$user_working_hours = $stmt->fetch()['total_working_hours'];

// Get all users with their stats for ranking
$stmt = $db->prepare("
    SELECT 
        u.id,
        u.full_name,
        COUNT(DISTINCT DATE(sa.attendance_date)) as working_days,
        COALESCE(SUM(ats.duration_hours), 0) as working_hours
    FROM users u
    LEFT JOIN seller_attendance sa ON u.id = sa.seller_id AND sa.status = 'completed'
    LEFT JOIN attendance_time_slots ats ON sa.time_slot_id = ats.id
    WHERE u.role = 'live_seller'
    GROUP BY u.id, u.full_name
    ORDER BY working_hours DESC, working_days DESC
");
$stmt->execute();
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
    <!-- Welcome Header -->
    <div class="dashboard-welcome">
        <div class="welcome-content">
            <div class="greeting-section">
                <div class="time-indicator">
                    <span class="time-icon">🌅</span>
                    <span class="greeting-text">Good <?php echo date('H') < 12 ? 'Morning' : (date('H') < 18 ? 'Afternoon' : 'Evening'); ?></span>
                </div>
                <h1 class="user-name"><?php echo htmlspecialchars($current_user['full_name']); ?></h1>
                <p class="role-badge">🎯 Live Seller</p>
            </div>
            <div class="current-date">
                <div class="date-display">
                    <span class="day"><?php echo date('d'); ?></span>
                    <div class="date-info">
                        <span class="month"><?php echo date('M'); ?></span>
                        <span class="year"><?php echo date('Y'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced User Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card working-days">
            <div class="card-background">
                <div class="bg-pattern"></div>
            </div>
            <div class="card-content">
                <div class="stat-header">
                    <div class="stat-icon-wrapper">
                        <span class="stat-icon">📅</span>
                    </div>
                    <div class="stat-trend">
                        <span class="trend-indicator positive">↗</span>
                    </div>
                </div>
                <div class="stat-body">
                    <h3 class="stat-title">Working Days</h3>
                    <div class="stat-value-container">
                        <span class="stat-value"><?php echo number_format($user_working_days); ?></span>
                        <span class="stat-unit">days</span>
                    </div>
                    <p class="stat-description">Total attendance recorded</p>
                </div>
            </div>
        </div>

        <div class="stat-card working-hours">
            <div class="card-background">
                <div class="bg-pattern"></div>
            </div>
            <div class="card-content">
                <div class="stat-header">
                    <div class="stat-icon-wrapper">
                        <span class="stat-icon">⏰</span>
                    </div>
                    <div class="stat-trend">
                        <span class="trend-indicator positive">↗</span>
                    </div>
                </div>
                <div class="stat-body">
                    <h3 class="stat-title">Working Hours</h3>
                    <div class="stat-value-container">
                        <span class="stat-value"><?php echo number_format($user_working_hours, 1); ?></span>
                        <span class="stat-unit">hrs</span>
                    </div>
                    <p class="stat-description">Total time commitment</p>
                </div>
            </div>
        </div>

        <div class="stat-card current-rank">
            <div class="card-background">
                <div class="bg-pattern"></div>
            </div>
            <div class="card-content">
                <div class="stat-header">
                    <div class="stat-icon-wrapper">
                        <span class="stat-icon">🏆</span>
                    </div>
                    <div class="rank-badge rank-<?php echo $current_user_rank <= 3 ? 'top' : 'standard'; ?>">
                        <?php if ($current_user_rank <= 3): ?>
                            <span class="crown">👑</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="stat-body">
                    <h3 class="stat-title">Current Rank</h3>
                    <div class="stat-value-container">
                        <span class="stat-value">#<?php echo $current_user_rank; ?></span>
                        <span class="stat-unit">of <?php echo count($all_users_rankings); ?></span>
                    </div>
                    <p class="stat-description"><?php echo $current_user_rank <= 3 ? 'Top performer!' : 'Keep pushing!'; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Live Sellers Ranking -->
    <div class="ranking-section">
        <div class="section-header">
            <div class="header-content">
                <h2 class="section-title">
                    <span class="title-icon">🏆</span>
                    Live Sellers Leaderboard
                </h2>
                <p class="section-subtitle">Performance rankings based on working hours and dedication</p>
            </div>
            <div class="header-stats">
                <div class="total-sellers">
                    <span class="count"><?php echo count($all_users_rankings); ?></span>
                    <span class="label">Active Sellers</span>
                </div>
            </div>
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
                                    <span class="avatar-initial"><?php echo strtoupper(substr($all_users_rankings[1]['full_name'], 0, 1)); ?></span>
                                </div>
                                <div class="medal-overlay silver-medal">🥈</div>
                            </div>
                            <div class="contestant-details">
                                <h4 class="contestant-name"><?php echo htmlspecialchars($all_users_rankings[1]['full_name']); ?></h4>
                                <div class="performance-stats">
                                    <span class="hours"><?php echo number_format($all_users_rankings[1]['working_hours'], 1); ?>h</span>
                                    <span class="days"><?php echo number_format($all_users_rankings[1]['working_days']); ?> days</span>
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
                                    <span class="avatar-initial"><?php echo strtoupper(substr($all_users_rankings[0]['full_name'], 0, 1)); ?></span>
                                </div>
                                <div class="medal-overlay gold-medal">🥇</div>
                            </div>
                            <div class="contestant-details">
                                <h4 class="contestant-name champion-name"><?php echo htmlspecialchars($all_users_rankings[0]['full_name']); ?></h4>
                                <div class="performance-stats">
                                    <span class="hours"><?php echo number_format($all_users_rankings[0]['working_hours'], 1); ?>h</span>
                                    <span class="days"><?php echo number_format($all_users_rankings[0]['working_days']); ?> days</span>
                                </div>
                                <div class="champion-badge">🎯 Champion</div>
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
                                    <span class="avatar-initial"><?php echo strtoupper(substr($all_users_rankings[2]['full_name'], 0, 1)); ?></span>
                                </div>
                                <div class="medal-overlay bronze-medal">🥉</div>
                            </div>
                            <div class="contestant-details">
                                <h4 class="contestant-name"><?php echo htmlspecialchars($all_users_rankings[2]['full_name']); ?></h4>
                                <div class="performance-stats">
                                    <span class="hours"><?php echo number_format($all_users_rankings[2]['working_hours'], 1); ?>h</span>
                                    <span class="days"><?php echo number_format($all_users_rankings[2]['working_days']); ?> days</span>
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
                                <div class="user-avatar">
                                    <div class="avatar-circle <?php echo $isCurrentUser ? 'current-user-avatar' : ''; ?>">
                                        <span class="avatar-initial"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></span>
                                    </div>
                                    <?php if ($isCurrentUser): ?>
                                        <div class="user-indicator">⭐</div>
                                    <?php endif; ?>
                                </div>
                                <div class="user-details">
                                    <h4 class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                                    <div class="user-metrics">
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
                                $performance_percent = $user['working_hours'] > 0 ? 
                                    min(100, ($user['working_hours'] / max(1, $all_users_rankings[0]['working_hours'])) * 100) : 0;
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