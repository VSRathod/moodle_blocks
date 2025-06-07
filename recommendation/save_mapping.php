<?php
require_once('../../config.php');
global $DB, $USER;

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

// Get raw POST data and parse it
$raw_post = file_get_contents('php://input');
parse_str($raw_post, $post_data);

$courseids = $post_data['courseid'] ?? [];
$mapcourses = $post_data['mapcourse'] ?? [];

// Process the mappings
$processedMappings = [];
foreach ($courseids as $index => $courseid) {
    $courseid = (int)$courseid;
    $mapped = [];
    
    if (isset($mapcourses[$index]) && is_array($mapcourses[$index])) {
        foreach ($mapcourses[$index] as $mapcourse) {
            if (!empty($mapcourse)) {
                $mapped[] = (int)$mapcourse;
            }
        }
    }
    
    if (!empty($courseid) && !empty($mapped)) {
        $processedMappings[$courseid] = implode(',', array_unique($mapped));
    }
}

// Clear all existing mappings
$DB->delete_records('block_courserecommend_map');

// Save new mappings
foreach ($processedMappings as $courseid => $mappedCourses) {
    $new = new stdClass();
    $new->courseid = $courseid;
    $new->mapcourse = $mappedCourses;
    $new->mapdate = time();
    $new->mapby = $USER->id;
    $DB->insert_record('block_courserecommend_map', $new);
}

// After successful save:
redirect(new moodle_url('/blocks/recommendation/map.php', [
    'notify' => 'success',
    'message' => get_string('mappingsaved', 'block_recommendation')
]));