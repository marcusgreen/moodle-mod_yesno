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

// Get module context early for capability checks.
$modulecontext = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/yesno:view', $modulecontext);

// Handle start attempt request (BEFORE loading attempt state).
$startattempt = optional_param('startattempt', 0, PARAM_INT);
if ($startattempt && confirm_sesskey()) {
    yesno_start_attempt($yesno, $USER->id);
    redirect(new moodle_url('/mod/yesno/view.php', ['id' => $cm->id]));
}

// Handle try another secret request - reset current attempt and start fresh.
$tryanother = optional_param('tryanother', 0, PARAM_INT);
if ($tryanother && confirm_sesskey()) {
    yesno_reset_attempt($yesno, $USER->id);
    yesno_start_attempt($yesno, $USER->id);
    redirect(new moodle_url('/mod/yesno/view.php', ['id' => $cm->id]));
}

// Handle abandon attempt request - reset current attempt and start fresh with new secret.
$abandon = optional_param('abandon', 0, PARAM_INT);
if ($abandon && confirm_sesskey()) {
    yesno_reset_attempt($yesno, $USER->id);
    yesno_start_attempt($yesno, $USER->id);
    redirect(new moodle_url('/mod/yesno/view.php', ['id' => $cm->id]));
}

// Handle finish session request.
$finish = optional_param('finish', 0, PARAM_INT);
if ($finish && confirm_sesskey()) {
    // Redirect to course page.
    redirect(new moodle_url('/course/view.php', ['id' => $course->id]));
}

// Load attempt state first to check if user has an existing attempt.
$attemptstate = lib::load_attempt_state($yesno, $USER->id);
$userattempt = $attemptstate['userattempt'];

// Load the appropriate secret(s) based on attempt state.
if ($userattempt && $userattempt->secretid) {
    // Load only the selected secret for this attempt.
    $secretrecord = $DB->get_record('yesno_secrets', ['id' => $userattempt->secretid]);
    if ($secretrecord) {
        $yesno->secret = $secretrecord->secret;
        $yesno->secrets = [$secretrecord->secret];
        $yesno->clues = [$secretrecord->clue];
    } else {
        $yesno->secrets = [];
        $yesno->clues = [];
    }
} else {
    // Load all secrets for teachers or when there's no active attempt.
    $secretrecords = $DB->get_records('yesno_secrets', ['yesnoid' => $cm->instance], 'sortorder');
    if (!empty($secretrecords)) {
        $yesno->secrets = [];
        $yesno->clues = [];
        foreach ($secretrecords as $secretrow) {
            $yesno->secrets[] = $secretrow->secret;
            $yesno->clues[] = $secretrow->clue;
        }
        // Store the first secret for backward compatibility.
        $firstsecret = reset($secretrecords);
        $yesno->secret = $firstsecret->secret;
    } else {
        $yesno->secrets = [];
        $yesno->clues = [];
    }
}

$PAGE->set_url('/mod/yesno/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($yesno->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

// Log the course module viewed event.
$event = \mod_yesno\event\course_module_viewed::create([
    'objectid' => $yesno->id,
    'context' => $modulecontext,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('yesno', $yesno);
$event->trigger();

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

// Show start attempt button if user has no attempt yet.
if (!$userattempt) {
    // Render welcome section with admin notice (if applicable) before the button.
    $welcomedata = [
        'show_welcome' => true,
        'description' => get_string('activitydescription', 'yesno'),
        'instructions' => get_string('startinstructions', 'yesno'),
        'show_admin_notice' => $canmanage,
        'admin_notice_text' => get_string('managemsg', 'yesno'),
    ];
    echo $OUTPUT->render_from_template('mod_yesno/welcome_section', $welcomedata);

    // Show start attempt button.
    echo yesno_render_start_attempt_button($modulecontext);
} else {
    // If user has started an attempt, show admin-only notices.
    if ($canmanage) {
        echo $OUTPUT->box(get_string('managemsg', 'yesno'), 'generalbox');

        // Display secret for current attempt (if teacher is making an attempt).
        if ($userattempt && $userattempt->secretid && !empty($yesno->secrets)) {
            $secrethtml = html_writer::start_tag('div', ['class' => 'alert alert-info']);
            $secrethtml .= html_writer::tag('strong', get_string('secrets', 'yesno') . ':');
            $secrethtml .= html_writer::start_tag('ul');
            // Only show the selected secret for this attempt.
            $secrethtml .= html_writer::tag('li', format_text($yesno->secrets[0], FORMAT_PLAIN));
            $secrethtml .= html_writer::end_tag('ul');
            $secrethtml .= html_writer::end_tag('div');
            echo $secrethtml;
        }
    }
}

// Handle reset request (teachers only) - do this BEFORE loading attempt state.
$resetuser = optional_param('resetuser', 0, PARAM_INT);
if ($resetuser && $canmanage && confirm_sesskey()) {
    yesno_reset_attempt($yesno, $resetuser);
    echo $OUTPUT->notification(get_string('sessionreset', 'yesno'), 'success');
    // Reload attempt state after reset.
    $attemptstate = lib::load_attempt_state($yesno, $USER->id);
    $userattempt = $attemptstate['userattempt'];
    $questioncount = $attemptstate['questioncount'];
    $score = $attemptstate['score'];
    $gamefinished = $attemptstate['gamefinished'];
}

// Extract attempt state variables (already loaded above).
$questioncount = $attemptstate['questioncount'];
$score = $attemptstate['score'];
$gamefinished = $attemptstate['gamefinished'];

// Handle Am I warm request.
$checkwarm = optional_param('checkwarm', 0, PARAM_INT);
if ($checkwarm && confirm_sesskey() && $userattempt && !$gamefinished) {
    $warmresult = yesno_check_warm($yesno, $userattempt, $modulecontext);
    if ($warmresult === 'yes') {
        echo $OUTPUT->notification(get_string('warmresultyes', 'yesno'), 'info');
    } else if ($warmresult === 'no') {
        echo $OUTPUT->notification(get_string('warmresultno', 'yesno'), 'warning');
    }
}

// Handle form submission BEFORE displaying attempt info.
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

// Two-column layout: main content left, conversation history right.
echo html_writer::start_tag('div', ['class' => 'row']);

// Left column: attempt info, question form, etc.
echo html_writer::start_tag('div', ['class' => 'col-md-7']);

// Display attempt information using mustache template (after handling submission).
// Only show if user has started an attempt.
if ($userattempt) {
    echo yesno_render_attempt_info($yesno, $questioncount, $score, $modulecontext, $userattempt);
    if ($gamefinished) {
        echo yesno_render_game_completion($yesno, $userattempt, $modulecontext);
    }
}

// Display most recent submission and response above the textarea.
echo yesno_render_last_response($userattempt, $modulecontext, $yesno);

// Display reset button for teachers.
if ($canmanage && $userattempt) {
    echo yesno_render_reset_button($modulecontext);
}

// Student question input form using mustache template.
// Only show if user has started an attempt and game is not finished.
if ($userattempt && !$gamefinished) {
    echo yesno_render_question_form($yesno, $modulecontext, $userattempt);
}

echo html_writer::end_tag('div');

// Right column: conversation history.
echo html_writer::start_tag('div', ['class' => 'col-md-5 yesno-history-column']);
echo yesno_render_conversation_history($userattempt, $modulecontext, $yesno);
echo html_writer::end_tag('div');

echo html_writer::end_tag('div');

// Add JavaScript for character counter using AMD module.
$PAGE->requires->js_call_amd('mod_yesno/charcounter', 'init');

// Add JavaScript for UI interactions (help toggle, etc).
$PAGE->requires->js_call_amd('mod_yesno/ui-interactions', 'init');

// Add JavaScript for celebration effects on win.
$PAGE->requires->js_call_amd('mod_yesno/celebration', 'init');

echo $OUTPUT->footer();
