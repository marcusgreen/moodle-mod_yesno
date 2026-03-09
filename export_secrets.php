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
 * Export secrets/clues for a yesno instance as a JSON download.
 *
 * @package    mod_yesno
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$id = required_param('id', PARAM_INT);

$yesno = $DB->get_record('yesno', ['id' => $id], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $yesno->course], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('yesno', $id, $course->id, false, MUST_EXIST);

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);
require_capability('mod/yesno:addinstance', $modulecontext);

$secretrows = $DB->get_records('yesno_secrets', ['yesnoid' => $id], 'sortorder');

$data = [];
foreach ($secretrows as $row) {
    $data[] = [
        'secret' => $row->secret,
        'clue'   => $row->clue ?? '',
    ];
}

$filename = clean_filename($yesno->name) . '_secrets.json';

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
