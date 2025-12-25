<?php
define('MOODLE_INTERNAL', true);
require_once('config.php');

global $CFG;

echo "Dataroot: " . $CFG->dataroot . "\n";
echo "Log File: " . $CFG->dataroot . '/gemini_debug.log' . "\n";

if (file_exists($CFG->dataroot . '/gemini_debug.log')) {
    echo "Content:\n";
    echo file_get_contents($CFG->dataroot . '/gemini_debug.log');
} else {
    echo "Log file not found.";
}
