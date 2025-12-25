<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');
global $DB;

$attemptid = 1;
$logs = $DB->get_records('local_ai_grader_logs', ['attemptid' => $attemptid]);
echo "Attempt ID: $attemptid\n";
echo "Log count: " . count($logs) . "\n";
foreach ($logs as $log) {
    echo "ID: {$log->id}, QuestionID: {$log->questionid}, Mark: {$log->ai_mark}, Time: " . date('Y-m-d H:i:s', $log->timecreated) . "\n";
}
