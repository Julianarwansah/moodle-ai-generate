<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/config.php');

$username = 'dosen';
$password = 'Moodle123!';

echo "Diagnosing login for user: $username\n";

// 1. Check User Record
$user = $DB->get_record('user', ['username' => $username]);
if (!$user) {
    die("User not found in DB!\n");
}
echo "User found. ID: {$user->id}, Auth: {$user->auth}, Confirmed: {$user->confirmed}\n";

// 2. Simulate Login Check
$auth_plugin = get_auth_plugin($user->auth);
echo "Auth plugin loaded: " . get_class($auth_plugin) . "\n";

// Check pure password validation
$internal_check = validate_internal_user_password($user, $password);
echo "validate_internal_user_password result: " . ($internal_check ? "TRUE (Password Match)" : "FALSE (Password Mismatch)") . "\n";

// Check through auth plugin
$plugin_check = $auth_plugin->user_login($username, $password);
echo "Auth Plugin user_login result: " . ($plugin_check ? "TRUE" : "FALSE") . "\n";

// 3. Check login capabilities (authenticate_user_login)
// This is the main function Moodle calls on login
$result = authenticate_user_login($username, $password);
if ($result) {
    echo "authenticate_user_login SUCCESS: User logged in object returned.\n";
} else {
    echo "authenticate_user_login FAILED: Returned false.\n";

    // Check why?
    if ($user->suspended)
        echo " - User is suspended.\n";
    if (!$user->confirmed)
        echo " - User is not confirmed.\n";
    if (is_siteadmin($user))
        echo " - User is Site Admin.\n";

    // Check if auth plugin prevents it
    if (!$auth_plugin->is_internal()) {
        echo " - Auth plugin is NOT internal. It might be delegating to external source.\n";
    }
}
