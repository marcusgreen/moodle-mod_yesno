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
 * Yes/No view page
 *
 * @package    mod_yesno
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/modinfolib.php');
require_once(__DIR__ . '/lib.php');

// Course module id
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id
$y = optional_param('y', 0, PARAM_INT);

if ($id) {
    list($course, $cm) = get_course_and_cm_from_cmid($id, 'yesno');
    $yesno = $DB->get_record('yesno', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($y) {
    $yesno = $DB->get_record('yesno', array('id' => $y), '*', MUST_EXIST);
    list($course, $cm) = get_course_and_cm_from_instance($y, 'yesno');
} else {
    throw new moodle_exception('missingparameter');
}

$modulecontext = context_module::instance($cm->id);

require_login($course, true, $cm);
$PAGE->set_url('/mod/yesno/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($yesno->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

// Output the module name and description
echo $OUTPUT->heading(format_string($yesno->name));

// Display the intro (description) if it exists
if (!empty($yesno->intro)) {
    echo $OUTPUT->box(format_module_intro('yesno', $yesno, $cm->id), 'generalbox', 'intro');
}

// Check if user can manage the activity
$canmanage = has_capability('mod/yesno:manage', $modulecontext);

// Display different content based on capability
if ($canmanage) {
    echo $OUTPUT->box(get_string('managemsg', 'yesno'), 'generalbox');
} else {
    echo $OUTPUT->box(get_string('viewmsg', 'yesno'), 'generalbox');
}

// Add some basic styling
echo html_writer::tag('div', 
    get_string('activitydescription', 'yesno'), 
    array('class' => 'yesno-description')
);

echo $OUTPUT->footer();