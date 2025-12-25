<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/config.php');

$usernames = ['dosen', 'mahasiswa'];
foreach ($usernames as $username) {
    $user = $DB->get_record('user', ['username' => $username]);
    if ($user) {
        echo "User: $username\n";
        echo "  - ID: $user->id\n";
        echo "  - Auth: $user->auth\n";
        echo "  - Confirmed: $user->confirmed\n";
        echo "  - Suspended: $user->suspended\n";
        echo "  - Deleted: $user->deleted\n";
        echo "  - Password format (start): " . substr($user->password, 0, 5) . "...\n";
    } else {
        echo "User: $username NOT FOUND\n";
    }
}

// Check enabled auth plugins
$auths = get_config('moodle', 'auth');
echo "\nEnabled Auth Plugins: " . $auths . "\n";
