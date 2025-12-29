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
 * Yes/No module form for editing yesno instance
 *
 * @package    mod_yesno
 * @copyright  2024 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module instance settings form
 */
class mod_yesno_mod_form extends moodleform_mod {
    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('yesnoname', 'yesno'), ['size' => '64']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'yesnoname', 'yesno');

        // Adding the optional "intro" and "introformat" pair of fields.
        $this->standard_intro_elements();

        // Adding the system prompt field.
        $mform->addElement('textarea', 'system_prompt', get_string('systemprompt', 'yesno'), ['rows' => 10, 'cols' => 60]);
        $mform->setType('system_prompt', PARAM_TEXT);
        $mform->addHelpButton('system_prompt', 'systemprompt', 'yesno');
        xdebug_break();
        // Set default value from settings
        $defaultprompt = get_config('yesno', 'defaultprompt');
        if ($defaultprompt !== false) {
            $mform->setDefault('system_prompt', $defaultprompt);
        }

        // Adding the max characters field.
        $mform->addElement('text', 'max_characters', get_string('maxcharacters', 'yesno'), ['size' => '6']);
        $mform->setType('max_characters', PARAM_INT);
        $mform->setDefault('max_characters', 200);
        $mform->addRule('max_characters', null, 'numeric', null, 'client');
        $mform->addRule('max_characters', get_string('maximumchars', '', 1000), 'maxlength', 4, 'client');
        $mform->addHelpButton('max_characters', 'maxcharacters', 'yesno');

        // Adding the max grade field.
        $mform->addElement('text', 'max_grade', get_string('maxgrade', 'yesno'), ['size' => '6']);
        $mform->setType('max_grade', PARAM_INT);
        $mform->setDefault('max_grade', 100);
        $mform->addRule('max_grade', null, 'numeric', null, 'client');
        $mform->addRule('max_grade', get_string('maximumchars', '', 1000), 'maxlength', 4, 'client');
        $mform->addHelpButton('max_grade', 'maxgrade', 'yesno');

        // Adding the clue field.
        $mform->addElement('editor', 'clue', get_string('clue', 'yesno'));
        $mform->setType('clue', PARAM_RAW);
        $mform->addHelpButton('clue', 'clue', 'yesno');

        // Adding standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Adding standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    /**
     * Set default values for the form
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        global $CFG;

        // Set default system prompt if not already set
        if (empty($defaultvalues['system_prompt'])) {
            $defaultprompt = get_config('yesno', 'defaultsystemprompt');
            if ($defaultprompt !== false) {
                $defaultvalues['system_prompt'] = $defaultprompt;
            }
        }

        // Preprocess the clue field (editor field)
        if (!empty($defaultvalues['clue'])) {
            $defaultvalues['clue'] = ['text' => $defaultvalues['clue'], 'format' => FORMAT_HTML];
        } else {
            $defaultvalues['clue'] = ['text' => '', 'format' => FORMAT_HTML];
        }
    }
}
