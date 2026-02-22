<?php
/**
 * PHPUnit tests for the yesno library functions.
 *
 * @package    mod_yesno
 * @category   test
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once(__DIR__.'/../lib.php');



/**
 * Test class for yesno library.
 */
class mod_yesno_lib_test extends advanced_testcase {

    /** @var stdClass The course record used for the test. */
    protected $course;

    /**
     * Setup common test data.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        // Create a dummy course.
        $this->course = $this->getDataGenerator()->create_course();
    }

    /**
     * Test that an instance can be added and then deleted successfully.
     */
    public function test_add_and_delete_instance(): void {
        // Build a minimal yesno object.
        $yesno = new stdClass();
        $yesno->name           = 'Test YesNo';
        $yesno->course         = $this->course->id;
        $yesno->max_grade      = 100;
        $yesno->max_questions  = 5;
        $yesno->max_characters = 250;
        $yesno->secret         = 'testsecret';
        $yesno->max_characters = 250;
        $yesno->max_grade      = 100;
        // Optional fields that the add_instance routine handles.
        $yesno->clue           = '';
        $yesno->system_prompt  = '';

        // Add the instance.
        $id = yesno_add_instance($yesno);
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        // Verify the record exists.
        global $DB;
        $record = $DB->get_record('yesno', ['id' => $id]);
        $this->assertNotEmpty($record);
        $this->assertEquals('Test YesNo', $record->name);

        // Delete the instance.
        $result = yesno_delete_instance($id);
        $this->assertTrue($result);

        // Ensure the record is gone.
        global $DB;
        $deleted = $DB->record_exists('yesno', ['id' => $id]);
        $this->assertFalse($deleted);
    }
}

