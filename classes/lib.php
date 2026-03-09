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

namespace mod_yesno;

/**
 * Library class for yesno activity module business logic.
 *
 * @package    mod_yesno
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lib {
    /**
     * Select a random secret for a new attempt.
     *
     * @param int $yesnoid The yesno activity ID.
     * @return int|null The ID of the selected secret, or null if no secrets exist.
     */
    public static function select_random_secret(int $yesnoid): ?int {
        global $DB;

        $secrets = $DB->get_records('yesno_secrets', ['yesnoid' => $yesnoid]);
        if (empty($secrets)) {
            return null;
        }

        $secretkeys = array_keys($secrets);
        $randomkey = $secretkeys[array_rand($secretkeys)];
        return (int)$randomkey;
    }

    /**
     * Load attempt state for a user in a yesno activity.
     *
     * @param \stdClass $yesno The yesno activity object.
     * @param int $userid The user ID.
     * @return array Array with keys: userattempt, questioncount, score, gamefinished.
     */
    public static function load_attempt_state(\stdClass $yesno, int $userid): array {
        global $DB;

        // Get the most recent attempt for this user/activity.
        $userattempt = $DB->get_record_sql(
            'SELECT * FROM {yesno_attempts} WHERE userid = ? AND yesnoid = ? ORDER BY id DESC',
            [$userid, $yesno->id],
            IGNORE_MULTIPLE
        ) ?: null;

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

        return [
            'userattempt' => $userattempt,
            'questioncount' => $questioncount,
            'score' => $score,
            'gamefinished' => $gamefinished,
        ];
    }

    /**
     * Build the combined AI prompt from template and student question.
     *
     * @param stdClass $yesno The yesno activity object.
     * @param string $studentquestion The student's question.
     * @return string The combined prompt string.
     */
    public static function build_ai_prompt(\stdClass $yesno, string $studentquestion): string {
        // Use the first secret if available, or use multiple secrets formatted.
        if (!empty($yesno->secrets) && is_array($yesno->secrets)) {
            $targetword = implode(', ', $yesno->secrets);
        } else if (!empty($yesno->secret)) {
            $targetword = $yesno->secret;
        } else {
            $targetword = '';
        }

        $promptwithsecret = str_replace('{{target_word}}', $targetword, $yesno->system_prompt);
        $questionprefix = get_string('studentquestionprefix', 'yesno');
        return $promptwithsecret . "\n\n" . $questionprefix . ": " . $studentquestion;
    }

    /**
     * Save or update attempt record in the database.
     *
     * @param \stdClass $yesno The yesno activity object.
     * @param int $userid The user ID.
     * @param \stdClass|null $userattempt The current attempt record or null.
     * @param int $questioncount The question number.
     * @param string $studentquestion The student's question.
     * @param string $airesponse The AI response.
     * @param bool $iscorrect Whether the question was correct.
     * @return array Array with keys: userattempt, questioncount, score, gamefinished.
     */
    public static function save_attempt(
        \stdClass $yesno,
        int $userid,
        ?\stdClass $userattempt,
        int $questioncount,
        string $studentquestion,
        string $airesponse,
        bool $iscorrect
    ): array {
        global $DB;

        // Build attempt data.
        $attemptdata = new \stdClass();
        $attemptdata->userid = $userid;
        $attemptdata->yesnoid = $yesno->id;
        $attemptdata->question_count = $questioncount + 1;
        $attemptdata->timemodified = time();

        // Process the attempt and calculate the score.
        $currentquestion = $questioncount + 1;
        $processedattempt = yesno_process_attempt(
            $yesno,
            $studentquestion,
            $airesponse,
            $currentquestion,
            $iscorrect
        );
        $score = $processedattempt['score'];
        $attemptdata->status = $processedattempt['status'];
        $attemptdata->score = $score;

        // Save or update the attempt record.
        if ($userattempt) {
            $attemptdata->id = $userattempt->id;
            $DB->update_record('yesno_attempts', $attemptdata);
            $attemptid = $userattempt->id;
        } else {
            // For new attempts, select a random secret.
            $attemptdata->secretid = self::select_random_secret($yesno->id);
            $attemptid = $DB->insert_record('yesno_attempts', $attemptdata);
        }

        // Record this question/response in the history table.
        $historyrow = new \stdClass();
        $historyrow->attemptid = $attemptid;
        $historyrow->question = $studentquestion;
        $historyrow->response = $airesponse;
        $historyrow->timecreated = time();
        $historyid = $DB->insert_record('yesno_history', $historyrow);
        $historyrow->id = $historyid;

        // Get context for event logging.
        $cm = get_coursemodule_from_instance('yesno', $yesno->id, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        // Log the question submitted event.
        $event = \mod_yesno\event\question_submitted::create([
            'objectid' => $historyrow->id,
            'context' => $context,
            'other' => [
                'yesnoid' => $yesno->id,
                'attemptid' => $attemptid,
                'questionnumber' => $questioncount + 1,
            ],
        ]);
        $event->add_record_snapshot('yesno_history', $historyrow);
        $event->trigger();

        // Update Moodle gradebook if the game is finished.
        if ($attemptdata->status === 'win' || $attemptdata->status === 'loss') {
            yesno_update_gradebook($yesno, $userid, $score);

            // Log the attempt completed event.
            $completeevent = \mod_yesno\event\attempt_completed::create([
                'objectid' => $attemptid,
                'context' => $context,
                'other' => [
                    'yesnoid' => $yesno->id,
                    'status' => $attemptdata->status,
                    'score' => $score,
                ],
            ]);
            $completeevent->trigger();
        }

        // Refresh the attempt data by id.
        $newerattempt = $DB->get_record('yesno_attempts', ['id' => $attemptid]) ?: null;

        $newquestioncount = $newerattempt ? $newerattempt->question_count : 0;
        $newscore = $newerattempt && isset($newerattempt->score) ? $newerattempt->score : 0;
        $newgamefinished = $newerattempt && ($newerattempt->status === 'win' || $newerattempt->status === 'loss');

        return [
            'userattempt' => $newerattempt,
            'questioncount' => $newquestioncount,
            'score' => $newscore,
            'gamefinished' => $newgamefinished,
        ];
    }

    /**
     * Handle the form submission and process the student's question.
     *
     * @param \stdClass $yesno The yesno activity object.
     * @param \stdClass $modulecontext The module context.
     * @param \stdClass|null $userattempt The current attempt record.
     * @param int $questioncount The current question count.
     * @param bool $gamefinished Whether the game is finished.
     * @param string $studentquestion The student's submitted question.
     * @return array Array with keys: userattempt, questioncount, score, gamefinished.
     */
    public static function handle_submission(
        \stdClass $yesno,
        \stdClass $modulecontext,
        ?\stdClass $userattempt,
        int $questioncount,
        bool $gamefinished,
        string $studentquestion
    ): array {
        global $OUTPUT, $USER;

        // Check if user has remaining attempts and game is not finished.
        if ($questioncount >= $yesno->maxquestions || $gamefinished) {
            echo $OUTPUT->notification(get_string('maxquestionsreached', 'yesno'), 'warning');
            return [
                'userattempt' => $userattempt,
                'questioncount' => $questioncount,
                'score' => $userattempt ? (isset($userattempt->score) ? $userattempt->score : 0) : 0,
                'gamefinished' => $gamefinished,
                'airesponse' => '',
            ];
        }

        $airesponse = '';

        // Always use the AI bridge to evaluate the student's input.
        try {
            $combinedprompt = self::build_ai_prompt($yesno, $studentquestion);

            // Use the AI bridge to get response.
            $aibridge = new \tool_ai_bridge\ai_bridge($modulecontext->id);
            $airesponse = $aibridge->perform_request($combinedprompt, 'twentyquestions');
        } catch (Exception $e) {
            echo $OUTPUT->notification(
                get_string('errorgettingresponse', 'yesno') . ': ' . $e->getMessage(),
                'error'
            );
        }

        // If we have an AI response, save the attempt.
        if (!empty($airesponse)) {
            $result = self::save_attempt(
                $yesno,
                $USER->id,
                $userattempt,
                $questioncount,
                $studentquestion,
                $airesponse,
                false
            );
            $result['airesponse'] = $airesponse;
            return $result;
        }

        return [
            'userattempt' => $userattempt,
            'questioncount' => $questioncount,
            'score' => $userattempt ? (isset($userattempt->score) ? $userattempt->score : 0) : 0,
            'gamefinished' => $gamefinished,
            'airesponse' => '',
        ];
    }
}
