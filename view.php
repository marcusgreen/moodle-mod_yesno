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
 * Yes/No view page
 *
 * @package    mod_yesno
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/modinfolib.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/classes/lib.php');
use mod_yesno\lib;

// Course module id.
$id = required_param('id', PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'yesno');
$yesno = $DB->get_record('yesno', ['id' => $cm->instance], '*', MUST_EXIST);

$modulecontext = context_module::instance($cm->id);
require_login($course, true, $cm);
$PAGE->set_url('/mod/yesno/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($yesno->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

// Add external stylesheet (must be done before header is printed).
$PAGE->requires->css('/mod/yesno/styles.css');

echo $OUTPUT->header();

// Output the module name and description.
echo $OUTPUT->heading(format_string($yesno->name));

// Display the intro (description) if it exists.
if (!empty($yesno->intro)) {
    echo $OUTPUT->box(format_module_intro('yesno', $yesno, $cm->id), 'generalbox', 'intro');
}

// Check if user can manage the activity.
$canmanage = has_capability('mod/yesno:manage', $modulecontext);

// Display different content based on capability.
if ($canmanage) {
    echo $OUTPUT->box(get_string('managemsg', 'yesno'), 'generalbox');
} else {
    echo $OUTPUT->box(get_string('viewmsg', 'yesno'), 'generalbox');
}

// Add some basic styling.
echo html_writer::tag(
    'div',
    get_string('activitydescription', 'yesno'),
    ['class' => 'yesno-description']
);

// Load current user's attempt state.
$attemptstate = lib::load_attempt_state($yesno, $USER->id);
$userattempt = $attemptstate['userattempt'];
$questioncount = $attemptstate['questioncount'];
$score = $attemptstate['score'];
$gamefinished = $attemptstate['gamefinished'];

// Display attempt information using mustache template.
echo yesno_render_attempt_info($yesno, $questioncount, $score, $modulecontext, $userattempt);

// Handle form submission.
$studentquestion = optional_param('student_question', '', PARAM_TEXT);

if (!empty($studentquestion) && confirm_sesskey()) {
    // Process form submission through class handler.
    $attemptstate = lib::handle_submission(
        $yesno,
        $modulecontext,
        $userattempt,
        $questioncount,
        $gamefinished,
        $studentquestion
    );
    $userattempt = $attemptstate['userattempt'];
    $questioncount = $attemptstate['questioncount'];
    $score = $attemptstate['score'];
    $gamefinished = $attemptstate['gamefinished'];
}

// Display most recent submission and response above the textarea.
echo yesno_render_last_response($userattempt, $modulecontext);

// Student question input form using mustache template.
if (!$gamefinished) {
    echo yesno_render_question_form($yesno, $modulecontext);
} else {
    echo html_writer::start_tag('div', ['class' => 'alert alert-info']);
    echo html_writer::tag('p', get_string('gamefinishedmsg', 'yesno'));
    echo html_writer::end_tag('div');
}

// Add JavaScript for character counter using AMD module.
$PAGE->requires->js_call_amd('mod_yesno/charcounter', 'init');

// Display conversation history if available (moved to appear after question form).
echo yesno_render_conversation_history($userattempt, $modulecontext);

echo $OUTPUT->footer();
