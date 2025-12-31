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
 * @copyright  2024 Marcus Green
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
    'yesnoid' => $yesno->id
]);

// Initialize question count.
$questioncount = 0;
if ($userattempt) {
    $questioncount = $userattempt->question_count;
}

// Display attempt information.
echo html_writer::start_tag('div', ['class' => 'yesno-attempt-info']);
echo html_writer::tag('p', get_string('attemptsinfo', 'yesno', ['count' => $questioncount, 'max' => $yesno->maxquestions]));
echo html_writer::end_tag('div');

// Display conversation history if available.
if ($userattempt && !empty($userattempt->history)) {
    $history = json_decode($userattempt->history, true);
    if (is_array($history) && count($history) > 0) {
        echo html_writer::start_tag('div', ['class' => 'yesno-conversation-history']);
        echo html_writer::tag('h4', get_string('conversationhistory', 'yesno'));

        foreach ($history as $item) {
            echo html_writer::start_tag('div', ['class' => 'history-item']);
            echo html_writer::tag('p', '<strong>' . get_string('yourquestion', 'yesno') . ':</strong> ' . s($item['question']));
            echo html_writer::tag('p', '<strong>' . get_string('airesponse', 'yesno') . ':</strong> ' . s($item['response']));
            echo html_writer::tag('p', '<small>' . userdate($item['timestamp']) . '</small>', ['class' => 'history-timestamp']);
            echo html_writer::end_tag('div');
        }

        echo html_writer::end_tag('div');
    }
}

// Student question input form.
echo html_writer::start_tag('div', ['class' => 'yesno-question-form']);
echo html_writer::tag('h3', get_string('askquestion', 'yesno'));

// Display the character limit information.
$maxchars = $yesno->max_characters;
echo html_writer::tag('p', get_string('charlimitinfo', 'yesno', $maxchars), ['class' => 'char-limit-info']);
echo html_writer::tag('p', get_string('charsremaining', 'yesno', ['remaining' => $maxchars, 'max' => $maxchars]),
    ['class' => 'char-counter', 'id' => 'char-counter']);

// Question input form.
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url->out(), 'class' => 'question-form']);
echo html_writer::tag('input', '', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::start_tag('div', ['class' => 'form-group']);
echo html_writer::tag('label', get_string('yourquestion', 'yesno'), ['for' => 'student_question']);
echo html_writer::tag('textarea', '', [
    'id' => 'student_question',
    'name' => 'student_question',
    'class' => 'form-control',
    'rows' => 3,
    'maxlength' => $maxchars,
    'placeholder' => get_string('enteryourquestion', 'yesno'),
    'required' => 'required'
]);
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['class' => 'form-group']);
echo html_writer::tag('button', get_string('submitquestion', 'yesno'), [
    'type' => 'submit',
    'class' => 'btn btn-primary'
]);
echo html_writer::end_tag('div');

echo html_writer::end_tag('form'); // Close form
echo html_writer::end_tag('div'); // Close question-form

// Handle form submission.
$studentquestion = optional_param('student_question', '', PARAM_TEXT);

if (!empty($studentquestion) && confirm_sesskey()) {
    require_sesskey();

    // Check if user has remaining attempts.
    xdebug_break();
    if ($questioncount < $yesno->maxquestions) {
            try {
                // Combine student question with system prompt.
                $combinedprompt = str_replace('{{target_word}}', $yesno->secret, $yesno->system_prompt) . "\n\n" . get_string('studentquestionprefix', 'yesno') . ": " . $studentquestion;

                // Use the AI bridge to get response.
                require_once(__DIR__ . '/classes/aibridge.php');
                $aibridge = new \quiz_aitext\aibridge($modulecontext->id);
                $airesponse = $aibridge->perform_request($combinedprompt, 'twentyquestions');

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
                    'timestamp' => time()
                ];

                $attemptdata->history = json_encode($history);

                // Save or update the attempt record.
                if ($userattempt) {
                    $attemptdata->id = $userattempt->id;
                    $DB->update_record('yesno_attempts', $attemptdata);
                } else {
                    $DB->insert_record('yesno_attempts', $attemptdata);
                }

                // Refresh the attempt data.
                $userattempt = $DB->get_record('yesno_attempts', [
                    'userid' => $USER->id,
                    'yesnoid' => $yesno->id
                ]);
                $questioncount = $userattempt->question_count;

                // Display the AI response.
                echo html_writer::start_tag('div', ['class' => 'yesno-ai-response']);
                echo html_writer::tag('h4', get_string('airesponse', 'yesno'));
                echo html_writer::tag('p', s($airesponse));
                echo html_writer::end_tag('div');

            } catch (Exception $e) {
                echo html_writer::start_tag('div', ['class' => 'alert alert-danger']);
                echo html_writer::tag('p', get_string('errorgettingresponse', 'yesno') . ': ' . $e->getMessage());
                echo html_writer::end_tag('div');
            }
        } else {
            echo html_writer::start_tag('div', ['class' => 'alert alert-warning']);
            echo html_writer::tag('p', get_string('maxquestionsreached', 'yesno'));
            echo html_writer::end_tag('div');
        }
}

// Add JavaScript for character counter using AMD module.
$PAGE->requires->js_call_amd('mod_yesno/charcounter', 'init');

// Display conversation history if available (moved to appear after question form).
if ($userattempt && !empty($userattempt->history)) {
    $history = json_decode($userattempt->history, true);
    if (is_array($history) && count($history) > 0) {
        echo html_writer::start_tag('div', ['class' => 'yesno-conversation-history']);
        echo html_writer::tag('h4', get_string('conversationhistory', 'yesno'));
        
        foreach ($history as $item) {
            echo html_writer::start_tag('div', ['class' => 'history-item']);
            echo html_writer::tag('p', '<strong>' . get_string('yourquestion', 'yesno') . ':</strong> ' . s($item['question']));
            echo html_writer::tag('p', '<strong>' . get_string('airesponse', 'yesno') . ':</strong> ' . s($item['response']));
            echo html_writer::tag('p', '<small>' . userdate($item['timestamp']) . '</small>', ['class' => 'history-timestamp']);
            echo html_writer::end_tag('div');
        }
        
        echo html_writer::end_tag('div');
    }
}

echo $OUTPUT->footer();
