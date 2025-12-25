<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/config.php');
require_once($CFG->dirroot . '/user/lib.php');

function fix_and_verify($username, $password)
{
    global $DB;
    echo "Processing user: $username\n";
    $user = $DB->get_record('user', ['username' => $username]);
    if (!$user) {
        echo " - User not found, skipping.\n";
        return;
    }

    echo " - Old Hash start: " . substr($user->password, 0, 10) . "...\n";

    // FORCE UPDATE
    if (update_internal_user_password($user, $password)) {
        echo " - Password updated successfully.\n";
    } else {
        echo " - Failed to update password.\n";
    }

    // RELOAD verify
    $user = $DB->get_record('user', ['username' => $username]);
    echo " - New Hash start: " . substr($user->password, 0, 10) . "...\n";

    if (validate_internal_user_password($user, $password)) {
        echo " - VALIDATION SUCCESS: Password matches!\n";
    } else {
        echo " - VALIDATION FAILED: Password still does not match.\n";
    }
    echo "---------------------------------------------------\n";
}

$password = 'Moodle123!';
fix_and_verify('dosen', $password);
fix_and_verify('mahasiswa', $password);
