<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/config.php');

$username = 'mahasiswa';
$user = $DB->get_record('user', ['username' => $username]);

if ($user) {
    echo "User found:\n";
    echo "ID: " . $user->id . "\n";
    echo "Confirmed: " . $user->confirmed . "\n";
    echo "Auth: " . $user->auth . "\n";
    echo "Suspended: " . $user->suspended . "\n";
    echo "Deleted: " . $user->deleted . "\n";
} else {
    echo "User '$username' not found.\n";
}
