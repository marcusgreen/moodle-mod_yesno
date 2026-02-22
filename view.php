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

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$y = optional_param('y', 0, PARAM_INT);

if ($id) {
    [$course, $cm] = get_course_and_cm_from_cmid($id, 'yesno');
    $yesno = $DB->get_record('yesno', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($y) {
    $yesno = $DB->get_record('yesno', ['id' => $y], '*', MUST_EXIST);
    [$course, $cm] = get_course_and_cm_from_instance($y, 'yesno');
} else {
    throw new moodle_exception('missingparameter');
}

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

// Get current user's attempt record.
$userattempt = $DB->get_record('yesno_attempts', [
    'userid' => $USER->id,
    'yesnoid' => $yesno->id,
]);
if (!$userattempt) {
    $userattempt = null;
}

// Initialize question count and check game status.
$questioncount = 0;
$score = 0;
$gamefinished = false;

if ($userattempt) {
    $questioncount = $userattempt->question_count;
    // Handle case where score field doesn't exist yet (before database upgrade).
    $score = isset($userattempt->score) ? $userattempt->score : 0;
    $gamefinished = ($userattempt->status === 'win' || $userattempt->status === 'loss');
}

// Display attempt information using mustache template.
echo yesno_render_attempt_info($yesno, $questioncount, $score, $modulecontext, $userattempt);

// Handle form submission.
$studentquestion = optional_param('student_question', '', PARAM_TEXT);

if (!empty($studentquestion) && confirm_sesskey()) {
    require_sesskey();
    xdebug_break();
    // Check if user has remaining attempts and game is not finished.
    if ($questioncount < $yesno->maxquestions && !$gamefinished) {
        $airesponse = '';
        $iscorrect = false;

        // If the student's question is the same as the secret word, they win.
        if (strcasecmp(trim($studentquestion), $yesno->secret) == 0) {
            $airesponse = "Yes you have guessed the secret";
            $iscorrect = true;
        } else if (stripos(trim($studentquestion), $yesno->secret) !== false) {
            $airesponse = "You have found the secret!";
            $iscorrect = true;
        } else {
            try {
                // Combine student question with system prompt.
                $promptwithsecret = str_replace('{{target_word}}', $yesno->secret, $yesno->system_prompt);
                $questionprefix = get_string('studentquestionprefix', 'yesno');
                $combinedprompt = $promptwithsecret . "\n\n" . $questionprefix . ": " . $studentquestion;

                // Use the AI bridge to get response.
                require_once(__DIR__ . '/classes/aibridge.php');
                $aibridge = new \mod_yesno\AiBridge($modulecontext->id);
                $airesponse = $aibridge->perform_request($combinedprompt, 'twentyquestions');
            } catch (Exception $e) {
                echo html_writer::start_tag('div', ['class' => 'alert alert-danger']);
                echo html_writer::tag('p', get_string('errorgettingresponse', 'yesno') . ': ' . $e->getMessage());
                echo html_writer::end_tag('div');
            }
        }

        if (!empty($airesponse)) {
            // Update attempt record.
            $attemptdata = new stdClass();
            $attemptdata->userid = $USER->id;
            $attemptdata->yesnoid = $yesno->id;
            $attemptdata->question_count = $questioncount + 1;
            $attemptdata->timemodified = time();

            // Initialize history if this is the first attempt.
            $history = [];
            if ($userattempt && !empty($userattempt->history)) {
                $history = json_decode($userattempt->history, true);
            }

            // Add current question and response to history.
            $history[] = [
                'question' => $studentquestion,
                'response' => $airesponse,
                'timestamp' => time(),
            ];

            // Process the attempt and calculate the score.
            $currentquestion = count($history);
            $processedattempt = yesno_process_attempt($yesno, $studentquestion, $airesponse, $currentquestion, $iscorrect);
            $score = $processedattempt['score'];
            $attemptdata->status = $processedattempt['status'];

            // Set the score in the attempt data.
            $attemptdata->score = $score;

            $attemptdata->history = json_encode($history);

            // Save or update the attempt record.
            if ($userattempt) {
                $attemptdata->id = $userattempt->id;
                $DB->update_record('yesno_attempts', $attemptdata);
            } else {
                $DB->insert_record('yesno_attempts', $attemptdata);
            }

            // Update Moodle gradebook if the game is finished.
            if ($attemptdata->status === 'win' || $attemptdata->status === 'loss') {
                yesno_update_gradebook($yesno, $USER->id, $score);
            }

            // Refresh the attempt data.
            $userattempt = $DB->get_record('yesno_attempts', [
                'userid' => $USER->id,
                'yesnoid' => $yesno->id,
            ]);
            $questioncount = $userattempt->question_count;
            $score = isset($userattempt->score) ? $userattempt->score : 0;
            $gamefinished = ($userattempt->status === 'win' || $userattempt->status === 'loss');
        }
    } else {
        echo html_writer::start_tag('div', ['class' => 'alert alert-warning']);
        echo html_writer::tag('p', get_string('maxquestionsreached', 'yesno'));
        echo html_writer::end_tag('div');
    }
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
