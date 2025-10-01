<?php
require_once __DIR__ . '/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

function require_login() {
    if (!is_logged_in()) {
        redirect('/tiktok-live-host/login.php');
    }
}

function require_role($required_role) {
    require_login();
    if ($_SESSION['user_role'] !== $required_role) {
        redirect('/tiktok-live-host/unauthorized.php');
    }
}

function get_logged_in_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function log_activity($user_id, $action, $description = null) {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id,
        $action,
        $description,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

function authenticate_user($username, $password) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        
        log_activity($user['id'], 'login', 'User logged in successfully');
        return true;
    }
    
    return false;
}

function logout_user() {
    if (is_logged_in()) {
        log_activity($_SESSION['user_id'], 'logout', 'User logged out');
    }
    
    session_destroy();
    session_start();
    session_regenerate_id(true);
}

function get_user_dashboard_url($role) {
    $base_url = '/tiktok-live-host'; // Laragon project directory
    switch ($role) {
        case 'admin':
            return $base_url . '/admin/dashboard.php';
        case 'live_seller':
            return $base_url . '/live-sellers/dashboard.php';
        default:
            return $base_url . '/';
    }
}
?>