<?php
class block_student_overview extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_student_overview');
    }

    public function get_content() {
        global $USER, $DB, $OUTPUT, $COURSE, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        // Fetch user info
        $user_name = fullname($USER);
        $course_count = $DB->count_records('user_enrolments', ['userid' => $USER->id]);
        $certification_count = rand(5, 20); // Placeholder for now
        $xp_points = rand(1000, 5000); // Placeholder for XP

        // Fetch enrolled courses
        $enrolled_courses = enrol_get_users_courses($USER->id, true);
        $course_data = [];
        foreach ($enrolled_courses as $course) {
            // Fetch the number of lessons (sections) in the course
            $lessons = $DB->count_records('course_sections', ['course' => $course->id]);
        
            // Fetch the number of assignments in the course
            $assignments = $DB->count_records('assign', ['course' => $course->id]);
        
            // Fetch the total duration of the course (example: sum of all activity durations)
            $duration = $DB->get_field_sql("
                SELECT SUM(completionexpected) 
                FROM {course_modules} 
                WHERE course = :courseid
            ", ['courseid' => $course->id]);
            $duration = $duration ? gmdate("H:i", $duration) : '50 min'; // Default duration if not set
        
            // Fetch the number of enrolled students in the course
            $coursepercentage = new \core_completion\progress();
            $progress = $coursepercentage->get_course_progress_percentage($course, $USER->id);
            // Add course data to the array
            
            if($progress >= 100){
                $smily = "😎";
            } elseif($progress >= 75 && $progress < 100){
                $smily = "😊";
            } elseif($progress >= 50 && $progress < 75){
                $smily = "🙂";
            } elseif($progress >= 20 && $progress < 50){
                $smily = "😐";
            } else{
                $smily ='<i class="fa-regular fa-face-smile"></i>';
            }
           
            $course_data[] = [
                'title' => $course->fullname,
                'lessons' => $lessons,
                'assignments' => $assignments,
                'duration' => $duration,
                'progress' => $progress,
                'url' => new moodle_url('/course/view.php?id='.$course->id),
                //'image' => theme_mb2nl_course_image_url($course->id) // Replace with actual image path or logic to fetch course image
                'image' => $smily // Replace with actual image path or logic to fetch course image
            ];
        }

        // Sample class categories
        $class_categories = [
            ['name' => 'All'],
            ['name' => 'Design'],
            ['name' => 'Science'],
            ['name' => 'Coding'],
            ['name' => 'Microbiology'],
            ['name' => 'Design Basics'],
            ['name' => 'Coding Bootcamp']
        ];

        // Features (Set Target, Consultation)
        $features = [
            ['title' => 'Set Target', 'description' => 'Set your study goals'],
            ['title' => 'Consultation', 'description' => 'Get mentor support']
        ];
       
        // Prepare data for Mustache
        $this->content = new stdClass();
        $userpicture = new user_picture($USER);
        $userpicture->size = 1; // Size f1.
        $profileimageurl = $userpicture->get_url($PAGE);
         // Fetch real data
         $attendance = $this->get_user_attendance($USER->id);
         $tasks = $this->get_user_tasks($USER->id);
         $time_spent = $this->get_total_timeSpent_by_user($USER->id);
     
         // Calculate percentages
         list($present, $total_attendance) = explode('/', $attendance);
         $attendance_percentage = ($total_attendance > 0) ? round(($present / $total_attendance) * 100, 2) : 0;
     
         list($completed, $total_tasks) = explode('/', $tasks);
         $tasks_percentage = ($total_tasks > 0) ? round(($completed / $total_tasks) * 100, 2) : 0;
     
         // Prepare data for Mustache template
      
        // Fetch learning time spent data
        $Charttime_spent = $this->get_learning_time_spent($USER->id);
        // Get overall progress
        $overallProgress = $this->get_overall_progress($USER->id);

        // Prepare data for the pie chart
        $chart = new \core\chart_pie();
        $chart->set_title('Overall Course Progress');

        $series = new \core\chart_series('Progress', [$overallProgress, 100 - $overallProgress]);
        $chart->add_series($series);

        $chart->set_labels(['Completed', 'Remaining']);

        $this->content->text = $OUTPUT->render_from_template('block_student_overview/student_overview', [
            'user_name' => fullname($USER),
            'user_background' => $OUTPUT->get_generated_image_for_id($COURSE->id),
            'user_photo' => $profileimageurl,//$OUTPUT->user_picture($USER, array('class' => 'userpicture')),
            'course_count' => $course_count,
            'certification_count' => $certification_count,
            'xp_points' => $xp_points,
            'courses' => $course_data,
            'class_categories' => $class_categories,
            'features' => $features,
            'attendance' => $attendance,
            'attendance_percentage' => $attendance_percentage,
            'tasks' => $tasks,
            'tasks_percentage' => $tasks_percentage,
            'time_spent' => $time_spent,
            'charttimespent' => $Charttime_spent,
           'overallProgress' => [
                'labels' => json_encode(['Completed', 'Remaining']),
                'data' => json_encode([$chart->get_series()[0]->get_values()[0], $chart->get_series()[0]->get_values()[1]]),
            ],
        ]);

        return $this->content;
    }

    private function get_user_attendance($userid) {
        global $DB;
    
        // Get all 'Present' status IDs
        $presentstatusids = $DB->get_fieldset_sql("
            SELECT id FROM {attendance_statuses} WHERE acronym = 'P'
        ");
    
        if (empty($presentstatusids)) {
            return "Error: No 'Present' statuses found";
        }
    
        // Count how many times the student was marked Present
        list($sqlin, $params) = $DB->get_in_or_equal($presentstatusids, SQL_PARAMS_NAMED);
        $params['userid'] = $userid;
    
        $present = $DB->count_records_sql("
            SELECT COUNT(*) FROM {attendance_log} 
            WHERE studentid = :userid AND statusid $sqlin
        ", $params);
    
        // Count total times the student was marked (Present, Absent, Late, etc.)
        $totalmarked = $DB->count_records('attendance_log', ['studentid' => $userid]);
    
        return "$present/$totalmarked";
    }
    
    
    

    private function get_user_tasks($userid) {
        global $DB;
    
        // Get user's enrolled courses
        $usercourses = $DB->get_fieldset_sql("
            SELECT DISTINCT e.courseid 
            FROM {user_enrolments} ue
            JOIN {enrol} e ON ue.enrolid = e.id
            WHERE ue.userid = :userid
        ", ['userid' => $userid]);
    
        if (empty($usercourses)) {
            return "0/0"; // User is not enrolled in any courses
        }
    
        // Get total tasks only from user's enrolled courses (with completion tracking enabled)
        list($sqlin, $params) = $DB->get_in_or_equal($usercourses, SQL_PARAMS_NAMED);
        $params['userid'] = $userid;
    
        $total = $DB->count_records_sql("
            SELECT COUNT(*) FROM {course_modules} cm
            JOIN {course} c ON cm.course = c.id
            WHERE cm.course $sqlin AND cm.completion > 0
        ", $params);
    
        $completed = $DB->count_records_sql("
            SELECT COUNT(*) FROM {course_modules_completion} cmc
            JOIN {course_modules} cm ON cmc.coursemoduleid = cm.id
            WHERE cmc.userid = :userid AND cm.course $sqlin AND cmc.completionstate = 1
        ", $params);
    
        return "$completed/$total";
    }

    private function get_total_timeSpent_by_user($userid) {
        global $DB;
    
        $sql = "
            SELECT 
                SUM(time_diff) AS total_time_spent
            FROM (
                SELECT
                    timecreated - LAG(timecreated) OVER (PARTITION BY userid, courseid ORDER BY timecreated) AS time_diff
                FROM
                    {logstore_standard_log}
                WHERE
                    userid = :userid
                    AND courseid IS NOT NULL
                    AND component LIKE 'mod_%'  -- Only count module interactions (assignments, quizzes, forums, etc.)
            ) AS subquery
            WHERE 
                time_diff IS NOT NULL
                AND time_diff < 1800  -- Ignore idle periods longer than 30 minutes
        ";
    
        $params = ['userid' => $userid];
        $record = $DB->get_record_sql($sql, $params);
    
        // Get total time spent in seconds
        $totalseconds = $record->total_time_spent ?? 0;
    
        // Convert time to hours and minutes
        $hours = floor($totalseconds / 3600);
        $minutes = round(($totalseconds % 3600) / 60);
    
        // Format output
        if ($hours > 0 && $minutes > 0) {
            $timespent = "{$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            $timespent = "{$hours}h";
        } else {
            $timespent = "{$minutes}m";
        }
    
        return $timespent ?: "0m"; // If still 0, show "0m"
    }

    // block_student_overview.php

    public function get_learning_time_spent($userid, $timePeriod = 'current_week') {
        global $DB;
       
        switch ($timePeriod) {
            case 'currentweek':
                return $this->get_time_spent_current_week($userid);
            case 'lastweek':
                return $this->get_time_spent_last_week($userid);
            case 'currentmonth':
                return $this->get_time_spent_current_month($userid);
            case 'lastmonth':
                return $this->get_time_spent_last_month($userid);
            case 'currentyear':
                return $this->get_time_spent_current_year($userid);
            default:
                return [];
        }
    }
    
    private function get_time_spent_current_week($userid) {
        global $DB;
    
        $startOfWeek = strtotime('Monday this week');
        $endOfWeek = strtotime('Sunday this week');
    
        // Debug: Print timestamps
       // echo "Start of Week: " . date('Y-m-d H:i:s', $startOfWeek) . "\n";
       // echo "End of Week: " . date('Y-m-d H:i:s', $endOfWeek) . "\n";
    
        $sql = "
            SELECT 
                day_of_week,
                SUM(time_spent) AS timespent
            FROM (
                SELECT 
                    WEEKDAY(FROM_UNIXTIME(timecreated)) AS day_of_week,
                    LEAD(timecreated) OVER (PARTITION BY userid, courseid ORDER BY timecreated) - timecreated AS time_spent
                FROM {logstore_standard_log}
                WHERE userid = :userid
                 AND courseid IS NOT NULL
                 AND component LIKE 'mod_%'
                  AND timecreated BETWEEN :start AND :end
            ) AS subquery
            WHERE time_spent IS NOT NULL 
                AND time_spent < 1800
            GROUP BY day_of_week
        ";
    
        $params = [
            'userid' => $userid,
            'start' => $startOfWeek,
            'end' => $endOfWeek,
        ];
    
        // Debug: Print SQL query and parameters
       // echo "SQL Query: $sql\n";
       // echo "Params: " . print_r($params, true) . "\n";
    
        $records = $DB->get_records_sql($sql, $params);
    
        // Debug: Print query results
        //echo "Query Results: " . print_r($records, true) . "\n";
    
        // Map day_of_week (0 = Monday, 6 = Sunday) to "Day 1" to "Day 7"
        $data = [];
        for ($i = 0; $i < 7; $i++) {
            $data["Day " . ($i + 1)] = 0; // Initialize all days
        }
        foreach ($records as $record) {
            $data["Day " . ($record->day_of_week + 1)] = ($record->timespent / 3600); // Convert seconds to hours
        }
    
        // Debug: Print final data
     
        return $data;
    }
    
    private function get_time_spent_last_week($userid) {
        global $DB;
    
        $startOfLastWeek = strtotime('Monday last week');
        $endOfLastWeek = strtotime('Sunday last week');
    
        $sql = "
            SELECT 
                day,
                SUM(time_spent) AS timespent
            FROM (
                SELECT 
                    DAY(FROM_UNIXTIME(timecreated)) AS day,
                    LEAD(timecreated) OVER (PARTITION BY userid, courseid ORDER BY timecreated) - timecreated AS time_spent
                FROM {logstore_standard_log}
                WHERE userid = :userid
                  AND courseid IS NOT NULL
                 AND component LIKE 'mod_%'
                  AND timecreated BETWEEN :start AND :end
            ) AS subquery
            WHERE time_spent IS NOT NULL   AND time_spent < 1800
            GROUP BY day
        ";
    
        $params = [
            'userid' => $userid,
            'start' => $startOfLastWeek,
            'end' => $endOfLastWeek,
        ];
    
        $records = $DB->get_records_sql($sql, $params);
    
        $data = [];
        for ($i = 1; $i <= 7; $i++) {
            $data["Day $i"] = 0; // Initialize all days
        }
        foreach ($records as $record) {
            $data["Day $record->day"] = ($record->timespent / 3600); // Convert seconds to hours
        }
    
        return $data;
    }
    
    private function get_time_spent_current_month($userid) {
        global $DB;
    
        $startOfMonth = strtotime('first day of this month');
        $endOfMonth = strtotime('last day of this month');
    
        $sql = "
            SELECT 
                week,
                SUM(time_spent) AS timespent
            FROM (
                SELECT 
                    WEEK(FROM_UNIXTIME(timecreated), 1) AS week,
                    LEAD(timecreated) OVER (PARTITION BY userid, courseid ORDER BY timecreated) - timecreated AS time_spent
                FROM {logstore_standard_log}
                WHERE userid = :userid
                   AND courseid IS NOT NULL
                 AND component LIKE 'mod_%'
                  AND timecreated BETWEEN :start AND :end
            ) AS subquery
            WHERE time_spent IS NOT NULL   AND time_spent < 1800
            GROUP BY week
        ";
    
        $params = [
            'userid' => $userid,
            'start' => $startOfMonth,
            'end' => $endOfMonth,
        ];
    
        $records = $DB->get_records_sql($sql, $params);
    
        $data = [];
        foreach ($records as $record) {
            $data["Week $record->week"] = ($record->timespent / 3600); // Convert seconds to hours
        }
    
        return $data;
    }
    
    private function get_time_spent_current_year($userid) {
        global $DB;
    
        $startOfYear = strtotime('first day of January this year');
        $endOfYear = strtotime('last day of December this year');
    
        $sql = "
            SELECT 
                month,
                SUM(time_spent) AS timespent
            FROM (
                SELECT 
                    MONTH(FROM_UNIXTIME(timecreated)) AS month,
                    LEAD(timecreated) OVER (PARTITION BY userid, courseid ORDER BY timecreated) - timecreated AS time_spent
                FROM {logstore_standard_log}
                WHERE userid = :userid
                  AND courseid IS NOT NULL
                 AND component LIKE 'mod_%'
                  AND timecreated BETWEEN :start AND :end
            ) AS subquery
            WHERE time_spent IS NOT NULL AND time_spent < 1800
            GROUP BY month
        ";
    
        $params = [
            'userid' => $userid,
            'start' => $startOfYear,
            'end' => $endOfYear,
        ];
    
        $records = $DB->get_records_sql($sql, $params);
    
        $data = [];
        foreach ($records as $record) {
            $data[date('F', mktime(0, 0, 0, $record->month, 10))] =$record->timespent / 3600; // Convert seconds to hours
        }
    
        return $data;
    }

    private function get_time_spent_last_month($userid) {
        global $DB;
    
        $startOfLastMonth = strtotime('first day of last month');
        $endOfLastMonth = strtotime('last day of last month');
    
        $sql = "
            SELECT 
                week,
                SUM(time_spent) AS timespent
            FROM (
                SELECT 
                    WEEK(FROM_UNIXTIME(timecreated), 1) - WEEK(FROM_UNIXTIME(:start1), 1) + 1 AS week,
                    LEAD(timecreated) OVER (PARTITION BY userid, courseid ORDER BY timecreated) - timecreated AS time_spent
                FROM {logstore_standard_log}
                WHERE userid = :userid
                  AND courseid IS NOT NULL
                 AND component LIKE 'mod_%'
                  AND timecreated BETWEEN :start AND :end
            ) AS subquery
            WHERE time_spent IS NOT NULL AND time_spent < 1800
            GROUP BY week
        ";
    
        $params = [
            'userid' => $userid,
            'start1' => $startOfLastMonth,
            'start' => $startOfLastMonth,
            'end' => $endOfLastMonth,
        ];
    
        $records = $DB->get_records_sql($sql, $params);
    
        $data = [];
        $totalWeeks = ceil((date('t', $startOfLastMonth) + date('w', $startOfLastMonth)) / 7); // Calculate total weeks in the month
        for ($i = 1; $i <= $totalWeeks; $i++) {
            $data["Week $i"] = 0; // Initialize all weeks
        }
        foreach ($records as $record) {
            $data["Week $record->week"] = ($record->timespent / 3600); // Convert seconds to hours
        }
    
        return $data;
    }

    private function get_time_spent_by_week($userid) {
        global $DB;
    
        $sql = "
            SELECT 
                YEAR(FROM_UNIXTIME(timecreated)) AS year,
                WEEK(FROM_UNIXTIME(timecreated)) AS week,
                SUM(time_spent) AS timespent
            FROM (
                SELECT 
                    timecreated,
                    LEAD(timecreated) OVER (PARTITION BY userid, courseid ORDER BY timecreated) - timecreated AS time_spent
                FROM {logstore_standard_log}
                WHERE userid = :userid
                  AND courseid IS NOT NULL
                 AND component LIKE 'mod_%'
            ) AS subquery
            WHERE time_spent IS NOT NULL AND time_spent < 1800
            GROUP BY year, week
        ";
    
        $params = ['userid' => $userid];
        $records = $DB->get_records_sql($sql, $params);
    
        $weekly_data = [];
        foreach ($records as $record) {
            $weekly_data["Week {$record->week}"] = ($record->timespent / 3600); // Convert seconds to hours
        }
    
        return $weekly_data;
    }
    
    private function get_time_spent_by_month($userid) {
        global $DB;
    
        $sql = "
            SELECT 
                YEAR(FROM_UNIXTIME(timecreated)) AS year,
                MONTH(FROM_UNIXTIME(timecreated)) AS month,
                SUM(time_spent) AS timespent
            FROM (
                SELECT 
                    timecreated,
                    LEAD(timecreated) OVER (PARTITION BY userid, courseid ORDER BY timecreated) - timecreated AS time_spent
                FROM {logstore_standard_log}
                WHERE userid = :userid
                  AND courseid IS NOT NULL
                 AND component LIKE 'mod_%'
            ) AS subquery
            WHERE time_spent IS NOT NULL  AND time_spent < 1800
            GROUP BY year, month
        ";
    
        $params = ['userid' => $userid];
        $records = $DB->get_records_sql($sql, $params);
    
        $monthly_data = [];
        foreach ($records as $record) {
            $monthly_data[date('F', mktime(0, 0, 0, $record->month, 10))] = round($record->timespent / 3600); // Convert seconds to hours
        }
    
        return $monthly_data;
    }

    private function get_time_spent_by_course($records) {
        $course_wise_data = [];
       
        if(!empty($records)){
            foreach ($records as $record) {
                if($record->courseid > 0){
                    $course = get_course($record->courseid);
                    $course_wise_data[$course->fullname] = $record->timespent / 3600; // Convert seconds to hours
                }
            }
        }
        return $course_wise_data;
    }

     /**
     * Get the overall course progress of a user in Moodle.
     *
     * @param int $userid The user ID.
     * @return float Overall course progress in percentage.
     */
    function get_overall_course_progress($userid) {
        global $DB;
    
        // Get all courses where the user is enrolled
        $enrolled_courses = enrol_get_users_courses($userid, true, 'id, fullname');
    
        if (empty($enrolled_courses)) {
            return 0; // No enrolled courses, return 0% progress
        }
    
        $total_progress = 0;
        $course_count = count($enrolled_courses);
        $coursepercentage = new \core_completion\progress();
    
        foreach ($enrolled_courses as $course) {
            $completion = new \completion_info($course);
            $progress = $coursepercentage->get_course_progress_percentage($course, $userid); // Returns value between 0 and 1
    
            if ($progress !== null) {
                $total_progress += $progress; // Keep it as a fraction
            }
        }
    
        // Convert to percentage and return
        return round(($total_progress / $course_count) * 1, 2);
    }

    private function get_course_progress($userid) {
        global $DB;

        // Get all courses the user is enrolled in
        $courses = enrol_get_all_users_courses($userid);

        $progressData = [];

        foreach ($courses as $course) {
            $completion = new completion_info($course);
            if ($completion->is_enabled()) {
                $percentage = \core_completion\progress::get_course_progress_percentage($course, $userid);
                if ($percentage !== null) {
                    $progressData[$course->id] = [
                        'course_name' => $course->fullname,
                        'progress' => $percentage,
                    ];
                }
            }
        }

        return $progressData;
    }

    private function get_overall_progress($userid) {
        $progressData = $this->get_course_progress($userid);

        if (empty($progressData)) {
            return 0; // No progress data available
        }

        $totalProgress = 0;
        $totalCourses = count($progressData);

        foreach ($progressData as $course) {
            $totalProgress += $course['progress'];
        }

        return $totalProgress / $totalCourses; // Average progress
    }
}


