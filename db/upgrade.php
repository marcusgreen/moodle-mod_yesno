<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Upgrade script for yesno module.
 *
 * @package    mod_yesno
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Function to upgrade yesno module.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_yesno_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025123101) {
        // Define field score to be added to yesno_attempts.
        $table = new xmldb_table('yesno_attempts');
        $field = new xmldb_field('score', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'status');

        // Conditionally launch add field score.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Yesno savepoint reached.
        upgrade_mod_savepoint(true, 2025123101, 'yesno');
    }

    if ($oldversion < 2026030100) {
        // Create yesno_history table.
        $table = new xmldb_table('yesno_history');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('attemptid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('question', XMLDB_TYPE_TEXT, null, null, null);
        $table->add_field('response', XMLDB_TYPE_TEXT, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_attempt', XMLDB_KEY_FOREIGN, ['attemptid'], 'yesno_attempts', ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Migrate existing JSON history to yesno_history rows.
        $attempts = $DB->get_records('yesno_attempts');
        foreach ($attempts as $attempt) {
            if (!empty($attempt->history)) {
                $historydata = json_decode($attempt->history, true);
                if (is_array($historydata)) {
                    foreach ($historydata as $item) {
                        $row = new stdClass();
                        $row->attemptid = $attempt->id;
                        $row->question = $item['question'] ?? '';
                        $row->response = $item['response'] ?? '';
                        $row->timecreated = $item['timestamp'] ?? time();
                        $DB->insert_record('yesno_history', $row);
                    }
                }
            }
        }

        // Drop history column from yesno_attempts.
        $table = new xmldb_table('yesno_attempts');
        $field = new xmldb_field('history');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Yesno savepoint reached.
        upgrade_mod_savepoint(true, 2026030100, 'yesno');
    }

    if ($oldversion < 2026030201) {
        // Create yesno_secrets table.
        $table = new xmldb_table('yesno_secrets');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('yesnoid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('secret', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('clue', XMLDB_TYPE_TEXT, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_yesno', XMLDB_KEY_FOREIGN, ['yesnoid'], 'yesno', ['id']);
        $table->add_index('yesnoid', XMLDB_INDEX_UNIQUE, ['yesnoid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Migrate existing data from yesno table to yesno_secrets.
        $instances = $DB->get_records('yesno', null, '', 'id, secret, clue');
        foreach ($instances as $instance) {
            $secret = new stdClass();
            $secret->yesnoid = $instance->id;
            $secret->secret = $instance->secret;
            $secret->clue = $instance->clue;
            $DB->insert_record('yesno_secrets', $secret);
        }

        // Drop secret and clue columns from yesno table.
        $table = new xmldb_table('yesno');
        $field = new xmldb_field('secret');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('clue');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Yesno savepoint reached.
        upgrade_mod_savepoint(true, 2026030201, 'yesno');
    }

    if ($oldversion < 2026030202) {
        // Add sortorder field to yesno_secrets.
        $table = new xmldb_table('yesno_secrets');
        $field = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'clue');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Remove unique constraint on yesnoid and add non-unique index.
        $table = new xmldb_table('yesno_secrets');
        $index = new xmldb_index('yesnoid', XMLDB_INDEX_UNIQUE, ['yesnoid']);

        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        $index = new xmldb_index('yesnoid', XMLDB_INDEX_NOTUNIQUE, ['yesnoid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Yesno savepoint reached.
        upgrade_mod_savepoint(true, 2026030202, 'yesno');
    }

    if ($oldversion < 2026030203) {
        // Add secretid field to yesno_attempts to track which secret was selected for this attempt.
        $table = new xmldb_table('yesno_attempts');
        $field = new xmldb_field('secretid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'yesnoid');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add foreign key for secretid.
        $key = new xmldb_key('fk_secret', XMLDB_KEY_FOREIGN, ['secretid'], 'yesno_secrets', ['id']);
        if (!$dbman->key_exists($table, $key)) {
            $dbman->add_key($table, $key);
        }

        // Yesno savepoint reached.
        upgrade_mod_savepoint(true, 2026030203, 'yesno');
    }

    return true;
}
