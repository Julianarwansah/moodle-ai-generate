<?php
define('MOODLE_INTERNAL', true);
require_once('config.php');

$apikey = get_config('local_ai_grader', 'apikey');

if (empty($apikey)) {
    die("API Key not configured in local_ai_grader settings.");
}

$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apikey;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

header('Content-Type: application/json');
echo $response;
