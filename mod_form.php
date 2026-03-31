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
 * @copyright  2025 Marcus Green
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
        global $CFG, $DB, $PAGE;

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

        // Game settings section.
        $mform->addElement('header', 'gamesettingsheader', get_string('gamesettings', 'yesno'));

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
        $defaultmaxgrade = get_config('mod_yesno', 'maximumgrade');
        if ($defaultmaxgrade === false) {
            $defaultmaxgrade = 20; // Fallback default.
        }
        $mform->setDefault('max_grade', $defaultmaxgrade);
        $mform->addRule('max_grade', null, 'numeric', null, 'client');
        $mform->addRule('max_grade', get_string('maximumchars', '', 1000), 'maxlength', 4, 'client');
        $mform->addHelpButton('max_grade', 'maxgrade', 'yesno');

        // Adding the max questions field.
        $mform->addElement('text', 'max_questions', get_string('maxquestions', 'yesno'), ['size' => '6']);
        $mform->setType('max_questions', PARAM_INT);
        $mform->setDefault('max_questions', 20);
        $mform->addRule('max_questions', null, 'numeric', null, 'client');
        $mform->addRule('max_questions', get_string('maximumchars', '', 1000), 'maxlength', 4, 'client');
        $mform->addHelpButton('max_questions', 'maxquestions', 'yesno');

        // Adding checkbox to show answer on loss.
        $mform->addElement('checkbox', 'show_answer_on_loss', get_string('showanswer', 'yesno'));
        $mform->setType('show_answer_on_loss', PARAM_INT);
        $mform->setDefault('show_answer_on_loss', 1);
        $mform->addHelpButton('show_answer_on_loss', 'showanswer', 'yesno');

        // Adding checkbox to include Am I warm button.
        $mform->addElement('checkbox', 'amiwarm', get_string('amiwarm', 'yesno'));
        $mform->setType('amiwarm', PARAM_INT);
        $mform->setDefault('amiwarm', 0);
        $mform->addHelpButton('amiwarm', 'amiwarm', 'yesno');

        // Generate Secrets section.
        $mform->addElement('header', 'generatesecretsheader', get_string('generatesecrets', 'yesno'));
        $mform->setExpanded('generatesecretsheader', false);
        $mform->addHelpButton('generatesecretsheader', 'generatesecrets', 'yesno');

        $secretsprompt = get_config('mod_yesno', 'secretsprompt');
        if ($secretsprompt === false || $secretsprompt === '') {
            $secretsprompt = get_string('secretspromptdefault', 'yesno');
        }
        $genhtml = html_writer::start_div('yesno-generate-secrets');
        $genhtml .= html_writer::tag(
            'label',
            get_string('secretsprompt', 'yesno'),
            ['for' => 'id_secretsprompt', 'class' => 'form-label fw-bold']
        );
        $genhtml .= html_writer::tag('textarea', s($secretsprompt), [
            'id'    => 'id_secretsprompt',
            'name'  => 'secretsprompt',
            'class' => 'form-control mb-2',
            'rows'  => 3,
        ]);
        $genhtml .= html_writer::tag('button', get_string('generatesecrets', 'yesno'), [
            'type'  => 'button',
            'id'    => 'id_generate_secrets_btn',
            'class' => 'btn btn-secondary btn-sm',
        ]);
        $genhtml .= html_writer::end_div();
        $mform->addElement('html', $genhtml);

        $PAGE->requires->js_call_amd('mod_yesno/generate_secrets', 'init', [
            $PAGE->context->id,
        ]);

        // Export/import section.
        $mform->addElement('header', 'exportimportheader', get_string('exportimportsecrets', 'yesno'));
        $mform->setExpanded('exportimportheader', false);

        $iohtml = html_writer::start_div('mb-2');
        if ($this->_instance) {
            $exporturl = new moodle_url('/mod/yesno/export_secrets.php', ['id' => $this->_instance]);
            $iohtml .= html_writer::link(
                $exporturl,
                get_string('exportsecrets', 'yesno'),
                ['class' => 'btn btn-outline-secondary btn-sm me-2']
            );
        }
        $iohtml .= html_writer::tag(
            'label',
            get_string('importsecrets', 'yesno'),
            ['for' => 'id_importsecrets_file', 'class' => 'btn btn-outline-secondary btn-sm mb-0']
        );
        $iohtml .= html_writer::empty_tag('input', [
            'type'   => 'file',
            'id'     => 'id_importsecrets_file',
            'accept' => '.json',
            'class'  => 'visually-hidden',
        ]);
        $iohtml .= html_writer::end_div();
        $mform->addElement('html', $iohtml);

        $PAGE->requires->js_call_amd('mod_yesno/secrets_io', 'init');

        // Adding repeating group for secret/clue pairs.
        $mform->addElement('header', 'secretsheader', get_string('secrets', 'yesno'));

        $repeatcount = 1;
        if ($instance = $this->_instance) {
            $repeatcount = max(1, $DB->count_records('yesno_secrets', ['yesnoid' => $instance]));
        }

        $repeatarray = [];
        $repeatarray[] = $mform->createElement('text', 'secret', get_string('secret', 'yesno'), ['size' => '60']);
        $repeatarray[] = $mform->createElement('editor', 'clue', get_string('clue', 'yesno'));

        $repeateloptions = [
            'secret' => ['type' => PARAM_TEXT],
            'clue' => ['type' => PARAM_RAW],
        ];

        $this->repeat_elements(
            $repeatarray,
            $repeatcount,
            $repeateloptions,
            'secret_repeats',
            'secret_add_fields',
            1,
            get_string('addsecret', 'yesno'),
            true
        );

        $mform->setExpanded('secretsheader', false);

        // Add rules and help buttons for instances (support up to 10 repeats).
        for ($i = 0; $i < 10; $i++) {
            if ($mform->elementExists('secret[' . $i . ']')) {
                $mform->addRule('secret[' . $i . ']', null, 'required', null, 'client');
                $mform->addRule('secret[' . $i . ']', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
                $mform->addHelpButton('secret[' . $i . ']', 'secret', 'yesno');
                $mform->addHelpButton('clue[' . $i . ']', 'clue', 'yesno');
            }
        }

        // Adding standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Adding standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    /**
     * Pre-process form data before it is set as form defaults.
     *
     * @param array $defaultvalues Form default values passed by reference.
     */
    public function data_preprocessing(&$defaultvalues) {
        global $DB;

        parent::data_preprocessing($defaultvalues);

        if (empty($defaultvalues['instance'])) {
            $defaultvalues['clue'][0] = ['text' => '', 'format' => FORMAT_HTML];
            return;
        }

        $allsecrets = $DB->get_records('yesno_secrets', ['yesnoid' => $defaultvalues['instance']], 'sortorder');

        if (empty($allsecrets)) {
            $defaultvalues['clue'][0] = ['text' => '', 'format' => FORMAT_HTML];
            return;
        }

        $defaultvalues['secret'] = [];
        $defaultvalues['clue'] = [];

        foreach ($allsecrets as $secretrow) {
            $defaultvalues['secret'][] = $secretrow->secret;
            $defaultvalues['clue'][] = [
                'text' => $secretrow->clue,
                'format' => FORMAT_HTML,
            ];
        }

        $defaultvalues['secret_repeats'] = count($allsecrets);
    }
}
