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
 * PHPUnit tests for the AiBridge::perform_request() method.
 *
 * @package    mod_yesno
 * @category   test
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_yesno\AiBridge
 */
final class aibridge_test extends \advanced_testcase {
    /** @var bool Whether a live LLM API connection is available for testing. */
    protected $islive;

    /** @var stdClass The course record used for the test. */
    protected $course;

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
     * Test that perform_request returns a string in a PHPUnit environment.
     *
     * @covers \mod_yesno\AiBridge::perform_request
     */
    public function test_perform_request_returns_string_in_test_env(): void {
        $bridge = new AiBridge(1);
        xdebug_break();
        $result = $bridge->perform_request('Is it a fruit?');
        $this->assertIsString($result, 'perform_request must return a string.');
    }

    /**
     * Test that perform_request returns a non-empty string response.
     *
     * @covers \mod_yesno\AiBridge::perform_request
     */
    public function test_perform_request_returns_stub_in_phpunit(): void {
        $bridge = new AiBridge(1);
        $result = $bridge->perform_request('Is it an animal?');
        $this->assertNotEmpty($result, 'Response must be a non-empty string.');
    }

    /**
     * Test that the purpose parameter is accepted without error.
     *
     * The default purpose is 'feedback'; verify an explicit value is also accepted.
     *
     * @covers \mod_yesno\AiBridge::perform_request
     */
    public function test_perform_request_accepts_custom_purpose(): void {
        $bridge = new AiBridge(1);
        $result = $bridge->perform_request('Is it a vegetable?', 'hint');
        $this->assertNotEmpty($result, 'Response must be a non-empty string regardless of purpose.');
    }

    /**
     * Test that perform_request works with different context IDs.
     *
     * @covers \mod_yesno\AiBridge::perform_request
     */
    public function test_perform_request_with_different_context_ids(): void {
        $contextids = [1, 42, 999];
        foreach ($contextids as $contextid) {
            $bridge = new AiBridge($contextid);
            $result = $bridge->perform_request('Is it bigger than a breadbox?');
            $this->assertNotEmpty(
                $result,
                "Response must be a non-empty string for context ID {$contextid}."
            );
        }
    }

    /**
     * Test that perform_request handles an empty prompt string.
     *
     * @covers \mod_yesno\AiBridge::perform_request
     */
    public function test_perform_request_with_empty_prompt(): void {
        $bridge = new AiBridge(1);
        $result = $bridge->perform_request('');
        $this->assertNotEmpty($result, 'Response must be a non-empty string even for an empty prompt.');
    }
}
