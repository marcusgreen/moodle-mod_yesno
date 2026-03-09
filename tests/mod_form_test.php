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
 * Unit tests for mod_yesno form data preprocessing.
 *
 * @package    mod_yesno
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_yesno;

use advanced_testcase;

/**
 * Unit tests for mod_form data_preprocessing function.
 *
 * @package    mod_yesno
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_yesno_mod_form::data_preprocessing
 */
final class mod_form_test extends advanced_testcase {
    /**
     * Test that data_preprocessing loads all secrets and clues correctly.
     *
     * @covers \mod_yesno_mod_form::data_preprocessing
     */
    public function test_data_preprocessing_loads_secrets_and_clues(): void {
        global $DB, $CFG;
        $this->resetAfterTest(true);

        // Load mod_form class.
        require_once($CFG->dirroot . '/mod/yesno/mod_form.php');

        // Create a course and yesno instance.
        $course = $this->getDataGenerator()->create_course();
        $yesno = $this->getDataGenerator()->create_module('yesno', [
            'course' => $course->id,
            'name' => 'Test Activity',
        ]);

        // Insert additional secrets and clues (module generator creates a default secret).
        $newsecretsdata = [
            ['secret' => 'dog', 'clue' => 'A barking animal', 'sortorder' => 1],
            ['secret' => 'cat', 'clue' => 'A meowing animal', 'sortorder' => 2],
            ['secret' => 'bird', 'clue' => 'A flying animal', 'sortorder' => 3],
        ];

        foreach ($newsecretsdata as $data) {
            $data['yesnoid'] = $yesno->id;
            $DB->insert_record('yesno_secrets', (object) $data);
        }

        // Create form instance using reflection to avoid constructor issues.
        $form = $this->getMockBuilder(\mod_yesno_mod_form::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        // Call data_preprocessing on the form.
        $defaultvalues = ['instance' => $yesno->id];
        $form->data_preprocessing($defaultvalues);

        // Verify all secrets are loaded (includes default + 3 new ones).
        $this->assertCount(4, $defaultvalues['secret']);
        $this->assertEquals('dog', $defaultvalues['secret'][1]);
        $this->assertEquals('cat', $defaultvalues['secret'][2]);
        $this->assertEquals('bird', $defaultvalues['secret'][3]);

        // Verify all clues are loaded with correct format.
        $this->assertCount(4, $defaultvalues['clue']);
        $this->assertEquals('A barking animal', $defaultvalues['clue'][1]['text']);
        $this->assertEquals(FORMAT_HTML, $defaultvalues['clue'][1]['format']);
        $this->assertEquals('A meowing animal', $defaultvalues['clue'][2]['text']);
        $this->assertEquals('A flying animal', $defaultvalues['clue'][3]['text']);
    }
}
