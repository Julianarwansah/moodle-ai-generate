<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');
global $DB;

$log = $DB->get_record('local_ai_grader_logs', ['id' => 5]);
if ($log) {
    echo "ID: " . $log->id . "\n";
    echo "Type of ai_mark: " . gettype($log->ai_mark) . "\n";
    echo "Value of ai_mark: " . var_export($log->ai_mark, true) . "\n";
    echo "Type of ai_comment: " . gettype($log->ai_comment) . "\n";
} else {
    echo "Log 5 not found\n";
}
