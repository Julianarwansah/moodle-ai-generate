<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/config.php');

// Get current auth setting
$current_auth = get_config('moodle', 'auth');
echo "Current auth config: " . $current_auth . "\n";

// Check Admin auth
$admin = $DB->get_record('user', ['username' => 'admin']);
echo "Admin auth method: " . ($admin ? $admin->auth : 'NOT FOUND') . "\n";

// Enable manual auth if not present
if (strpos($current_auth, 'manual') === false) {
    if (empty($current_auth)) {
        $new_auth = 'manual';
    } else {
        $new_auth = 'manual,' . $current_auth;
    }
    set_config('auth', $new_auth);
    echo "Updated auth config to: " . $new_auth . "\n";
} else {
    echo "Manual auth already enabled.\n";
}

// Purge caches to ensure config applies
purge_all_caches();
echo "Caches purged.\n";
