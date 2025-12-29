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
 * Library functions for the yesno module.
 *
 * @package    mod_yesno
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Update a yesno instance
 *
 * @param object $data
 * @param object $mform
 * @return bool true if successful
 * @package mod_yesno
 */
function yesno_update_instance($data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    // Updating the record.
    $result = $DB->update_record('yesno', $data);

    return $result;
}

/**
 * Delete a yesno instance
 *
 * @param int $id
 * @return bool true if successful
 * @package mod_yesno
 */
function yesno_delete_instance($id) {
    global $DB;

    // Deleting the record.
    $DB->delete_records('yesno', ['id' => $id]);

    return true;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $yesno
 * @return int
 * @package mod_yesno
 */
function yesno_add_instance($yesno) {
    global $DB;

    $yesno->timecreated = time();

    return $DB->insert_record('yesno', $yesno);
}
