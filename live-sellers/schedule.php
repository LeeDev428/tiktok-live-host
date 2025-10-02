<?php
require_once __DIR__ . '/../includes/functions.php';

// Require live_seller role
require_role('live_seller');

// Get current user info
$current_user = get_logged_in_user();

// Get seller dashboard stats
$db = getDB();

// Get today's date
$today = date('Y-m-d');
$view_date = $_GET['date'] ?? $today;

// Check if user already has attendance for today
$stmt = $db->prepare("
    SELECT COUNT(*) as attendance_count 
    FROM seller_attendance 
    WHERE seller_id = ? AND attendance_date = ? AND status != 'cancelled'
");
$stmt->execute([$current_user['id'], $today]);
$today_attendance_count = $stmt->fetchColumn();
$has_attendance_today = $today_attendance_count > 0;

// Check for successful submission
$attendance_submitted = isset($_SESSION['attendance_submitted']) && $_SESSION['attendance_submitted'] === true;
if ($attendance_submitted) {
    unset($_SESSION['attendance_submitted']);
}

// Validate the view date
if ($view_date < $today) {
    $view_date = $today; // Don't allow viewing past dates
} elseif ($view_date > date('Y-m-d', strtotime('+30 days'))) {
    $view_date = date('Y-m-d', strtotime('+30 days')); // Don't allow viewing too far in future
}

// Get attendance data for the viewed date
$stmt = $db->prepare("
    SELECT sa.*, ats.name as slot_name, ats.duration_hours, ats.start_time, ats.end_time
    FROM seller_attendance sa
    JOIN attendance_time_slots ats ON sa.time_slot_id = ats.id
    WHERE sa.seller_id = ? AND sa.attendance_date = ?
    ORDER BY ats.start_time
");
$stmt->execute([$current_user['id'], $view_date]);
$view_date_attendance = $stmt->fetchAll();

// Get available time slots
$stmt = $db->query("SELECT * FROM attendance_time_slots WHERE is_active = 1 ORDER BY start_time");
$available_slots = $stmt->fetchAll();

// Handle attendance actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'schedule_slot') {
        $slot_id = $_POST['slot_id'] ?? '';
        $attendance_date = $_POST['attendance_date'] ?? $today;
        $custom_slot_data = $_POST['custom_slot_data'] ?? '';
        
        // Validate that the date is not in the past
        if ($attendance_date < $today) {
            $error_message = "You cannot schedule for past dates. Please select today or a future date.";
        } elseif ($attendance_date > date('Y-m-d', strtotime('+30 days'))) {
            $error_message = "You can only schedule up to 30 days in advance.";
        } elseif ($slot_id && $attendance_date && $custom_slot_data) {
            try {
                $slot_data = json_decode($custom_slot_data, true);
                
                if ($slot_data && isset($slot_data['duration'], $slot_data['start_time'], $slot_data['end_time'], $slot_data['name'])) {
                    // First, check if a time slot with these exact times already exists
                    $stmt = $db->prepare("
                        SELECT id FROM attendance_time_slots 
                        WHERE start_time = ? AND end_time = ? AND duration_hours = ?
                    ");
                    $stmt->execute([$slot_data['start_time'], $slot_data['end_time'], $slot_data['duration']]);
                    $existing_slot = $stmt->fetch();
                    
                    if ($existing_slot) {
                        $time_slot_id = $existing_slot['id'];
                    } else {
                        // Create new time slot
                        $stmt = $db->prepare("
                            INSERT INTO attendance_time_slots (name, start_time, end_time, duration_hours, is_active)
                            VALUES (?, ?, ?, ?, 1)
                        ");
                        $stmt->execute([
                            $slot_data['name'],
                            $slot_data['start_time'],
                            $slot_data['end_time'],
                            $slot_data['duration']
                        ]);
                        $time_slot_id = $db->lastInsertId();
                    }
                    
                    // Check if this seller already has this slot scheduled for the same date
                    $stmt = $db->prepare("
                        SELECT id FROM seller_attendance 
                        WHERE seller_id = ? AND attendance_date = ? AND time_slot_id = ?
                    ");
                    $stmt->execute([$current_user['id'], $attendance_date, $time_slot_id]);
                    $existing_attendance = $stmt->fetch();
                    
                    if ($existing_attendance) {
                        $error_message = "You have already scheduled this time slot for the selected date.";
                    } else {
                        // Schedule the attendance
                        $stmt = $db->prepare("
                            INSERT INTO seller_attendance (seller_id, attendance_date, time_slot_id, status)
                            VALUES (?, ?, ?, 'scheduled')
                        ");
                        $stmt->execute([$current_user['id'], $attendance_date, $time_slot_id]);
                        
                        // Set session flag for successful submission
                        $_SESSION['attendance_submitted'] = true;
                        
                        // Redirect to prevent form resubmission
                        header('Location: ' . $_SERVER['REQUEST_URI']);
                        exit;
                    }
                } else {
                    $error_message = "Invalid slot data provided.";
                }
            } catch (Exception $e) {
                $error_message = "Error scheduling slot: " . $e->getMessage();
            }
        } else {
            $error_message = "Please select both duration and time slot.";
        }
    } elseif ($action === 'check_in') {
        $attendance_id = $_POST['attendance_id'] ?? '';
        if ($attendance_id) {
            $stmt = $db->prepare("
                UPDATE seller_attendance 
                SET status = 'checked_in', check_in_time = CURRENT_TIME, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND seller_id = ?
            ");
            $stmt->execute([$attendance_id, $current_user['id']]);
            $success_message = "Checked in successfully!";
        }
    } elseif ($action === 'check_out') {
        $attendance_id = $_POST['attendance_id'] ?? '';
        if ($attendance_id) {
            $stmt = $db->prepare("
                UPDATE seller_attendance 
                SET status = 'completed', check_out_time = CURRENT_TIME,
                    actual_hours = TIME_TO_SEC(TIMEDIFF(CURRENT_TIME, check_in_time)) / 3600,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND seller_id = ?
            ");
            $stmt->execute([$attendance_id, $current_user['id']]);
            $success_message = "Checked out successfully!";
        }
    } elseif ($action === 'cancel_slot') {
        $attendance_id = $_POST['attendance_id'] ?? '';
        if ($attendance_id) {
            $stmt = $db->prepare("
                UPDATE seller_attendance 
                SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND seller_id = ? AND status = 'scheduled'
            ");
            $stmt->execute([$attendance_id, $current_user['id']]);
            $success_message = "Time slot cancelled successfully!";
        }
    }
    
    // Refresh attendance data after action
    $stmt = $db->prepare("
        SELECT sa.*, ats.name as slot_name, ats.duration_hours, ats.start_time, ats.end_time
        FROM seller_attendance sa
        JOIN attendance_time_slots ats ON sa.time_slot_id = ats.id
        WHERE sa.seller_id = ? AND sa.attendance_date = ?
        ORDER BY ats.start_time
    ");
    $stmt->execute([$current_user['id'], $view_date]);
    $view_date_attendance = $stmt->fetchAll();
}

// Get upcoming schedule (next 7 days)
$stmt = $db->prepare("
    SELECT sa.*, ats.name as slot_name, ats.duration_hours, ats.start_time, ats.end_time
    FROM seller_attendance sa
    JOIN attendance_time_slots ats ON sa.time_slot_id = ats.id
    WHERE sa.seller_id = ? AND sa.attendance_date BETWEEN ? AND DATE_ADD(?, INTERVAL 7 DAY)
    ORDER BY sa.attendance_date, ats.start_time
");
$stmt->execute([$current_user['id'], $today, $today]);
$upcoming_schedule = $stmt->fetchAll();

$page_title = 'Schedule & Attendance';
include 'layout/header.php';
?>

<div class="schedule-container">
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

    <!-- Simple Schedule Form Layout -->
    <div class="simple-schedule-container">
        <?php if ($attendance_submitted): ?>
            <!-- Success Message with Dashboard Redirect -->
            <div class="attendance-success-card">
                <div class="success-icon">✅</div>
                <h2>Attendance Submitted Successfully!</h2>
                <p>Your attendance has been recorded for today. Thank you for your submission.</p>
                <div class="success-actions">
                    <a href="dashboard.php" class="btn btn-primary btn-large">
                        <span class="btn-icon">🏠</span>
                        Go to Dashboard
                    </a>
                </div>
            </div>
        <?php elseif ($has_attendance_today): ?>
            <!-- Already Submitted Message -->
            <div class="attendance-form-wrapper">
                <div class="attendance-form-card already-submitted">
                    <div class="form-body">
                        <div class="attendance-status-display">
                            <div class="status-icon-large">✅</div>
                            <div class="status-content">
                                <h3>Attendance Successfully Recorded</h3>
                                <div class="status-summary">
                                    <p class="main-message">Your daily attendance has been confirmed!</p>
                                    <p class="date-info">Submitted on <span class="highlight-date"><?php echo date('F j, Y'); ?></span></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="next-submission-info">
                            <div class="info-content-compact">
                                <span class="info-icon">🕐</span>
                                <div class="info-text">
                                    <p class="next-date">Next submission available tomorrow, <strong><?php echo date('F j, Y', strtotime('+1 day')); ?></strong></p>
                                    <p class="thank-you">Thank you for your participation!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-footer">
                        <div class="single-action">
                            <a href="dashboard.php" class="btn btn-primary btn-dashboard">
                                <span class="btn-icon">🏠</span>
                                <span class="btn-text">Return to Dashboard</span>
                                <span class="btn-arrow">→</span>
                            </a>
                            <p class="footer-note">Continue managing your schedule from the dashboard</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Schedule Form -->
            <div class="schedule-form-card">
            <div class="form-header">
                <h2>Schedule Your Time Slot</h2>
                <p>Choose your preferred duration and time slot. We offer 3-hour and 4-hour shifts throughout the day.</p>
            </div>
            
                        <form method="POST" class="simple-schedule-form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="schedule_slot">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="attendance_date" class="required">Date:</label>
                        <input type="date" id="attendance_date" name="attendance_date" 
                               value="<?php echo $view_date; ?>" min="<?php echo $today; ?>" max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="duration_choice" class="required">Duration:</label>
                        <select id="duration_choice" name="duration_choice" required onchange="updateTimeSlots()">
                            <option value="">Select duration...</option>
                            <option value="3">3 Hours</option>
                            <option value="4">4 Hours</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label for="slot_id" class="required">Time Slot:</label>
                    <select id="slot_id" name="slot_id" required disabled>
                        <option value="">First select duration...</option>
                    </select>
                </div>
                
                <div class="form-group full-width">
                    <label for="solds">Solds:</label>
                    <input type="number" id="solds" name="solds" placeholder="Enter total solds amount" min="0" step="1">
                </div>
                
                <div class="form-group full-width">
                    <label for="sold_photo">📱 Total Sold Photo:</label>
                    <div class="photo-upload-container">
                        <input type="file" id="sold_photo" name="sold_photo" accept="image/*" class="file-input">
                        <div class="upload-placeholder" onclick="document.getElementById('sold_photo').click()">
                            <span class="upload-icon">📷</span>
                            <p>Upload your total sold photo</p>
                            <span class="btn btn-outline">Choose Photo</span>
                        </div>
                        <div id="photo-preview" class="photo-preview" style="display: none;">
                            <img id="preview-image" src="" alt="Preview">
                            <button type="button" class="remove-photo" onclick="removePhoto()">×</button>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-large" style="text-align: center; display: flex; align-items: center; justify-content: center;">
                    <span class="btn-icon">⏰</span>
                    Submit
                </button>
                
                <input type="hidden" id="custom_slot_data" name="custom_slot_data" value="">
            </form>
        </div>
        <?php endif; ?>
    </div>

<script>
// Define time slot data
const timeSlots = {
    3: [
        { value: "3_5am_8am", text: "5:00 AM - 8:00 AM", start_time: "05:00:00", end_time: "08:00:00" },
        { value: "3_8am_11am", text: "8:00 AM - 11:00 AM", start_time: "08:00:00", end_time: "11:00:00" },
        { value: "3_11am_2pm", text: "11:00 AM - 2:00 PM", start_time: "11:00:00", end_time: "14:00:00" },
        { value: "3_2pm_5pm", text: "2:00 PM - 5:00 PM", start_time: "14:00:00", end_time: "17:00:00" },
        { value: "3_5pm_8pm", text: "5:00 PM - 8:00 PM", start_time: "17:00:00", end_time: "20:00:00" },
        { value: "3_8pm_11pm", text: "8:00 PM - 11:00 PM", start_time: "20:00:00", end_time: "23:00:00" },
        { value: "3_11pm_2am", text: "11:00 PM - 2:00 AM", start_time: "23:00:00", end_time: "02:00:00" },
        { value: "3_2am_5am", text: "2:00 AM - 5:00 AM", start_time: "02:00:00", end_time: "05:00:00" }
    ],
    4: [
        { value: "4_6am_10am", text: "6:00 AM - 10:00 AM", start_time: "06:00:00", end_time: "10:00:00" },
        { value: "4_10am_2pm", text: "10:00 AM - 2:00 PM", start_time: "10:00:00", end_time: "14:00:00" },
        { value: "4_2pm_6pm", text: "2:00 PM - 6:00 PM", start_time: "14:00:00", end_time: "18:00:00" },
        { value: "4_6pm_10pm", text: "6:00 PM - 10:00 PM", start_time: "18:00:00", end_time: "22:00:00" },
        { value: "4_10pm_2am", text: "10:00 PM - 2:00 AM", start_time: "22:00:00", end_time: "02:00:00" },
        { value: "4_2am_6am", text: "2:00 AM - 6:00 AM", start_time: "02:00:00", end_time: "06:00:00" }
    ]
};

function updateTimeSlots() {
    const durationChoice = document.getElementById('duration_choice').value;
    const slotSelect = document.getElementById('slot_id');
    const customSlotData = document.getElementById('custom_slot_data');
    
    updateSlotDropdown(durationChoice, slotSelect, customSlotData);
}

function updateSlotDropdown(durationChoice, slotSelect, customSlotData) {
    // Clear existing options
    slotSelect.innerHTML = '<option value="">Select a time slot...</option>';
    
    if (durationChoice && timeSlots[durationChoice]) {
        slotSelect.disabled = false;
        
        timeSlots[durationChoice].forEach(slot => {
            const option = document.createElement('option');
            option.value = slot.value;
            option.textContent = slot.text;
            slotSelect.appendChild(option);
        });
    } else {
        slotSelect.disabled = true;
        slotSelect.innerHTML = '<option value="">First select duration...</option>';
    }
    
    // Update custom slot data when selection changes
    slotSelect.onchange = function() {
        const selectedSlot = timeSlots[durationChoice]?.find(slot => slot.value === this.value);
        if (selectedSlot) {
            customSlotData.value = JSON.stringify({
                duration: durationChoice,
                start_time: selectedSlot.start_time,
                end_time: selectedSlot.end_time,
                name: selectedSlot.text
            });
        } else {
            customSlotData.value = '';
        }
    };
}

// Auto-refresh attendance status every 30 seconds
setInterval(function() {
    // Check if there are any active sessions
    const liveIndicators = document.querySelectorAll('.live-indicator');
    if (liveIndicators.length > 0) {
        // Only refresh if there are active sessions to avoid unnecessary requests
        location.reload();
    }
}, 30000);  

// Set minimum date to today for date inputs and prevent past date selection
document.addEventListener('DOMContentLoaded', function() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    const today = new Date().toISOString().split('T')[0];
    const maxDate = new Date();
    maxDate.setDate(maxDate.getDate() + 30);
    const maxDateString = maxDate.toISOString().split('T')[0];
    
    dateInputs.forEach(input => {
        // Set minimum date to today for all date inputs
        input.min = today;
        // Set maximum date to 30 days from now
        input.max = maxDateString;
        
        // Set default value to today if empty
        if (!input.value) {
            input.value = today;
        }
        
        // Add event listener to prevent manual entry of past dates
        input.addEventListener('change', function() {
            if (this.value < today) {
                alert('You cannot schedule for past dates. Please select today or a future date.');
                this.value = today;
            }
            if (this.value > maxDateString) {
                alert('You can only schedule up to 30 days in advance.');
                this.value = maxDateString;
            }
        });
        
        // Prevent typing past dates
        input.addEventListener('input', function() {
            if (this.value && this.value < today) {
                this.value = today;
            }
        });
    });
});

// Photo preview functionality
document.getElementById('sold_photo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-image').src = e.target.result;
            document.getElementById('photo-preview').style.display = 'block';
            document.querySelector('.upload-placeholder').style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
});

function removePhoto() {
    document.getElementById('sold_photo').value = '';
    document.getElementById('photo-preview').style.display = 'none';
    document.querySelector('.upload-placeholder').style.display = 'flex';
}
</script>

<?php include 'layout/footer.php'; ?>