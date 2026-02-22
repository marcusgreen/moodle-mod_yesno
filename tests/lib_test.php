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
    /**
     * Test that the gradebook is updated correctly for a yesno activity.
     */
    public function test_update_gradebook(): void {
        // Create a dummy yesno instance.
        $yesno = new stdClass();
        $yesno->name           = 'Gradebook Test';
        $yesno->course         = $this->course->id;
        $yesno->max_grade      = 100;
        $yesno->max_questions  = 5;
        $yesno->max_characters = 250;
        $yesno->secret         = 'grade-secret';
        $yesno->clue           = '';
        $yesno->system_prompt  = '';
        $cm = $this->getDataGenerator()->create_module('yesno', $yesno);
        if (property_exists($cm, 'instance')) {
            $yesnoid = $cm->instance;
        } else {
            // Fallback: retrieve the activity id directly from the DB.
            global $DB;
            $yesnoid = $DB->get_field('yesno', 'id', ['name' => $yesno->name, 'course' => $this->course->id]);
        }
        $yesnoid = (int)$yesnoid;
        $this->assertIsInt($yesnoid);

        // Retrieve the freshly inserted record.
        global $DB;
        $yesnorecord = $DB->get_record('yesno', ['id' => $yesnoid]);
        $this->assertNotEmpty($yesnorecord);

        // Create a dummy user.
        $user = $this->getDataGenerator()->create_user();

        // Update the gradebook with a specific score.
        $score = 85.5;
        $result = yesno_update_gradebook($yesnorecord, $user->id, $score);
        $this->assertTrue($result);

        // Verify the grade item exists.
        $gradeitem = $DB->get_record('grade_items', [
            'itemtype'    => 'mod',
            'itemmodule'  => 'yesno',
            'iteminstance'=> $yesnoid,
            'courseid'    => $yesnorecord->course,
        ]);
        $this->assertNotEmpty($gradeitem);

        // Verify the grade record for the user.
        $grade = $DB->get_record('grade_grades', [
            'itemid' => $gradeitem->id,
            'userid' => $user->id,
        ]);
        $this->assertNotEmpty($grade);
        $this->assertEquals($score, $grade->finalgrade);
    }
}

