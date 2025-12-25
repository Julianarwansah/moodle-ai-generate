<?php
defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_ai_grader_grade_essay' => [
        'classname' => 'local_ai_grader\external',
        'methodname' => 'grade_essay',
        'classpath' => 'local/ai_grader/classes/external.php',
        'description' => 'Grades an essay using Gemini AI',
        'type' => 'write',
        'ajax' => true,
    ],
    'local_ai_grader_get_logs' => [
        'classname' => 'local_ai_grader\external',
        'methodname' => 'get_logs',
        'classpath' => 'local/ai_grader/classes/external.php',
        'description' => 'Retrieves AI grading logs for an attempt',
        'type' => 'read',
        'ajax' => true,
    ],
];
