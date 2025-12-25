<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');

echo "WWW Root: " . $CFG->wwwroot . "\n";
echo "Data Root: " . $CFG->dataroot . "\n";
echo "Is Writable: " . (is_writable($CFG->dataroot) ? 'Yes' : 'No') . "\n";

if (!file_exists($CFG->dataroot)) {
    echo "Data Root does not exist!\n";
} else {
    echo "Data Root exists.\n";
}

echo "Slash Arguments: " . ($CFG->slasharguments ? 'On' : 'Off') . "\n";
