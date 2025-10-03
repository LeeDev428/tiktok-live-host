<?php
// Database migration script to add experience_status column
require_once __DIR__ . '/../config/config.php';

try {
    $db = getDB();
    
    // Check if experience_status column already exists
    $stmt = $db->prepare("SHOW COLUMNS FROM users LIKE 'experience_status'");
    $stmt->execute();
    $column_exists = $stmt->fetch();
    
    if (!$column_exists) {
        echo "Adding experience_status column to users table...\n";
        
        // Add the experience_status column
        $sql = "ALTER TABLE users ADD COLUMN experience_status ENUM('newbie', 'tenured') DEFAULT 'newbie' AFTER profile_image";
        $db->exec($sql);
        
        // Add index for the new column
        $sql = "ALTER TABLE users ADD INDEX idx_experience_status (experience_status)";
        $db->exec($sql);
        
        echo "✅ Successfully added experience_status column!\n";
        
        // Update existing users with default values
        $sql = "UPDATE users SET experience_status = 'newbie' WHERE experience_status IS NULL";
        $affected = $db->exec($sql);
        
        echo "✅ Updated {$affected} existing users with default experience_status!\n";
        
    } else {
        echo "✅ experience_status column already exists!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>