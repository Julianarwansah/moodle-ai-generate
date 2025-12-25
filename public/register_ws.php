<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/externallib.php');

global $DB;

$component = 'local_ai_grader';
echo "Registering Web Service functions for $component...\n";

$functions = [
    'local_ai_grader_get_logs' => [
        'classname' => 'local_ai_grader\external',
        'methodname' => 'get_logs',
        'classpath' => 'local/ai_grader/classes/external.php',
        'description' => 'Retrieves AI grading logs for an attempt',
        'type' => 'read',
        'ajax' => true,
    ],
    'local_ai_grader_grade_essay' => [
        'classname' => 'local_ai_grader\external',
        'methodname' => 'grade_essay',
        'classpath' => 'local/ai_grader/classes/external.php',
        'description' => 'Grades an essay using Gemini AI',
        'type' => 'write',
        'ajax' => true,
    ],
];

foreach ($functions as $name => $data) {
    $function = $DB->get_record('external_functions', ['name' => $name]);
    if (!$function) {
        $function = new stdClass();
        $function->name = $name;
        $function->classname = $data['classname'];
        $function->methodname = $data['methodname'];
        $function->classpath = $data['classpath'];
        $function->description = $data['description'];
        $function->component = $component;
        $function->capabilities = '';
        $function->services = '';
        $DB->insert_record('external_functions', $function);
        echo "Inserted $name\n";
    } else {
        $function->classname = $data['classname'];
        $function->methodname = $data['methodname'];
        $function->classpath = $data['classpath'];
        $function->description = $data['description'];
        $DB->update_record('external_functions', $function);
        echo "Updated $name\n";
    }
}

// Clear caches
purge_all_caches();
echo "Done!\n";
