<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../lib.php');

/**
 * PHPUnit tests for the yesno_process_attempt() function.
 *
 * @package    mod_yesno
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

final class process_attempt_test extends advanced_testcase {
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
        $result = yesno_process_attempt(
            $yesno,
            $studentquestion,
            $airesponse,
            $currentquestion,
            $iscorrect
        );

        // Verify that the full score is awarded and the status is win.
        $this->assertEquals(100, $result['score'], 'Full score should be awarded on first correct guess.');
        $this->assertEquals('win', $result['status'], 'Status must be "win" for a correct first guess.');
    }
}
