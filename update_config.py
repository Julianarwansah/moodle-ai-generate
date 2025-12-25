import os

content = """<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'moodle_lms';
$CFG->dbuser    = 'root';
$CFG->dbpass    = '';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => '',
  'dbsocket' => '',
  'dbcollation' => 'utf8mb4_unicode_ci',
);

$CFG->wwwroot   = 'http://localhost:8080/moodle/public';
$CFG->dataroot  = 'C:/laragon/www/moodledata';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 0777;

// Mematikan slasharguments untuk kompatibilitas lebih luas
$CFG->slasharguments = false;

require_once(__DIR__ . '/public/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
"""

with open('c:/laragon/www/moodle/config.php', 'w') as f:
    f.write(content)

print("config.php updated successfully")
