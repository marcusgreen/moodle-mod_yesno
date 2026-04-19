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
 * Return feature support for this module.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool|null True if module supports feature, false if not, null if unknown
 * @package mod_yesno
 */
function yesno_supports(string $feature): bool | string | null {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        default:
            return null;
    }
}

/**
 * Update a yesno instance
 *
 * @param stdClass $data
 * @param moodleform|null $mform
 * @return bool true if successful
 * @package mod_yesno
 */
function yesno_update_instance(stdClass $data, ?moodleform $mform = null): bool {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

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

    // Checkboxes are absent from submitted data when unchecked.
    $data->amiwarm = !empty($data->amiwarm) ? 1 : 0;
    $data->show_answer_on_loss = !empty($data->show_answer_on_loss) ? 1 : 0;

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
 * @param stdClass $yesno
 * @param int $questioncount
 * @param int $score
 * @param context_module $modulecontext
 * @param stdClass|null $userattempt
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
    if (
        $gamefinished && $gamestatus === 'loss' && !empty($yesno->show_answer_on_loss)
            && $userattempt && $userattempt->secretid
    ) {
        $secret = $DB->get_field('yesno_secrets', 'secret', ['id' => $userattempt->secretid]);
        if ($secret) {
            $gameoversecret = get_string('revealsecretmsg', 'yesno', $secret);
        }
    }

    // Calculate progress percentage.
    $progresspercentage = ($yesno->max_questions > 0) ? (int)(($questioncount / $yesno->max_questions) * 100) : 0;

    $data = [
        'has_attempt_info' => true,
        'attempt_info_text' => get_string(
            'attemptsinfo',
            'yesno',
            ['count' => $questioncount, 'max' => $yesno->max_questions]
        ),
        'progress_percentage' => $progresspercentage,
        'question_count' => $questioncount,
        'max_questions' => $yesno->max_questions,
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
 * Strip surrounding quote characters from an AI response string.
 *
 * @param string $response The raw AI response text.
 * @return string The response with leading/trailing quotes removed.
 * @package mod_yesno
 */
function yesno_strip_quotes(string $response): string {
    return preg_replace('/^[\s\x{201C}\x{2018}"\']+|[\s\x{201D}\x{2019}"\']+$/u', '', $response);
}

/**
 * Check if a response contains the secret word
 *
 * @param string $response The AI response text
 * @param stdClass $yesno The yesno activity object
 * @return bool True if the response contains the secret
 * @package mod_yesno
 */
function yesno_response_is_correct(string $response, stdClass $yesno): bool {
    $responselower = strtolower($response);

    // Check if the AI confirmed the secret was found (without revealing it).
    if (strpos($responselower, 'you have found the secret') !== false) {
        return true;
    }

    // Check if any secret word appears in the response.
    if (!empty($yesno->secrets) && is_array($yesno->secrets)) {
        foreach ($yesno->secrets as $secret) {
            $targetwordlower = strtolower($secret);
            if (strpos($responselower, $targetwordlower) !== false) {
                return true;
            }
        }
    } else if (!empty($yesno->secret)) {
        // Fallback for single secret (backward compatibility).
        $targetwordlower = strtolower($yesno->secret);
        return (strpos($responselower, $targetwordlower) !== false);
    }

    return false;
}

/**
 * Render the last (most recent) response only
 *
 * @param stdClass|null $userattempt
 * @param context_module $modulecontext
 * @param stdClass $yesno
 * @return string HTML output of the last response
 * @package mod_yesno
 */
function yesno_render_last_response(?stdClass $userattempt, context_module $modulecontext, stdClass $yesno): string {
    global $OUTPUT, $DB;

    if (!$userattempt) {
        return '';
    }

    $historyrows = $DB->get_records('yesno_history', ['attemptid' => $userattempt->id], 'id DESC', '*', 0, 1);
    if (empty($historyrows)) {
        return '';
    }

    $lastitem = reset($historyrows);
    $iscorrect = yesno_response_is_correct($lastitem->response, $yesno);

    $data = [
        'has_history' => true,
        'your_question_label' => get_string('yourquestion', 'yesno'),
        'ai_response_label' => get_string('airesponse', 'yesno'),
        'history_items' => [[
            'question' => format_text($lastitem->question, FORMAT_PLAIN),
            'response' => format_text(yesno_strip_quotes($lastitem->response), FORMAT_HTML),
            'timestamp' => userdate($lastitem->timecreated),
            'is_correct' => $iscorrect,
            'feedback_icon' => $iscorrect ? '✓' : '○',
            'feedback_label' => $iscorrect ? get_string('correctanswer', 'yesno') : get_string('incorrectanswer', 'yesno'),
        ]],
    ];

    return $OUTPUT->render_from_template('mod_yesno/conversation_history', $data);
}

/**
 * Render conversation history (excluding the last response) using mustache template
 *
 * @param stdClass|null $userattempt
 * @param context_module $modulecontext
 * @param stdClass $yesno
 * @return string HTML output of conversation history
 * @package mod_yesno
 */
function yesno_render_conversation_history(?stdClass $userattempt, context_module $modulecontext, stdClass $yesno): string {
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
        $iscorrect = yesno_response_is_correct($item->response, $yesno);
        $historyitems[] = [
            'question' => format_text($item->question, FORMAT_PLAIN),
            'response' => format_text(yesno_strip_quotes($item->response), FORMAT_HTML),
            'timestamp' => userdate($item->timecreated),
            'is_correct' => $iscorrect,
            'feedback_icon' => $iscorrect ? '✓' : '○',
            'feedback_label' => $iscorrect ? get_string('correctanswer', 'yesno') : get_string('incorrectanswer', 'yesno'),
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
 * @param stdClass $yesno
 * @param context_module $modulecontext
 * @param stdClass|null $userattempt
 * @return string HTML output of question form
 * @package mod_yesno
 */
function yesno_render_question_form(stdClass $yesno, context_module $modulecontext, ?stdClass $userattempt = null): string {
    global $OUTPUT, $DB;

    $clue = (!empty($yesno->clues) && !empty($yesno->clues[0])) ? $yesno->clues[0] : '';

    $abandonurl = new moodle_url(
        $modulecontext->get_url(),
        ['abandon' => 1, 'sesskey' => sesskey()]
    );

    $warmurl = new moodle_url(
        $modulecontext->get_url(),
        ['checkwarm' => 1, 'sesskey' => sesskey()]
    );

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
        'clue' => $clue,
        'has_clue' => !empty($clue),
        'clue_label' => get_string('clue', 'yesno'),
        'abandon_button_url' => $abandonurl->out(false),
        'abandon_button_text' => get_string('abandonattempt', 'yesno'),
        'abandon_confirm_text_json' => json_encode(get_string('abandonconfirm', 'yesno')),
        'show_warm_button' => !empty($yesno->amiwarm) && $userattempt &&
            $DB->count_records('yesno_history', ['attemptid' => $userattempt->id]) >= 3,
        'warm_button_url' => $warmurl->out(false),
        'warm_button_text' => get_string('amiwarmbtn', 'yesno'),
    ];

    return $OUTPUT->render_from_template('mod_yesno/question_form', $data);
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $yesno
 * @return int
 * @package mod_yesno
 */
function yesno_add_instance(stdClass $yesno): int {
    global $DB;

    $yesno->timecreated = time();

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

    // Checkboxes are absent from submitted data when unchecked.
    $yesno->amiwarm = !empty($yesno->amiwarm) ? 1 : 0;
    $yesno->show_answer_on_loss = !empty($yesno->show_answer_on_loss) ? 1 : 0;

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
 * @param stdClass $yesno The yesno activity object.
 * @param string $studentquestion The student's submitted question.
 * @param string $airesponse The response from the AI.
 * @param int $currentquestion The number of the current question.
 * @param bool $iscorrect Whether the student's guess was correct.
 * @return array An array containing the calculated score and the game status.
 */
function yesno_process_attempt(
    stdClass $yesno,
    string $studentquestion,
    string $airesponse,
    int $currentquestion,
    bool $iscorrect = false
): array {
    // Check if the AI response indicates any target word was found.
    if (!$iscorrect) {
        $airesponselower = strtolower($airesponse);

        // Check if the AI confirmed the secret was found.
        if (strpos($airesponselower, 'you have found the secret') !== false) {
            $iscorrect = true;
        }

        // Check if any secret word appears in the response.
        if (!$iscorrect) {
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
 * @param stdClass $yesno The yesno activity object.
 * @param int $userid The user's ID.
 * @param float $score The score to be recorded.
 * @return bool True if successful.
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

    grade_update('mod/yesno', $yesno->course, 'mod', 'yesno', $yesno->id, 0, $grade, ['itemname' => $yesno->name]);
    return true;
}

/**
 * Reset a user's attempt for a yesno activity.
 *
 * @param stdClass $yesno The yesno activity object.
 * @param int $userid The user's ID.
 * @return bool True if successful.
 */
function yesno_reset_attempt(stdClass $yesno, int $userid): bool {
    global $DB, $CFG;

    // Get all attempt records for this user (duplicates may exist in legacy data).
    $attempts = $DB->get_records('yesno_attempts', [
        'userid' => $userid,
        'yesnoid' => $yesno->id,
    ]);

    if (empty($attempts)) {
        return true; // No attempt to reset.
    }

    // Delete history entries and attempts for every matching record.
    foreach ($attempts as $attempt) {
        $DB->delete_records('yesno_history', ['attemptid' => $attempt->id]);
        $DB->delete_records('yesno_attempts', ['id' => $attempt->id]);
    }

    // Reset the gradebook.
    require_once($CFG->libdir . '/gradelib.php');
    grade_update(
        'mod/yesno',
        $yesno->course,
        'mod',
        'yesno',
        $yesno->id,
        0,
        null,
        ['itemname' => $yesno->name, 'userid' => $userid]
    );

    return true;
}

/**
 * Create a new attempt for a user by selecting a random secret
 *
 * @param stdClass $yesno The yesno activity object.
 * @param int $userid The user's ID.
 * @return stdClass The created attempt record.
 * @package mod_yesno
 */
function yesno_start_attempt(stdClass $yesno, int $userid): stdClass {
    global $DB;

    // Return existing attempt if one already exists (prevents reset on page refresh).
    $existingattempt = $DB->get_record_sql(
        'SELECT * FROM {yesno_attempts} WHERE userid = ? AND yesnoid = ? ORDER BY id DESC',
        [$userid, $yesno->id],
        IGNORE_MULTIPLE
    );
    if ($existingattempt) {
        return $existingattempt;
    }

    // Get all secrets for this activity.
    $secrets = $DB->get_records('yesno_secrets', ['yesnoid' => $yesno->id], 'sortorder');

    if (empty($secrets)) {
        throw new Exception('No secrets found for this activity');
    }

    // Select a random secret.
    $selectedsecret = $secrets[array_rand($secrets)];

    // Create attempt record with the selected secret.
    $attempt = new stdClass();
    $attempt->yesnoid = $yesno->id;
    $attempt->userid = $userid;
    $attempt->secretid = $selectedsecret->id;
    $attempt->question_count = 0;
    $attempt->status = 'active';
    $attempt->score = 0;
    $attempt->timecreated = time();
    $attempt->timemodified = time();

    $attempt->id = $DB->insert_record('yesno_attempts', $attempt);

    // Log the attempt started event.
    $cm = get_coursemodule_from_instance('yesno', $yesno->id, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    $event = \mod_yesno\event\attempt_started::create([
        'objectid' => $attempt->id,
        'context' => $context,
        'other' => ['yesnoid' => $yesno->id],
    ]);
    $event->add_record_snapshot('yesno_attempts', $attempt);
    $event->trigger();

    return $attempt;
}

/**
 * Render the game completion UI with try another and finish options
 *
 * @param stdClass $yesno The yesno activity object
 * @param stdClass $userattempt The completed attempt record
 * @param context_module $modulecontext The module context
 * @return string HTML output of the completion UI
 * @package mod_yesno
 */
function yesno_render_game_completion(stdClass $yesno, stdClass $userattempt, context_module $modulecontext): string {
    global $OUTPUT, $DB;

    $iswon = ($userattempt->status === 'win');
    $islost = ($userattempt->status === 'loss');

    // Reveal the secret on loss if the setting is enabled.
    $revealsecret = '';
    if ($islost && !empty($yesno->show_answer_on_loss) && $userattempt->secretid) {
        $secret = $DB->get_field('yesno_secrets', 'secret', ['id' => $userattempt->secretid]);
        if ($secret) {
            $revealsecret = $secret;
        }
    }

    // Build URLs with sesskey for security.
    $tryanotherurl = new moodle_url(
        $modulecontext->get_url(),
        ['tryanother' => 1, 'sesskey' => sesskey()]
    );

    $finishurl = new moodle_url(
        $modulecontext->get_url(),
        ['finish' => 1, 'sesskey' => sesskey()]
    );

    $data = [
        'is_win' => $iswon,
        'is_loss' => $islost,
        'game_won_title' => $iswon ? get_string('gamewon', 'yesno') : '',
        'game_won_message' => $iswon ? 'You guessed correctly!' : '',
        'game_lost_title' => $islost ? get_string('gamelost', 'yesno') : '',
        'game_lost_message' => $islost ? get_string('maxquestionsreached', 'yesno') : '',
        'show_score' => true,
        'score_label' => get_string('finalscore', 'yesno'),
        'final_score' => $userattempt->score,
        'try_another_url' => $tryanotherurl->out(false),
        'try_another_text' => get_string('tryanotherattempt', 'yesno'),
        'finish_session_url' => $finishurl->out(false),
        'finish_session_text' => get_string('finishsession', 'yesno'),
        'reveal_secret' => $revealsecret,
        'reveal_secret_label' => get_string('revealsecretlabel', 'yesno'),
    ];

    return $OUTPUT->render_from_template('mod_yesno/game_completion', $data);
}

/**
 * Render the start attempt button using mustache template
 *
 * @param context_module $modulecontext
 * @return string HTML output of the start button
 * @package mod_yesno
 */
function yesno_render_start_attempt_button(context_module $modulecontext): string {
    global $OUTPUT;

    $starturl = new moodle_url(
        $modulecontext->get_url(),
        ['startattempt' => 1, 'sesskey' => sesskey()]
    );

    $data = [
        'start_button_text' => get_string('startattempt', 'yesno'),
        'start_button_url' => $starturl->out(false),
    ];

    return $OUTPUT->render_from_template('mod_yesno/start_attempt_button', $data);
}

/**
 * Ask the AI if the student is getting warmer (closer to the secret).
 *
 * Reads the last 3 question/response pairs from yesno_history for the
 * current attempt, builds a prompt that includes the secret, and asks
 * the AI to reply with "yes" or "no".
 *
 * @param stdClass $yesno The yesno activity object.
 * @param stdClass $userattempt The current attempt record.
 * @param context_module $modulecontext The module context.
 * @return string 'yes', 'no', or empty string on error.
 * @package mod_yesno
 */
function yesno_check_warm(stdClass $yesno, stdClass $userattempt, context_module $modulecontext): string {
    global $DB;
    // Need at least one history entry to evaluate.
    $historyrows = $DB->get_records(
        'yesno_history',
        ['attemptid' => $userattempt->id],
        'id DESC',
        '*',
        0,
        3
    );
    if (empty($historyrows)) {
        return '';
    }

    // Get the secret for this attempt.
    $secret = $DB->get_field('yesno_secrets', 'secret', ['id' => $userattempt->secretid]);
    if ($secret === false) {
        return '';
    }

    // Build Q&A summary (oldest first).
    $pairs = [];
    foreach (array_reverse($historyrows) as $row) {
        $pairs[] = 'Q: ' . $row->question . ' | A: ' . $row->response;
    }
    $combined = implode(' || ', $pairs);

    $prompt = 'The secret word or phrase is "' . $secret . '". ' .
        'The student\'s recent questions and responses are: ' . $combined . '. ' .
        'Is the student getting closer to discovering the secret? ' .
        'Reply with only "yes" or "no".';

    try {
        $backend = get_config('mod_yesno', 'backend') ?: 'tool_aiconnect';
        $aibridge = new \tool_ai_bridge\ai_bridge($modulecontext->id, $backend);
        $airesponse = $aibridge->perform_request($prompt, 'feedback');
        $airesponse = strip_tags($airesponse);
        $airesponse = strtolower(trim($airesponse));
        if (strpos($airesponse, 'yes') !== false) {
            return 'yes';
        }
        return 'no';
    } catch (Exception $e) {
        return '';
    }
}

/**
 * Render the abandon attempt button for students.
 *
 * @param context_module $modulecontext
 * @return string HTML output
 * @package mod_yesno
 */
function yesno_render_abandon_button(context_module $modulecontext): string {
    global $OUTPUT;

    $abandonurl = new moodle_url(
        $modulecontext->get_url(),
        ['abandon' => 1, 'sesskey' => sesskey()]
    );

    $confirmtext = get_string('abandonconfirm', 'yesno');

    $data = [
        'abandon_button_text' => get_string('abandonattempt', 'yesno'),
        'abandon_button_url' => $abandonurl->out(false),
        'abandon_confirm_text_json' => json_encode($confirmtext),
    ];

    return $OUTPUT->render_from_template('mod_yesno/abandon_button', $data);
}
