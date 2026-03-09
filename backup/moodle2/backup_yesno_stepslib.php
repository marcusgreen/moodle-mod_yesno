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
 * Defines all backup steps for mod_yesno.
 *
 * @package    mod_yesno
 * @category   backup
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define the complete yesno structure for backup, with file and id annotations.
 *
 * @package    mod_yesno
 * @category   backup
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_yesno_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the structure of the backup XML.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element.
        $yesno = new backup_nested_element('yesno', ['id'], [
            'name', 'intro', 'introformat', 'system_prompt',
            'max_characters', 'max_grade', 'maxquestions', 'max_questions',
            'show_answer_on_loss', 'timecreated', 'timemodified',
        ]);

        $secrets = new backup_nested_element('secrets');

        $secret = new backup_nested_element('secret', ['id'], [
            'secret', 'clue', 'sortorder',
        ]);

        $attempts = new backup_nested_element('attempts');

        $attempt = new backup_nested_element('attempt', ['id'], [
            'userid', 'secretid', 'question_count', 'status', 'score', 'timemodified',
        ]);

        $histories = new backup_nested_element('histories');

        $history = new backup_nested_element('history', ['id'], [
            'question', 'response', 'timecreated',
        ]);

        // Build the tree.
        $yesno->add_child($secrets);
        $secrets->add_child($secret);

        $yesno->add_child($attempts);
        $attempts->add_child($attempt);
        $attempt->add_child($histories);
        $histories->add_child($history);

        // Define sources (content-level, always backed up).
        $yesno->set_source_table('yesno', ['id' => backup::VAR_ACTIVITYID]);
        $secret->set_source_table('yesno_secrets', ['yesnoid' => backup::VAR_PARENTID], 'sortorder ASC');

        // User-level data only when userinfo is requested.
        if ($userinfo) {
            $attempt->set_source_table('yesno_attempts', ['yesnoid' => backup::VAR_PARENTID]);
            $history->set_source_table('yesno_history', ['attemptid' => backup::VAR_PARENTID], 'id ASC');
        }

        // Annotate IDs for remapping during restore.
        $attempt->annotate_ids('user', 'userid');
        $attempt->annotate_ids('yesno_secret', 'secretid');

        // Annotate files in the intro field.
        $yesno->annotate_files('mod_yesno', 'intro', null);

        return $this->prepare_activity_structure($yesno);
    }
}
