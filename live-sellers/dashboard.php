<?php
require_once __DIR__ . '/../includes/functions.php';

// Require live_seller role
require_role('live_seller');

// Get current user info
$current_user = get_logged_in_user();

// Get seller dashboard stats
$db = getDB();

// Count seller's streams
$stmt = $db->prepare("SELECT COUNT(*) as total FROM live_streams WHERE seller_id = ?");
$stmt->execute([$current_user['id']]);
$total_streams = $stmt->fetch()['total'];

// Count active streams
$stmt = $db->prepare("SELECT COUNT(*) as total FROM live_streams WHERE seller_id = ? AND status = 'live'");
$stmt->execute([$current_user['id']]);
$active_streams = $stmt->fetch()['total'];

// Get recent streams
$stmt = $db->prepare("
    SELECT * FROM live_streams 
    WHERE seller_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$current_user['id']]);
$recent_streams = $stmt->fetchAll();

$page_title = 'Live Seller Dashboard';
include 'layout/header.php';
?>

<div class="dashboard-container">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="header-content">
            <h1>Welcome back, <?php echo htmlspecialchars($current_user['full_name']); ?>! 🎯</h1>
            <p>Ready to create engaging live streams and connect with your audience?</p>
            <div class="header-notice">
                <span class="notice-icon">📅</span>
                <span>Manage your schedule and attendance in the <a href="schedule.php" class="schedule-link">Schedule</a> section</span>
            </div>
        </div>
        <div class="header-actions">
            <a href="schedule.php" class="btn btn-secondary">
                <span class="btn-icon">📅</span>
                Schedule & Attendance
            </a>
            <button class="btn btn-primary" onclick="location.href='stream-new.php'">
                <span class="btn-icon">📺</span>
                Start New Stream
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📺</div>
            <div class="stat-content">
                <h3><?php echo $total_streams; ?></h3>
                <p>Total Streams</p>
            </div>
            <div class="stat-trend positive">+3</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🔴</div>
            <div class="stat-content">
                <h3><?php echo $active_streams; ?></h3>
                <p>Live Now</p>
            </div>
            <div class="stat-trend <?php echo $active_streams > 0 ? 'positive' : 'neutral'; ?>">
                <?php echo $active_streams > 0 ? 'LIVE' : 'OFF'; ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <h3>2.4K</h3>
                <p>Total Viewers</p>
            </div>
            <div class="stat-trend positive">+18%</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <h3>$1,250</h3>
                <p>This Month</p>
            </div>
            <div class="stat-trend positive">+22%</div>
        </div>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Main Content Grid -->
    <div class="dashboard-grid">
        <!-- Recent Streams -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Recent Streams</h3>
                <a href="streams.php" class="view-all-link">View All</a>
            </div>
            <div class="card-content">
                <?php if (empty($recent_streams)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📺</div>
                        <p>No streams yet</p>
                        <a href="stream-new.php" class="btn btn-primary">Create Your First Stream</a>
                    </div>
                <?php else: ?>
                    <div class="streams-list">
                        <?php foreach ($recent_streams as $stream): ?>
                            <div class="stream-item">
                                <div class="stream-status <?php echo $stream['status']; ?>">
                                    <?php
                                    $status_icons = [
                                        'live' => '🔴',
                                        'scheduled' => '⏰',
                                        'ended' => '✅',
                                        'cancelled' => '❌'
                                    ];
                                    echo $status_icons[$stream['status']] ?? '📺';
                                    ?>
                                </div>
                                <div class="stream-content">
                                    <h4><?php echo htmlspecialchars($stream['title']); ?></h4>
                                    <p class="stream-description"><?php echo htmlspecialchars($stream['description'] ?? 'No description'); ?></p>
                                    <div class="stream-meta">
                                        <span class="status-badge status-<?php echo $stream['status']; ?>">
                                            <?php echo ucfirst($stream['status']); ?>
                                        </span>
                                        <span class="stream-date">
                                            <?php echo date('M j, Y g:i A', strtotime($stream['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="stream-actions">
                                    <?php if ($stream['status'] === 'live'): ?>
                                        <a href="stream-manage.php?id=<?php echo $stream['id']; ?>" class="action-btn live">
                                            Manage
                                        </a>
                                    <?php elseif ($stream['status'] === 'scheduled'): ?>
                                        <a href="stream-edit.php?id=<?php echo $stream['id']; ?>" class="action-btn edit">
                                            Edit
                                        </a>
                                    <?php else: ?>
                                        <a href="stream-view.php?id=<?php echo $stream['id']; ?>" class="action-btn view">
                                            View
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions & Performance -->
        <div class="sidebar-cards">
            <!-- Quick Actions -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="card-content">
                    <div class="quick-actions">
                        <a href="stream-new.php" class="quick-action">
                            <div class="action-icon">📺</div>
                            <div class="action-content">
                                <h4>New Stream</h4>
                                <p>Start streaming now</p>
                            </div>
                        </a>

                        <a href="schedule.php" class="quick-action">
                            <div class="action-icon">📅</div>
                            <div class="action-content">
                                <h4>Schedule Stream</h4>
                                <p>Plan for later</p>
                            </div>
                        </a>

                        <a href="analytics.php" class="quick-action">
                            <div class="action-icon">📊</div>
                            <div class="action-content">
                                <h4>View Analytics</h4>
                                <p>Track performance</p>
                            </div>
                        </a>

                        <a href="profile.php" class="quick-action">
                            <div class="action-icon">👤</div>
                            <div class="action-content">
                                <h4>Update Profile</h4>
                                <p>Manage your info</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Performance Overview -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>This Week</h3>
                </div>
                <div class="card-content">
                    <div class="performance-stats">
                        <div class="perf-item">
                            <div class="perf-label">Streams</div>
                            <div class="perf-value">8</div>
                            <div class="perf-change positive">+2</div>
                        </div>
                        <div class="perf-item">
                            <div class="perf-label">Avg Viewers</div>
                            <div class="perf-value">156</div>
                            <div class="perf-change positive">+12%</div>
                        </div>
                        <div class="perf-item">
                            <div class="perf-label">Total Hours</div>
                            <div class="perf-value">24.5</div>
                            <div class="perf-change positive">+3.2</div>
                        </div>
                        <div class="perf-item">
                            <div class="perf-label">Earnings</div>
                            <div class="perf-value">$340</div>
                            <div class="perf-change positive">+8%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh attendance status every 30 seconds for any live sessions
setInterval(function() {
    // Check if there are any live indicators on the page
    const liveIndicators = document.querySelectorAll('.live-indicator');
    if (liveIndicators.length > 0) {
        // Only refresh if there are active sessions to avoid unnecessary requests
        location.reload();
    }
}, 30000);

// Set minimum date to today for date inputs
document.addEventListener('DOMContentLoaded', function() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    const today = new Date().toISOString().split('T')[0];
    dateInputs.forEach(input => {
        if (!input.value) {
            input.value = today;
        }
        input.min = today;
    });
});
</script>

<?php include 'layout/footer.php'; ?>