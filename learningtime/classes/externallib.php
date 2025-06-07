<?php
namespace block_learningtime;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class external extends \external_api {

    public static function get_learning_time_data_parameters() {
        return new \external_function_parameters([
            'userid' => new \external_value(PARAM_INT, 'User ID', VALUE_REQUIRED),
            'range' => new \external_value(PARAM_TEXT, 'Time range filter', VALUE_REQUIRED)
        ]);
    }

    public static function get_learning_time_data($userid, $range) {
        global $DB, $USER;

        // Validate and sanitize parameters
        $params = self::validate_parameters(
            self::get_learning_time_data_parameters(),
            [
                'userid' => (int)$userid,  // Force integer
                'range' => (string)$range   // Force string
            ]
        );
        $userid = $params['userid'];
        $range = $params['range'];

        // Validate context and capabilities
        $context = \context_user::instance($userid);
        self::validate_context($context);
        

        // Calculate time range
        $now = time();
        $starttime = 0;
        $endtime = $now;

        switch ($range) {
            case 'today':
                $starttime = strtotime('today midnight');
                break;
            case 'last7days':
                $starttime = strtotime('-7 days', $now);
                break;
            case 'last30days':
                $starttime = strtotime('-30 days', $now);
                break;
            case 'last6months':
                $starttime = strtotime('-6 months', $now);
                break;
            case 'currentyear':
                $starttime = strtotime('first day of january this year');
                break;
            default:
                throw new \moodle_exception('invalidrange', 'block_learningtime');
        }

        return self::get_real_learning_time($userid, $starttime, $endtime);
    }

    private static function get_real_learning_time($userid, $starttime, $endtime) {
        global $DB;
        
        $params = [
            'userid' => $userid,
            'starttime' => $starttime,
            'endtime' => $endtime,
            'action' => 'viewed',
            'starttime1' => $starttime,
            'endtime1' => $endtime,
            'action1' => 'viewed',
            'userid2' => $userid  // Used in the subquery
        ];
    
        $sql = "SELECT 
                    FLOOR(l1.timecreated/86400) as day,
                    SUM(l1.timecreated - IFNULL((
                        SELECT MAX(l2.timecreated) 
                        FROM {logstore_standard_log} l2 
                        WHERE l2.userid = :userid2 
                        AND l2.courseid = l1.courseid
                        AND l2.timecreated < l1.timecreated
                        AND l2.timecreated BETWEEN :starttime AND :endtime
                        AND l2.action = :action
                    ), l1.timecreated)) as timespent
                FROM {logstore_standard_log} l1
                WHERE l1.userid = :userid
                AND l1.timecreated BETWEEN :starttime1 AND :endtime1
                AND l1.action = :action1
                GROUP BY FLOOR(l1.timecreated/86400)
                ORDER BY day";
    
        $rawdata = $DB->get_records_sql($sql, $params);
        
        // Process the results
        $days = [];
        $totaltime = 0;
        
        foreach ($rawdata as $day => $record) {
            $minutes = round($record->timespent / 60);
            $hours = round($minutes / 60, 2);
            $totaltime += $minutes;
            
            $days[] = [
                'date' => date('Y-m-d', $day * 86400),
                'label' => date('M j', $day * 86400),
                'minutes' => $minutes,
                'hours' => $hours
            ];
        }
        
        // Calculate averages
        $daycount = count($days);
        $average = $daycount > 0 ? round($totaltime / $daycount, 2) : 0;
        
        return [
            'days' => $days,
            'total' => [
                'minutes' => $totaltime,
                'hours' => round($totaltime / 60, 2)
            ],
            'average' => [
                'minutes' => $average,
                'hours' => round($average / 60, 2)
            ],
            'range' => [
                'start' => date('Y-m-d', $starttime),
                'end' => date('Y-m-d', $endtime)
            ]
        ];
    }

    public static function get_learning_time_data_returns() {
        return new \external_single_structure([
            'days' => new \external_multiple_structure(
                new \external_single_structure([
                    'date' => new \external_value(PARAM_TEXT, 'Date in YYYY-MM-DD format'),
                    'label' => new \external_value(PARAM_TEXT, 'Display label for the date'),
                    'minutes' => new \external_value(PARAM_FLOAT, 'Time spent in minutes'),
                    'hours' => new \external_value(PARAM_FLOAT, 'Time spent in hours')
                ])
            ),
            'total' => new \external_single_structure([
                'minutes' => new \external_value(PARAM_FLOAT, 'Total time spent in minutes'),
                'hours' => new \external_value(PARAM_FLOAT, 'Total time spent in hours')
            ]),
            'average' => new \external_single_structure([
                'minutes' => new \external_value(PARAM_FLOAT, 'Average time per day in minutes'),
                'hours' => new \external_value(PARAM_FLOAT, 'Average time per day in hours')
            ]),
            'range' => new \external_single_structure([
                'start' => new \external_value(PARAM_TEXT, 'Start date of the range'),
                'end' => new \external_value(PARAM_TEXT, 'End date of the range')
            ])
        ]);
    }
}