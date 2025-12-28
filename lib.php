<?php

defined('MOODLE_INTERNAL') || die();


/**
 * Update a yesno instance
 *
 * @param object $data
 * @param object $mform
 * @return bool true if successful
 */
function yesno_update_instance($data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;
    
    // Update the record
    $result = $DB->update_record('yesno', $data);
    
    return $result;
}

/**
 * Delete a yesno instance
 *
 * @param int $id
 * @return bool true if successful
 */
function yesno_delete_instance($id) {
    global $DB;

    // Delete the record
    $DB->delete_records('yesno', array('id' => $id));
    
    return true;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $yesno
 * @return int
 */
function yesno_add_instance($yesno) {
    global $DB;
    
    $yesno->timecreated = time();
    
    return $DB->insert_record('yesno', $yesno);
}


