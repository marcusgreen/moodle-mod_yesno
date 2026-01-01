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

defined('MOODLE_INTERNAL') || die();

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
        // Define field score to be added to yesno_attempts
        $table = new xmldb_table('yesno_attempts');
        $field = new xmldb_field('score', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'status');

        // Conditionally launch add field score
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // yesno savepoint reached
        upgrade_mod_savepoint(true, 2025123101, 'yesno');
    }

    return true;
}