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
 * Defines all restore steps for mod_yesno.
 *
 * @package    mod_yesno
 * @category   backup
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to restore one yesno activity.
 *
 * @package    mod_yesno
 * @category   backup
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_yesno_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define the XML paths to process during restore.
     *
     * @return array
     */
    protected function define_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('yesno', '/activity/yesno');
        $paths[] = new restore_path_element('yesno_secret', '/activity/yesno/secrets/secret');

        if ($userinfo) {
            $paths[] = new restore_path_element('yesno_attempt', '/activity/yesno/attempts/attempt');
            $paths[] = new restore_path_element('yesno_history', '/activity/yesno/attempts/attempt/histories/history');
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore the main yesno activity record.
     *
     * @param array $data
     */
    protected function process_yesno($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $newitemid = $DB->insert_record('yesno', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restore a yesno_secrets record.
     *
     * @param array $data
     */
    protected function process_yesno_secret($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->yesnoid = $this->get_new_parentid('yesno');

        $newitemid = $DB->insert_record('yesno_secrets', $data);
        $this->set_mapping('yesno_secret', $oldid, $newitemid);
    }

    /**
     * Restore a yesno_attempts record.
     *
     * @param array $data
     */
    protected function process_yesno_attempt($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->yesnoid = $this->get_new_parentid('yesno');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->secretid = $this->get_mappingid('yesno_secret', $data->secretid);

        $newitemid = $DB->insert_record('yesno_attempts', $data);
        $this->set_mapping('yesno_attempt', $oldid, $newitemid);
    }

    /**
     * Restore a yesno_history record.
     *
     * @param array $data
     */
    protected function process_yesno_history($data) {
        global $DB;

        $data = (object)$data;
        $data->attemptid = $this->get_new_parentid('yesno_attempt');

        $DB->insert_record('yesno_history', $data);
    }

    /**
     * Restore any files associated with the yesno intro field.
     */
    protected function after_execute() {
        $this->add_related_files('mod_yesno', 'intro', null);
    }
}
