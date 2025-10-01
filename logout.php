<?php
require_once __DIR__ . '/includes/functions.php';

// Only logout if user is logged in
if (is_logged_in()) {
    logout_user();
}

// Redirect to landing page
redirect('/tiktok-live-host/');
?>