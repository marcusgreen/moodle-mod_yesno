<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Data generator for the yesno activity module.
 *
 * Allows unit tests to create a fully‑initialised yesno activity using
 * `$this->getDataGenerator()->create_module('yesno', $record);`
 *
 * @package    mod_yesno
 * @category   test
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * yesno module test data generator.
 */
class mod_yesno_generator extends testing_module_generator {
    /**
     * Create a yesno instance.
     *
     * @param stdClass|array|null $record  Data for the activity. Any missing fields are
     *                                      filled with sensible defaults.
     * @param array|null          $options Options passed to the parent generator.
     * @return stdClass The created module record (including id, course, etc.).
     */
    public function create_instance($record = null, $options = null) {
        $record = (object) (array) $record; // ensure we have an object.

        // Required fields with sensible defaults.
        if (empty($record->name)) {
            $record->name = 'Test yesno activity';
        }
        if (empty($record->max_grade)) {
            $record->max_grade = 100;
        }
        if (empty($record->max_questions)) {
            $record->max_questions = 5;
        }
        if (empty($record->max_characters)) {
            $record->max_characters = 250;
        }
        if (empty($record->secret)) {
            $record->secret = 'secret';
        }
        if (empty($record->system_prompt)) {
            $record->system_prompt = get_config('mod_yesno', 'defaultprompt');
        }
        // Optional editor fields – ensure they are stored as plain text.
        if (isset($record->clue) && is_array($record->clue)) {
            $record->clue = $record->clue['text'];
        }
        if (isset($record->system_prompt) && is_array($record->system_prompt)) {
            $record->system_prompt = $record->system_prompt['text'];
        }

        // Delegate to the core module generator which creates the course module
        // record, the entry in the yesno table, and the associated context.
        return parent::create_instance($record, $options);
    }
}
