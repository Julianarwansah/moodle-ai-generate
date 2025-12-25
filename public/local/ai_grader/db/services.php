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
];
