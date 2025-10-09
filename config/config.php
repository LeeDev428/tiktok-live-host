<?php
/**
 * Configuration for TikTok Live Host Agency
 * Domain: tiktokliveagency.pw (Hostinger)
 */

// ============================================
// DATABASE CONFIGURATION
// ============================================
// ⚠️ UPDATE THESE WITH YOUR HOSTINGER CREDENTIALS AFTER DEPLOYMENT!
// Get credentials from: hPanel → Databases → Your Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'YOUR_DATABASE_NAME');        // Example: u123456789_tiktok
define('DB_USER', 'YOUR_DATABASE_USERNAME');    // Example: u123456789_admin
define('DB_PASS', 'YOUR_DATABASE_PASSWORD');    // From Hostinger hPanel
define('DB_CHARSET', 'utf8mb4');

// ============================================
// APPLICATION CONFIGURATION
// ============================================
define('SITE_NAME', 'TikTok Live Host Agency');
define('SITE_URL', 'https://tiktokliveagency.pw'); // ⚠️ HTTPS only for production
define('SITE_ROOT', dirname(__DIR__));

// ============================================
// SECURITY CONFIGURATION
// ============================================
define('SESSION_LIFETIME', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 6);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutes

// ============================================
// TIMEZONE
// ============================================
date_default_timezone_set('Asia/Manila');

// ============================================
// ERROR HANDLING - PRODUCTION MODE
// ============================================
error_reporting(0);                        // Don't show errors to users
ini_set('display_errors', 0);             // Hide errors
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);                 // Log to file instead

// Create logs directory if it doesn't exist
$logs_dir = __DIR__ . '/../logs';
if (!is_dir($logs_dir)) {
    @mkdir($logs_dir, 0755, true);
}
ini_set('error_log', $logs_dir . '/php_errors.log');

// ============================================
// SESSION CONFIGURATION - PRODUCTION SECURITY
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.auto_start', 0);
    ini_set('session.cookie_httponly', 1);      // Prevent JavaScript access
    ini_set('session.cookie_secure', 1);        // ⚠️ HTTPS only
    ini_set('session.use_strict_mode', 1);      // Prevent session fixation
    ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
    
    // Start session after configuration
    session_start();
}

// ============================================
// CSRF TOKEN GENERATION
// ============================================
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>