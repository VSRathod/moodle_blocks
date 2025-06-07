<?php
define('AJAX_SCRIPT', true);
require_once('../../config.php');

global $DB, $USER;

$filter = required_param('filter', PARAM_ALPHA);
$data = [];

switch ($filter) {
    case 'daily':
        $sql = "SELECT FROM_UNIXTIME(timecreated, '%Y-%m-%d') AS period, COUNT(id) AS count 
                FROM {logstore_standard_log} WHERE eventname LIKE '%user_loggedin%'
                GROUP BY period ORDER BY period DESC LIMIT 7";
        break;

    case 'weekly':
        $sql = "SELECT FROM_UNIXTIME(timecreated, '%Y-%u') AS period, COUNT(id) AS count 
                FROM {logstore_standard_log} WHERE eventname LIKE '%user_loggedin%'
                GROUP BY period ORDER BY period DESC LIMIT 4";
        break;

    case 'monthly':
        $sql = "SELECT FROM_UNIXTIME(timecreated, '%Y-%m') AS period, COUNT(id) AS count 
                FROM {logstore_standard_log} WHERE eventname LIKE '%user_loggedin%'
                GROUP BY period ORDER BY period DESC LIMIT 12";
        break;

    case 'userwise':
        $sql = "SELECT u.username AS period, COUNT(l.id) AS count 
                FROM {logstore_standard_log} l 
                JOIN {user} u ON u.id = l.userid
                WHERE l.eventname LIKE '%user_loggedin%'
                GROUP BY u.username ORDER BY count DESC LIMIT 10";
        break;

    default:
        echo json_encode(['error' => 'Invalid filter']);
        exit;
}

$results = $DB->get_records_sql($sql);
foreach ($results as $entry) {
    $data[] = ['label' => $entry->period, 'value' => $entry->count];
}

echo json_encode($data);
exit;
