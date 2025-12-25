<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/config.php');

$users = $DB->get_records('user', null, '', 'id, username, firstname, lastname, email');
foreach ($users as $user) {
    echo "User: $user->username | Name: $user->firstname $user->lastname | Email: $user->email\n";
}
