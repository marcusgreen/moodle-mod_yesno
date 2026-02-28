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
     * Load attempt state for a user in a yesno activity.
     *
     * @param stdClass $yesno The yesno activity object.
     * @param int $userid The user ID.
     * @return array Array with keys: userattempt, questioncount, score, gamefinished.
     */
    public static function load_attempt_state(\stdClass $yesno, int $userid): array {
        global $DB;

        // Get current user's attempt record.
        $userattempt = $DB->get_record('yesno_attempts', [
            'userid' => $userid,
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

        return [
            'userattempt' => $userattempt,
            'questioncount' => $questioncount,
            'score' => $score,
            'gamefinished' => $gamefinished,
        ];
    }

    /**
     * Check if student's question is a direct match to the secret word.
     *
     * @param string $studentquestion The student's question.
     * @param string $secret The secret word.
     * @return string|null The AI response string if direct win detected, null otherwise.
     */
    public static function check_direct_win(string $studentquestion, string $secret): ?string {
        // If the student's question is the same as the secret word, they win.
        if (strcasecmp(trim($studentquestion), $secret) == 0) {
            return "Yes you have guessed the secret";
        }
        if (stripos(trim($studentquestion), $secret) !== false) {
            return "You have found the secret!";
        }
        return null;
    }

    /**
     * Build the combined AI prompt from template and student question.
     *
     * @param stdClass $yesno The yesno activity object.
     * @param string $studentquestion The student's question.
     * @return string The combined prompt string.
     */
    public static function build_ai_prompt(\stdClass $yesno, string $studentquestion): string {
        $promptwithsecret = str_replace('{{target_word}}', $yesno->secret, $yesno->system_prompt);
        $questionprefix = get_string('studentquestionprefix', 'yesno');
        return $promptwithsecret . "\n\n" . $questionprefix . ": " . $studentquestion;
    }

    /**
     * Save or update attempt record in the database.
     *
     * @param stdClass $yesno The yesno activity object.
     * @param int $userid The user ID.
     * @param stdClass|null $userattempt The current attempt record or null.
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
            yesno_update_gradebook($yesno, $userid, $score);
        }

        // Refresh the attempt data.
        $newerattempt = $DB->get_record('yesno_attempts', [
            'userid' => $userid,
            'yesnoid' => $yesno->id,
        ]);
        if (!$newerattempt) {
            $newerattempt = null;
        }

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
            ];
        }

        $airesponse = '';
        $iscorrect = false;

        // Check for direct win first.
        $directwin = self::check_direct_win($studentquestion, $yesno->secret);
        if ($directwin !== null) {
            $airesponse = $directwin;
            $iscorrect = true;
        } else {
            // Not a direct win, call AI for response.
            try {
                $combinedprompt = self::build_ai_prompt($yesno, $studentquestion);

                // Use the AI bridge to get response.
                require_once(__DIR__ . '/AiBridge.php');
                $aibridge = new \mod_yesno\AiBridge($modulecontext->id);
                $airesponse = $aibridge->perform_request($combinedprompt, 'twentyquestions');
            } catch (Exception $e) {
                echo $OUTPUT->notification(
                    get_string('errorgettingresponse', 'yesno') . ': ' . $e->getMessage(),
                    'error'
                );
            }
        }

        // If we have an AI response, save the attempt.
        if (!empty($airesponse)) {
            return self::save_attempt(
                $yesno,
                $USER->id,
                $userattempt,
                $questioncount,
                $studentquestion,
                $airesponse,
                $iscorrect
            );
        }

        return [
            'userattempt' => $userattempt,
            'questioncount' => $questioncount,
            'score' => $userattempt ? (isset($userattempt->score) ? $userattempt->score : 0) : 0,
            'gamefinished' => $gamefinished,
        ];
    }
}
