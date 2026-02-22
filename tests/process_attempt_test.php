<?php
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/../lib.php');

/**
 * PHPUnit tests for the yesno_process_attempt() function.
 *
 * @package    mod_yesno
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mod_yesno_process_attempt_test extends advanced_testcase {

    /**
     * Test that a correct guess on the first attempt awards the full max grade.
     */
    public function test_first_attempt_correct_gives_full_score(): void {
        // Build a minimal yesno activity object.
        $yesno = (object)[
            'max_grade'     => 100,
            'max_questions' => 20,
            'secret'        => 'banana',
        ];

        // Simulate a correct AI response on the first question.
        $studentquestion = 'Is it a fruit?';
        $airesponse      = 'Yes, it is banana';
        $currentquestion = 1; // first attempt
        $iscorrect       = false; // let the function detect it via AI response

        // Call the function under test.
        $result = yesno_process_attempt($yesno, $studentquestion, $airesponse,
                                        $currentquestion, $iscorrect);

        // Verify that the full score is awarded and the status is win.
        $this->assertEquals(100, $result['score'], 'Full score should be awarded on first correct guess.');
        $this->assertEquals('win', $result['status'], 'Status must be "win" for a correct first guess.');
    }
}
?>
