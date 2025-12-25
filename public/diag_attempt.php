<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');
global $DB;

$attemptid = 1;
$a = $DB->get_record('quiz_attempts', ['id' => $attemptid]);
if ($a) {
    echo "Attempt ID: {$a->id}\n";
    echo "Unique ID (Usage ID): {$a->uniqueid}\n";

    $q_attempts = $DB->get_records('question_attempts', ['questionusageid' => $a->uniqueid]);
    echo "Question Attempts for Usage ID {$a->uniqueid}:\n";
    foreach ($q_attempts as $qa) {
        echo "Slot: {$qa->slot}, QuestionID: {$qa->questionid}, ID: {$qa->id}\n";
    }
} else {
    echo "Attempt not found.\n";
}
