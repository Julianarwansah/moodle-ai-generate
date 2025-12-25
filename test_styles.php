<?php
// Mock enough of Moodle to run a small part or just check if the file is accessible
$file = __DIR__ . '/public/theme/styles.php';
if (file_exists($file)) {
    echo "styles.php exists.\n";
} else {
    echo "styles.php not found.\n";
}

// Check if we can reach it via local web request if possible
$url = "http://localhost:8080/moodle/public/theme/styles.php";
// Note: This might fail if the server requires auth or port is different
echo "Testing URL: $url\n";
