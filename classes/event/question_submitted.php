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
 * The mod_yesno question submitted event.
 *
 * @package    mod_yesno
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_yesno\event;

/**
 * The mod_yesno question submitted event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - int yesnoid: id of the yesno activity.
 *      - int attemptid: id of the attempt.
 *      - int questionnumber: the question number within the attempt.
 * }
 *
 * @package    mod_yesno
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_submitted extends \core\event\base {
    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' submitted question " . $this->other['questionnumber'] .
            " for attempt with id '" . $this->other['attemptid'] . "' " .
            "in the yesno activity with course module id '$this->contextinstanceid'.";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventquestionsubmitted', 'mod_yesno');
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/yesno/view.php', ['id' => $this->contextinstanceid]);
    }

    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'yesno_history';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['yesnoid'])) {
            throw new \coding_exception('The \'yesnoid\' value must be set in other.');
        }
        if (!isset($this->other['attemptid'])) {
            throw new \coding_exception('The \'attemptid\' value must be set in other.');
        }
        if (!isset($this->other['questionnumber'])) {
            throw new \coding_exception('The \'questionnumber\' value must be set in other.');
        }
    }

    /**
     * Returns objectid mapping for restore.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'yesno_history', 'restore' => 'yesno_history'];
    }

    /**
     * Returns other values mapping for restore.
     *
     * @return array
     */
    public static function get_other_mapping() {
        return [
            'yesnoid' => ['db' => 'yesno', 'restore' => 'yesno'],
            'attemptid' => ['db' => 'yesno_attempts', 'restore' => 'yesno_attempt'],
            'questionnumber' => \core\event\base::NOT_MAPPED,
        ];
    }
}
