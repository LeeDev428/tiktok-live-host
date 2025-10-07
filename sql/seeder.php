<?php
/**
 * Database Seeder for TikTok Live Host Agency
 * This file contains the default users with plain text passwords
 * Passwords will be hashed when stored in the database
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';

// Default attendance time slots
$default_time_slots = [
    ['name' => 'Morning Shift (3hrs)', 'duration_hours' => 3.0, 'start_time' => '07:00:00', 'end_time' => '10:00:00'],
    ['name' => 'Late Morning (4hrs)', 'duration_hours' => 4.0, 'start_time' => '08:00:00', 'end_time' => '12:00:00'],
    ['name' => 'Afternoon Shift (3hrs)', 'duration_hours' => 3.0, 'start_time' => '13:00:00', 'end_time' => '16:00:00'],
    ['name' => 'Evening Shift (4hrs)', 'duration_hours' => 4.0, 'start_time' => '16:00:00', 'end_time' => '20:00:00'],
    ['name' => 'Night Shift (3hrs)', 'duration_hours' => 3.0, 'start_time' => '20:00:00', 'end_time' => '23:00:00'],
    ['name' => 'Extended Day (6hrs)', 'duration_hours' => 6.0, 'start_time' => '10:00:00', 'end_time' => '16:00:00'],
    ['name' => 'Split Shift AM (2hrs)', 'duration_hours' => 2.0, 'start_time' => '07:00:00', 'end_time' => '09:00:00'],
    ['name' => 'Split Shift PM (2hrs)', 'duration_hours' => 2.0, 'start_time' => '18:00:00', 'end_time' => '20:00:00']
];

// Default users with plain text passwords
$default_users = [
    [
        'username' => 'admin',
        'email' => 'admin@gmail.com',
        'password' => 'admin123', // Plain text password
        'role' => 'admin',
        'full_name' => 'System Administrator',
        'status' => 'active'
    ],
    [
        'username' => 'seller1',
        'email' => 'seller1@gmail.com',
        'password' => 'seller123', // Plain text password
        'role' => 'live_seller',
        'full_name' => 'Jane Doe',
        'status' => 'active'
    ],
    [
        'username' => 'seller2',
        'email' => 'seller2@gmail.com',
        'password' => 'seller123', // Plain text password
        'role' => 'live_seller',
        'full_name' => 'John Smith',
        'status' => 'active'
    ],
    [
        'username' => 'demo_seller',
        'email' => 'demo@tiktok-live-host.com',
        'password' => 'demo2024', // Plain text password
        'role' => 'live_seller',
        'full_name' => 'Demo Seller',
        'status' => 'active'
    ]
];

/**
 * Function to seed the database with default users
 */
function seedUsers() {
    global $default_users;
    
    try {
        $db = getDB();
        
        // Clear existing users (optional - remove if you want to keep existing data)
        echo "Clearing existing users...\n";
        $db->exec("DELETE FROM users");
        
        // Reset auto increment
        $db->exec("ALTER TABLE users AUTO_INCREMENT = 1");
        
        echo "Seeding users...\n";
        
        $stmt = $db->prepare("
            INSERT INTO users (username, email, password, role, full_name, status) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($default_users as $user) {
            // Hash the password before storing
            $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
            
            $stmt->execute([
                $user['username'],
                $user['email'],
                $hashed_password,
                $user['role'],
                $user['full_name'],
                $user['status']
            ]);
            
            echo "✓ Created user: {$user['username']} (Password: {$user['password']})\n";
        }
        
        echo "Users seeded successfully!\n";
        
    } catch (Exception $e) {
        echo "Error seeding users: " . $e->getMessage() . "\n";
        exit(1);
    }
}

/**
 * Function to seed attendance time slots
 */
function seedTimeSlots() {
    global $default_time_slots;
    
    try {
        $db = getDB();
        
        // Clear existing time slots
        echo "Clearing existing time slots...\n";
        $db->exec("DELETE FROM attendance_time_slots");
        $db->exec("ALTER TABLE attendance_time_slots AUTO_INCREMENT = 1");
        
        echo "Seeding attendance time slots...\n";
        
        $stmt = $db->prepare("
            INSERT INTO attendance_time_slots (name, duration_hours, start_time, end_time, is_active) 
            VALUES (?, ?, ?, ?, 1)
        ");
        
        foreach ($default_time_slots as $slot) {
            $stmt->execute([
                $slot['name'],
                $slot['duration_hours'],
                $slot['start_time'],
                $slot['end_time']
            ]);
            
            echo "✓ Created time slot: {$slot['name']} ({$slot['start_time']} - {$slot['end_time']})\n";
        }
        
        echo "Time slots seeded successfully!\n";
        
    } catch (Exception $e) {
        echo "Error seeding time slots: " . $e->getMessage() . "\n";
        exit(1);
    }
}

/**
 * Function to seed everything
 */
function seedAll() {
    echo "Starting complete database seeding...\n\n";
    seedTimeSlots();
    echo "\n";
    seedUsers();
    echo "\n";
    seedSalesData();
    echo "\n";
    showCredentials();
    echo "\nDatabase seeding completed successfully!\n";
}

/**
 * Function to seed sales data (products and sales)
 */
function seedSalesData() {
    try {
        $db = getDB();
        
        echo "Seeding products and sales data...\n";
        
        // Clear existing data
        $db->exec("DELETE FROM live_host_sales");
        $db->exec("DELETE FROM live_host_daily_summary");
        $db->exec("DELETE FROM products");
        
        // Reset auto increments
        $db->exec("ALTER TABLE live_host_sales AUTO_INCREMENT = 1");
        $db->exec("ALTER TABLE live_host_daily_summary AUTO_INCREMENT = 1");
        $db->exec("ALTER TABLE products AUTO_INCREMENT = 1");
        
        // Insert products
        $products = [
            ['Wireless Bluetooth Headphones', 'High-quality wireless headphones with noise cancellation', 89.99, 'Electronics', 'WBH001', 50],
            ['Smartphone Case', 'Protective case for smartphones with multiple colors', 19.99, 'Accessories', 'SPC001', 100],
            ['LED Desk Lamp', 'Adjustable LED desk lamp with USB charging port', 45.99, 'Home & Office', 'LDL001', 30],
            ['Fitness Tracker', 'Smart fitness tracker with heart rate monitor', 129.99, 'Health & Fitness', 'FT001', 25],
            ['Portable Power Bank', '10000mAh portable charger with fast charging', 34.99, 'Electronics', 'PPB001', 75],
            ['Skincare Set', 'Complete skincare routine set for all skin types', 79.99, 'Beauty', 'SKS001', 40],
            ['Coffee Tumbler', 'Insulated travel coffee tumbler 16oz', 24.99, 'Kitchen', 'CT001', 60],
            ['Yoga Mat', 'Non-slip exercise yoga mat with carrying strap', 39.99, 'Health & Fitness', 'YM001', 35]
        ];
        
        $stmt = $db->prepare("INSERT INTO products (name, description, price, category, sku, stock_quantity, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        foreach ($products as $product) {
            $stmt->execute($product);
        }
        
        // Insert sample sales
        $sales = [
            [2, 1, 'Wireless Bluetooth Headphones', 2, 89.99, 179.98, 15.00, 26.99, '2025-10-01 14:30:00'],
            [2, 2, 'Smartphone Case', 5, 19.99, 99.95, 10.00, 9.99, '2025-10-01 15:15:00'],
            [2, 3, 'LED Desk Lamp', 1, 45.99, 45.99, 12.00, 5.52, '2025-10-01 16:45:00'],
            [3, 4, 'Fitness Tracker', 3, 129.99, 389.97, 20.00, 77.99, '2025-10-01 19:20:00'],
            [3, 5, 'Portable Power Bank', 4, 34.99, 139.96, 10.00, 13.99, '2025-10-01 20:10:00'],
            [2, 6, 'Skincare Set', 2, 79.99, 159.98, 15.00, 23.99, '2025-10-02 10:30:00'],
            [3, 7, 'Coffee Tumbler', 6, 24.99, 149.94, 8.00, 11.99, '2025-10-02 11:45:00'],
            [2, 8, 'Yoga Mat', 1, 39.99, 39.99, 10.00, 3.99, '2025-10-02 13:20:00']
        ];
        
        $stmt = $db->prepare("INSERT INTO live_host_sales (seller_id, product_id, product_name, quantity, unit_price, total_amount, commission_rate, commission_amount, sale_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')");
        foreach ($sales as $sale) {
            $stmt->execute($sale);
        }
        
        // Insert daily summaries
        $summaries = [
            [2, '2025-10-01', 325.92, 8, 40.50, 6.0, 2],
            [3, '2025-10-01', 529.93, 7, 91.98, 4.0, 1],
            [2, '2025-10-02', 199.97, 3, 27.98, 3.0, 1],
            [3, '2025-10-02', 149.94, 6, 11.99, 2.0, 1]
        ];
        
        $stmt = $db->prepare("INSERT INTO live_host_daily_summary (seller_id, summary_date, total_sales, total_items_sold, total_commission, hours_worked, streams_count) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($summaries as $summary) {
            $stmt->execute($summary);
        }
        
        echo "✓ Created " . count($products) . " products\n";
        echo "✓ Created " . count($sales) . " sales records\n";
        echo "✓ Created " . count($summaries) . " daily summaries\n";
        
        echo "Sales data seeded successfully!\n";
        
    } catch (Exception $e) {
        echo "Error seeding sales data: " . $e->getMessage() . "\n";
        exit(1);
    }
}

/**
 * Function to display credentials without seeding
 */
function showCredentials() {
    global $default_users;
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "DEFAULT LOGIN CREDENTIALS:\n";
    echo str_repeat("=", 60) . "\n";
    
    foreach ($default_users as $user) {
        echo "Role: " . ucfirst($user['role']) . "\n";
        echo "Username: {$user['username']}\n";
        echo "Email: {$user['email']}\n";
        echo "Password: {$user['password']}\n";
        echo "Full Name: {$user['full_name']}\n";
        echo str_repeat("-", 40) . "\n";
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $action = $argv[1] ?? 'show';
    
    switch ($action) {
        case 'seed':
            seedAll();
            break;
            
        case 'users':
            echo "Seeding users only...\n";
            seedUsers();
            break;
            
        case 'slots':
            echo "Seeding time slots only...\n";
            seedTimeSlots();
            break;
            
        case 'sales':
            echo "Seeding sales data only...\n";
            seedSalesData();
            break;
            
        case 'show':
        default:
            showCredentials();
            break;
            
        case 'help':
            echo "Usage: php seeder.php [action]\n";
            echo "Actions:\n";
            echo "  show   - Display credentials only (default)\n";
            echo "  seed   - Seed database with users, time slots, and sales data\n";
            echo "  users  - Seed users only\n";
            echo "  slots  - Seed attendance time slots only\n";
            echo "  sales  - Seed products and sales data only\n";
            echo "  help   - Show this help message\n";
            break;
    }
} else {
    // If accessed via web browser
    echo "<h1>TikTok Live Host - Default Credentials</h1>";
    echo "<style>body{font-family:Arial,sans-serif;margin:40px;} .user{background:#f5f5f5;padding:15px;margin:10px 0;border-radius:5px;} .role{color:#007cba;font-weight:bold;} .password{color:#d9534f;font-weight:bold;}</style>";
    
    foreach ($default_users as $user) {
        echo "<div class='user'>";
        echo "<div class='role'>Role: " . ucfirst($user['role']) . "</div>";
        echo "<div><strong>Username:</strong> {$user['username']}</div>";
        echo "<div><strong>Email:</strong> {$user['email']}</div>";
        echo "<div class='password'><strong>Password:</strong> {$user['password']}</div>";
        echo "<div><strong>Full Name:</strong> {$user['full_name']}</div>";
        echo "</div>";
    }
    
    echo "<p><strong>Note:</strong> To seed the database, run: <code>php seeder.php seed</code> from the command line.</p>";
}
?>