<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_local_ai_grader_upgrade($oldversion)
{
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2024121505) {

        // Define table local_ai_grader_logs to be created.
        $table = new xmldb_table('local_ai_grader_logs');

        // Adding fields to table local_ai_grader_logs.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('attemptid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('ai_mark', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
        $table->add_field('ai_comment', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_ai_grader_logs.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table local_ai_grader_logs.
        $table->add_index('attempt_question', XMLDB_INDEX_NOTUNIQUE, ['attemptid', 'questionid']);

        // Conditionally launch create table for local_ai_grader_logs.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // local_ai_grader savepoint reached.
        upgrade_plugin_savepoint(true, 2024121505, 'local', 'ai_grader');
    }

    return true;
}
