<?php 
require_once('../../config.php');
require_login();

$timePeriod = optional_param('time_period', 'current_week', PARAM_ALPHA);

// Fetch learning time spent data
$block = block_instance('student_overview');
$time_spent = $block->get_learning_time_spent($USER->id, $timePeriod);

// Prepare response
$data = [
    'labels' => array_keys($time_spent),
    'values' => array_values($time_spent),
];

echo json_encode($data);