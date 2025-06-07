<?php
define('AJAX_SCRIPT', true); // Important for clean AJAX output
require_once('../../config.php');

global $DB;

require_login();
require_capability('moodle/site:config', context_system::instance());

$courseid = required_param('courseid', PARAM_INT);

// JSON header
header('Content-Type: application/json');

if ($DB->record_exists('block_courserecommend_map', ['courseid' => $courseid])) {
    $DB->delete_records('block_courserecommend_map', ['courseid' => $courseid]);
    echo json_encode(['success' => true, 'message' => 'Mapping deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Mapping not found']);
}
exit;
