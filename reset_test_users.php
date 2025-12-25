<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/config.php');
require_once($CFG->dirroot . '/user/lib.php');

function ensure_user($username, $password, $firstname, $lastname, $email)
{
    global $DB, $CFG;

    $user = $DB->get_record('user', ['username' => $username]);

    if ($user) {
        echo "User '$username' exists. Resetting password...\n";
        update_internal_user_password($user, $password);
    } else {
        echo "Creating user '$username'...\n";
        $user = new stdClass();
        $user->auth = 'manual';
        $user->confirmed = 1;
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->username = $username;
        $user->password = hash_internal_user_password($password);
        $user->firstname = $firstname;
        $user->lastname = $lastname;
        $user->email = $email;
        $user->country = 'ID';
        $user->city = 'Jakarta';
        $user->lang = 'en';

        try {
            $id = user_create_user($user);
            echo "Created '$username' (ID: $id).\n";
        } catch (Exception $e) {
            echo "Error creating '$username': " . $e->getMessage() . "\n";
        }
    }
}

// Ensure users exist with known password
$password = 'Moodle123!';

ensure_user('dosen', $password, 'Dosen', 'Pengajar', 'dosen@localhost.com');
ensure_user('mahasiswa', $password, 'Mahasiswa', 'Satu', 'mahasiswa@localhost.com');

echo "\nDone! Credentials:\n";
echo "Dosen: dosen / $password\n";
echo "Mahasiswa: mahasiswa / $password\n";
