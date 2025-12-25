<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'AI Quiz Grader';
$string['apikey'] = 'Gemini API Key';
$string['apikey_desc'] = 'Enter your Google Gemini API Key here.';
$string['prompt_template'] = 'Prompt Template';
$string['prompt_template_desc'] = 'The prompt sent to the AI. Use placeholders {question}, {answer}, and {maxmark}.';
$string['grade_button'] = 'Grade with AI';
$string['grading'] = 'AI Grading...';
$string['error_grading'] = 'Error grading with AI: ';
$string['apikey_missing'] = 'Gemini API Key is missing. Please configure it in the plugin settings.';
$string['api_error'] = 'Gemini API Error: {$a}';
$string['api_invalid_response'] = 'Invalid response from AI provider.';
