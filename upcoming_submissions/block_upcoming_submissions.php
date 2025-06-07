<?php
class block_upcoming_submissions extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_upcoming_submissions');
    }

    public function get_content() {
        global $USER, $DB, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';

        if (!isloggedin() || isguestuser()) {
            $this->content->text = get_string('notloggedin', 'block_upcoming_submissions');
            return $this->content;
        }

        $now = time();
        $userid = $USER->id;

        $courses = enrol_get_users_courses($userid, true);

        if (empty($courses)) {
            $this->content->text = get_string('nocourses', 'block_upcoming_submissions');
            return $this->content;
        }

        $activities = [];

        foreach ($courses as $course) {
            // Assignments
            $assignments = $DB->get_records_sql("
                SELECT a.id, a.name, a.duedate, c.id AS courseid, c.fullname
                FROM {assign} a
                JOIN {course} c ON c.id = a.course
                JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
                JOIN {role_assignments} ra ON ra.contextid = ctx.id
                WHERE ra.userid = ? AND a.duedate > 0
            ", [$userid]);

            // Quizzes
            $quizzes = $DB->get_records_sql("
                SELECT q.id, q.name, q.timeclose AS duedate, c.id AS courseid, c.fullname
                FROM {quiz} q
                JOIN {course} c ON c.id = q.course
                JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
                JOIN {role_assignments} ra ON ra.contextid = ctx.id
                WHERE ra.userid = ? AND q.timeclose > 0
            ", [$userid]);

            $activities = array_merge($activities, $assignments, $quizzes);
        }

        if (empty($activities)) {
            $this->content->text = get_string('noactivities', 'block_upcoming_submissions');
            return $this->content;
        }

        // Remove duplicate activities
        $unique_activities = [];
        foreach ($activities as $activity) {
            $key = $activity->id . '_' . $activity->courseid;
            if (!isset($unique_activities[$key])) {
                $unique_activities[$key] = $activity;
            }
        }
        $activities = array_values($unique_activities);

        // Sort activities by duedate
        usort($activities, function($a, $b) {
            return $a->duedate - $b->duedate;
        });

        // Start building HTML
        $html = html_writer::start_tag('div', ['class' => 'assignment-list']);

        $counter = 0;
        $htmlhidden = ''; // Hidden assignments ke liye
        foreach ($activities as $activity) {
            $duedate = userdate($activity->duedate);
            $daysleft = ceil(($activity->duedate - $now) / 86400);

            $linkurl = '';
            if (isset($activity->name)) {
                if ($DB->record_exists('assign', ['id' => $activity->id])) {
                    $cmid = $DB->get_field('course_modules', 'id', [
                        'instance' => $activity->id,
                        'module' => $DB->get_field('modules', 'id', ['name' => 'assign'])
                    ]);
                    $linkurl = new moodle_url('/mod/assign/view.php', ['id' => $cmid]);
                } elseif ($DB->record_exists('quiz', ['id' => $activity->id])) {
                    $cmid = $DB->get_field('course_modules', 'id', [
                        'instance' => $activity->id,
                        'module' => $DB->get_field('modules', 'id', ['name' => 'quiz'])
                    ]);
                    $linkurl = new moodle_url('/mod/quiz/view.php', ['id' => $cmid]);
                }
            }

            $priorityclass = '';
            if ($daysleft <= 2) {
                $priorityclass = 'priority-high';
            } elseif ($daysleft <= 5) {
                $priorityclass = 'priority-mid';
            } else {
                $priorityclass = 'priority-low';
            }

            $singleitem = html_writer::start_tag('div', ['class' => 'assignment-item']);
            $singleitem .= html_writer::tag('div', html_writer::tag('h5', $activity->name, ['class' => 'assignment-name']));
            $singleitem .= html_writer::tag('p', $activity->fullname, ['class' => 'assignment-course']);
            $singleitem .= html_writer::empty_tag('hr', [
                'style' => 'border: none; height: 1px; background: var(--light-borders, #F1F5F7); margin: 10px 0;'
            ]);
            
            $singleitem .= html_writer::start_tag('div', ['class' => 'assignment-due']);
            $singleitem .= html_writer::tag('span', 'Due Date: ' . $duedate);
            if (!empty($linkurl)) {
                $singleitem .= html_writer::link($linkurl, 'Submit Assignment', ['class' => 'submit-button']);
            }
            $singleitem .= html_writer::end_tag('div');
            $singleitem .= html_writer::end_tag('div');

            if ($counter < 2) {
                $html .= $singleitem;
            } else {
                $htmlhidden .= $singleitem;
            }
            $counter++;
        }

        // Agar 2 se jyada assignments hain
        if (!empty($htmlhidden)) {
            $html .= html_writer::start_tag('div', ['id' => 'hidden-assignments', 'style' => 'display:none;']);
            $html .= $htmlhidden;
            $html .= html_writer::end_tag('div');

            $html .= html_writer::link('#', 'View More Assignments', ['id' => 'view-more-btn', 'class' => 'view-more-btn']);
        }

        $html .= html_writer::end_tag('div'); // assignment-list close

        // Add simple JS
        $html .= "
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var viewMoreBtn = document.getElementById('view-more-btn');
                if (viewMoreBtn) {
                    viewMoreBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        var hiddenAssignments = document.getElementById('hidden-assignments');
                        if (hiddenAssignments) {
                            hiddenAssignments.style.display = 'block';
                            viewMoreBtn.style.display = 'none';
                        }
                    });
                }
            });
        </script>
        ";

        $this->content->text = $html;
        return $this->content;
    }

    public function applicable_formats() {
        return array(
            'all' => true,
            'site' => false
        );
    }

    public function instance_allow_multiple() {
        return true;
    }
}
?>
