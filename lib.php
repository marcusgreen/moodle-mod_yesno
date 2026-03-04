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
 * Library functions for the yesno module.
 *
 * @package    mod_yesno
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Update a yesno instance
 *
 * @param object $data
 * @param object $mform
 * @return bool true if successful
 * @package mod_yesno
 */
function yesno_update_instance(stdClass $data, ?moodleform $mform = null): bool {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    // Handle editor fields - they come as arrays with 'text' and 'format'.
    if (isset($data->system_prompt) && is_array($data->system_prompt)) {
        $data->system_prompt = $data->system_prompt['text'];
    }

    // Extract secrets and clues before updating main record.
    $secrets = $data->secret ?? [];
    if (!is_array($secrets)) {
        $secrets = [$secrets];
    }

    $clues = $data->clue ?? [];
    if (!is_array($clues)) {
        $clues = [$clues];
    }

    // Unset these from the main record before update.
    unset($data->secret);
    unset($data->clue);

    // Update main yesno record.
    $result = $DB->update_record('yesno', $data);

    // Delete old secrets for this instance.
    $DB->delete_records('yesno_secrets', ['yesnoid' => $data->id]);

    // Insert new secrets and clues.
    foreach ($secrets as $index => $secret) {
        if (!empty($secret)) {
            $secretrecord = new stdClass();
            $secretrecord->yesnoid = $data->id;
            $secretrecord->secret = $secret;

            // Handle editor field format.
            if (isset($clues[$index])) {
                if (is_array($clues[$index])) {
                    $secretrecord->clue = $clues[$index]['text'];
                } else {
                    $secretrecord->clue = $clues[$index];
                }
            } else {
                $secretrecord->clue = null;
            }

            $secretrecord->sortorder = $index;
            $DB->insert_record('yesno_secrets', $secretrecord);
        }
    }

    return $result;
}

/**
 * Delete a yesno instance
 *
 * @param int $id
 * @return bool true if successful
 * @package mod_yesno
 */
function yesno_delete_instance(int $id): bool {
    global $DB;

    // Delete history rows for all attempts of this instance.
    $attemptids = $DB->get_fieldset_select('yesno_attempts', 'id', 'yesnoid = ?', [$id]);
    if (!empty($attemptids)) {
        [$insql, $inparams] = $DB->get_in_or_equal($attemptids);
        $DB->delete_records_select('yesno_history', "attemptid $insql", $inparams);
    }

    // Delete attempts for this instance.
    $DB->delete_records('yesno_attempts', ['yesnoid' => $id]);

    // Delete secrets for this instance.
    $DB->delete_records('yesno_secrets', ['yesnoid' => $id]);

    // Delete the activity instance.
    $DB->delete_records('yesno', ['id' => $id]);

    return true;
}

/**
 * Render attempt information using mustache template
 *
 * @param object $yesno
 * @param int $questioncount
 * @param int $score
 * @param context_module $modulecontext
 * @param object $userattempt
 * @return string HTML output of attempt information
 * @package mod_yesno
 */
function yesno_render_attempt_info(
    stdClass $yesno,
    int $questioncount,
    int $score,
    context_module $modulecontext,
    ?stdClass $userattempt = null
): string {
    global $OUTPUT, $DB;

    // Ensure score is a valid integer.
    $score = is_numeric($score) ? (int)$score : 0;

    // Determine if game is finished.
    $gamefinished = false;
    $finalscore = 0;
    $gamestatus = 'active';

    if ($userattempt) {
        $gamefinished = ($userattempt->status === 'win' || $userattempt->status === 'loss');
        $finalscore = $userattempt->score;
        $gamestatus = $userattempt->status;
    }

    // Build game over message for loss.
    $gameoversecret = '';
    if ($gamefinished && $gamestatus === 'loss' && $userattempt && $userattempt->secretid) {
        $secret = $DB->get_field('yesno_secrets', 'secret', ['id' => $userattempt->secretid]);
        if ($secret) {
            $gameoversecret = "\nYes the secret was: " . $secret;
        }
    }

    $data = [
        'has_attempt_info' => true,
        'attempt_info_text' => get_string(
            'attemptsinfo',
            'yesno',
            ['count' => $questioncount, 'max' => $yesno->max_questions]
        ),
        'score' => $score,
        'max_score' => $yesno->max_grade,
        'show_score' => ($score > 0 && $gamefinished),
        'game_finished' => $gamefinished,
        'final_score' => $finalscore,
        'game_status' => $gamestatus,
        'is_win' => ($gamestatus === 'win'),
        'is_loss' => ($gamestatus === 'loss'),
        'game_over_message' => $gamefinished ?
            ($gamestatus === 'win' ?
                get_string(
                    'gamewon',
                    'yesno',
                    ['score' => $finalscore, 'max' => $yesno->max_grade]
                ) :
                ($gameoversecret ?: get_string('gamelost', 'yesno'))
            ) : '',
    ];

    return $OUTPUT->render_from_template('mod_yesno/attempt_info', $data);
}

/**
 * Render the last (most recent) response only
 *
 * @param object $userattempt
 * @param context_module $modulecontext
 * @return string HTML output of the last response
 * @package mod_yesno
 */
function yesno_render_last_response(?stdClass $userattempt, context_module $modulecontext): string {
    global $OUTPUT, $DB;

    if (!$userattempt) {
        return '';
    }

    $historyrows = $DB->get_records('yesno_history', ['attemptid' => $userattempt->id], 'id DESC', '*', 0, 1);
    if (empty($historyrows)) {
        return '';
    }

    $lastitem = reset($historyrows);

    $data = [
        'has_history' => true,
        'your_question_label' => get_string('yourquestion', 'yesno'),
        'ai_response_label' => get_string('airesponse', 'yesno'),
        'history_items' => [[
            'question' => format_text($lastitem->question, FORMAT_PLAIN),
            'response' => format_text($lastitem->response, FORMAT_PLAIN),
            'timestamp' => userdate($lastitem->timecreated),
        ]],
    ];

    return $OUTPUT->render_from_template('mod_yesno/conversation_history', $data);
}

/**
 * Render conversation history (excluding the last response) using mustache template
 *
 * @param object $userattempt
 * @param context_module $modulecontext
 * @return string HTML output of conversation history
 * @package mod_yesno
 */
function yesno_render_conversation_history(?stdClass $userattempt, context_module $modulecontext): string {
    global $OUTPUT, $DB;

    if (!$userattempt) {
        return '';
    }

    $historyrows = $DB->get_records('yesno_history', ['attemptid' => $userattempt->id], 'id ASC');
    if (count($historyrows) < 2) {
        return '';
    }

    // Remove the last item (most recent) and reverse to show newest first (excluding current).
    array_pop($historyrows);
    $historyrows = array_reverse($historyrows);

    $historyitems = [];
    foreach ($historyrows as $item) {
        $historyitems[] = [
            'question' => format_text($item->question, FORMAT_PLAIN),
            'response' => format_text($item->response, FORMAT_PLAIN),
            'timestamp' => userdate($item->timecreated),
        ];
    }

    $data = [
        'has_history' => true,
        'conversation_history_title' => get_string('conversationhistory', 'yesno'),
        'your_question_label' => get_string('yourquestion', 'yesno'),
        'ai_response_label' => get_string('airesponse', 'yesno'),
        'history_items' => $historyitems,
    ];

    return $OUTPUT->render_from_template('mod_yesno/conversation_history', $data);
}

/**
 * Render reset button for teachers
 *
 * @param context_module $modulecontext
 * @return string HTML output of reset button
 * @package mod_yesno
 */
function yesno_render_reset_button(context_module $modulecontext): string {
    global $OUTPUT;

    $reseturl = new moodle_url($modulecontext->get_url(), ['resetuser' => 1, 'sesskey' => sesskey()]);
    $confirmtext = get_string('resetconfirm', 'yesno');

    $data = [
        'reset_button_url' => $reseturl->out(false),
        'reset_button_text' => get_string('resetsession', 'yesno'),
        'reset_confirm_text_json' => json_encode($confirmtext),
    ];

    return $OUTPUT->render_from_template('mod_yesno/reset_button', $data);
}

/**
 * Render question form using mustache template
 *
 * @param object $yesno
 * @param context_module $modulecontext
 * @return string HTML output of question form
 * @package mod_yesno
 */
function yesno_render_question_form(stdClass $yesno, context_module $modulecontext): string {
    global $OUTPUT;

    $data = [
        'ask_question_title' => get_string('askquestion', 'yesno'),
        'char_limit_info' => true,
        'char_limit_info_text' => get_string('charlimitinfo', 'yesno', $yesno->max_characters),
        'char_counter_text' => get_string('charsremaining', 'yesno', [
            'remaining' => $yesno->max_characters,
            'max' => $yesno->max_characters,
        ]),
        'form_action' => $modulecontext->get_url()->out(),
        'sesskey' => sesskey(),
        'your_question_label' => get_string('yourquestion', 'yesno'),
        'help_button' => $OUTPUT->help_icon('yourquestion', 'yesno'),
        'max_characters' => $yesno->max_characters,
        'enter_question_placeholder' => get_string('enteryourquestion', 'yesno'),
        'submit_button_text' => get_string('submitquestion', 'yesno'),
    ];

    return $OUTPUT->render_from_template('mod_yesno/question_form', $data);
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $yesno
 * @return int
 * @package mod_yesno
 */
function yesno_add_instance(stdClass $yesno): int {
    global $DB;

    $yesno->timecreated = time();

    // Handle editor fields - they come as arrays with 'text' and 'format'.
    if (isset($yesno->system_prompt) && is_array($yesno->system_prompt)) {
        $yesno->system_prompt = $yesno->system_prompt['text'];
    }

    // Set default prompt if not provided.
    if (empty($yesno->system_prompt)) {
        $yesno->system_prompt = get_config('mod_yesno', 'defaultprompt');
    }

    // Extract secrets and clues before inserting main record.
    $secrets = $yesno->secret ?? [];
    if (!is_array($secrets)) {
        $secrets = [$secrets];
    }

    $clues = $yesno->clue ?? [];
    if (!is_array($clues)) {
        $clues = [$clues];
    }

    // Unset these from the main record before insert.
    unset($yesno->secret);
    unset($yesno->clue);

    // Insert main yesno record.
    $yesnoid = $DB->insert_record('yesno', $yesno);

    // Insert secrets and clues into yesno_secrets table.
    foreach ($secrets as $index => $secret) {
        if (!empty($secret)) {
            $secretrecord = new stdClass();
            $secretrecord->yesnoid = $yesnoid;
            $secretrecord->secret = $secret;

            // Handle editor field format.
            if (isset($clues[$index])) {
                if (is_array($clues[$index])) {
                    $secretrecord->clue = $clues[$index]['text'];
                } else {
                    $secretrecord->clue = $clues[$index];
                }
            } else {
                $secretrecord->clue = null;
            }

            $secretrecord->sortorder = $index;
            $DB->insert_record('yesno_secrets', $secretrecord);
        }
    }

    return $yesnoid;
}

/**
 * Process a user's attempt, calculate the score, and determine the game status.
 *
 * @param object $yesno The yesno activity object.
 * @param string $studentquestion The student's submitted question.
 * @param string $airesponse The response from the AI.
 * @param int $currentquestion The number of the current question.
 * @param bool $iscorrect Whether the student's guess was correct.
 * @return array An array containing the calculated score and the game status.
 */
function yesno_process_attempt(stdClass $yesno, string $studentquestion, string $airesponse, int $currentquestion, bool $iscorrect = false): array {
    // Check if the AI response indicates any target word was found.
    if (!$iscorrect) {
        $airesponselower = strtolower($airesponse);

        // Check if any secret word appears in the response.
        if (!empty($yesno->secrets) && is_array($yesno->secrets)) {
            foreach ($yesno->secrets as $secret) {
                $targetwordlower = strtolower($secret);
                if (strpos($airesponselower, $targetwordlower) !== false) {
                    $iscorrect = true;
                    break;
                }
            }
        } else if (!empty($yesno->secret)) {
            // Fallback for single secret (backward compatibility).
            $targetwordlower = strtolower($yesno->secret);
            $iscorrect = (strpos($airesponselower, $targetwordlower) !== false);
        }
    }

    if ($iscorrect) {
        // If correct answer found, calculate proportional score.
        $score = max(0, $yesno->max_grade - ($currentquestion - 1));
        $status = 'win';
    } else if ($currentquestion >= $yesno->max_questions) {
        // If no correct answer and max questions reached, score is 0.
        $score = 0;
        $status = 'loss';
    } else {
        // Game still active, no score yet.
        $score = 0;
        $status = 'active';
    }

    return ['score' => $score, 'status' => $status];
}

/**
 * Update the Moodle gradebook with the user's score for the yesno activity.
 *
 * @param object $yesno The yesno activity object.
 * @param int $userid The user's ID.
 * @param float $score The score to be recorded.
 */
function yesno_update_gradebook(stdClass $yesno, int $userid, float $score): bool {
    global $CFG;

    require_once($CFG->libdir . '/gradelib.php');

    $grade = new stdClass();
    $grade->userid = $userid;
    $grade->rawgrade = $score;
    $grade->maxgrade = $yesno->max_grade;
    $grade->timecreated = time();
    $grade->timemodified = time();

    grade_update('mod/yesno', $yesno->course, 'mod', 'yesno', $yesno->id, 0, $grade);
    return true;
}

/**
 * Reset a user's attempt for a yesno activity.
 *
 * @param object $yesno The yesno activity object.
 * @param int $userid The user's ID.
 * @return bool True if successful.
 */
function yesno_reset_attempt(stdClass $yesno, int $userid): bool {
    global $DB, $CFG;

    // Get the attempt record.
    $attempt = $DB->get_record('yesno_attempts', [
        'userid' => $userid,
        'yesnoid' => $yesno->id,
    ]);

    if (!$attempt) {
        return true; // No attempt to reset.
    }

    // Delete history entries for this attempt.
    $DB->delete_records('yesno_history', ['attemptid' => $attempt->id]);

    // Delete the attempt record.
    $DB->delete_records('yesno_attempts', ['id' => $attempt->id]);

    // Reset the gradebook.
    require_once($CFG->libdir . '/gradelib.php');
    grade_update('mod/yesno', $yesno->course, 'mod', 'yesno', $yesno->id, 0, null, ['userid' => $userid]);

    return true;
}
