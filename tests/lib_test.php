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

/**
 * Unit tests for mod_yesno library class.
 *
 * @package    mod_yesno
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_yesno;

use advanced_testcase;
use stdClass;

/**
 * Unit tests for mod_yesno library class.
 *
 * @package    mod_yesno
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_yesno\lib::handle_submission
 */
final class lib_test extends advanced_testcase {
    /**
     * @var stdClass Course object used in tests.
     */
    protected $course;

    /**
     * @var bool Whether the test is using live LLM API.
     */
    protected $islive;
    /**
     * Config.php should include the apikey and orgid in the form
     * define("TEST_LLM_APIKEY", "XXXXXXXXXXXX");
     * define("TEST_LLM_ORGID", "XXXXXXXXXXXX");
     * Summary of setUp
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        if (defined('TEST_LLM_APIKEY') && defined('TEST_LLM_ORGID')) {
            // Tell AiBridge which backend to use.
            set_config('backend', 'core_ai_subsystem', 'qtype_aitext');
            // Register the provider in the ai_providers table via the manager.
            $manager = \core\di::get(\core_ai\manager::class);
            $manager->create_provider_instance(
                classname: '\aiprovider_openai\provider',
                name: 'test_openai',
                enabled: true,
                config: [
                    'apikey' => TEST_LLM_APIKEY,
                    'orgid'  => TEST_LLM_ORGID,
                ],
                actionconfig: [
                    \core_ai\aiactions\generate_text::class => [
                        'enabled'  => true,
                        'settings' => [
                            'model'             => 'gpt-4o',
                            'endpoint'          => 'https://api.openai.com/v1/chat/completions',
                            'systeminstruction' => \core_ai\aiactions\generate_text::get_system_instruction(),
                        ],
                    ],
                ],
            );
            $this->islive = true;
        }
        // Create a dummy course.
        $this->course = $this->getDataGenerator()->create_course();
    }
    /**
     * Test handle_submission with a correct first guess.
     *
     * @return void
     */
    public function test_handle_submission_correct_first_guess(): void {
        $this->resetAfterTest(true);

        // Create a course and user.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        xdebug_break();
        // Create a yesno activity with a known secret.
        $secret = 'elephant';
        $yesno = $this->getDataGenerator()->create_module('yesno', [
            'course' => $course->id,
            'name' => 'Test Yesno Activity',
            'secret' => $secret,
            'maxquestions' => 10,
        ]);
        // Get module context.
        $cm = get_coursemodule_from_instance('yesno', $yesno->id);
        $context = \context_module::instance($cm->id);
         // Load the attempt state to verify it was saved correctly.
        $attemptstate = lib::load_attempt_state($yesno, $user->id);
        $userattempt = $attemptstate['userattempt'];

        // Initial state: no attempt, no questions asked.

        $studentquestion = "Is it a dog";
        $result = lib::handle_submission(
            $yesno,
            $context,
            $userattempt,
            0,
            false,
            $studentquestion
        );

        $this->assertEquals(0, $result['score'], 'Score should be  0');

        $attemptstate = lib::load_attempt_state($yesno, $user->id);
        $userattempt = $attemptstate['userattempt'];
        // Initial state: no attempt, no questions asked.
        $studentquestion = "Is it an elephant";
        xdebug_break();

        $result = lib::handle_submission(
            $yesno,
            $context,
            $userattempt,
            0,
            false,
            $studentquestion
        );


    }
}
