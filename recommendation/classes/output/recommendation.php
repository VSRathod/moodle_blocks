<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace block_recommendation\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use context_course;
use moodle_url;
use tool_courserating\api;
/**
 * Class containing data for course recommendation block.
 */
class recommendation implements renderable, templatable {

    /**
     * @var object An object containing the configuration information for the current instance of this block.
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param object $config Configuration object.
     */
    public function __construct($config) {
        $this->config = $config;
    }

    /**
     * Export this data for the mustache template.
     *
     * @param \renderer_base $output
     * @return \stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $CFG, $DB, $USER;

        $coursedata = new \stdClass();
        $coursedata->recommendations = [];
        $coursedata->hasrecommendations = false;
        $coursedata->categories = [];

        // Get course recommendations.
        $recommendations = $this->get_user_recommendations($USER->id);

        if (!empty($recommendations)) {
            $coursedata->hasrecommendations = true;

            foreach ($recommendations as $recommendation) {
                $course = $recommendation['recommended_course'];

                try {
                   
                    // Get course image.
                    $imgurl = $this->get_course_image($course->id);
                    $totalactivemodules = $DB->count_records('course_modules', array(
                        'course' => $course->id,
                        'deletioninprogress' => 0,
                        'visible' => 1
                    ));
                    $record = $DB->get_record_sql("
                        SELECT AVG(rating) AS averagerating, COUNT(*) AS numratings
                        FROM {tool_courserating_rating}
                        WHERE courseid = :courseid
                    ", ['courseid' => $course->id]);

                    $stars = [];
                    for ($i = 0; $i < 5; $i++) {
                        $stars[] = [
                            'filled' => ($i < round($record->averagerating)) ? true : false,
                        ];
                    }
                    // Prepare recommendation data
                    $coursedata->recommendations[] = [
                        'name' => format_string($course->fullname),
                        'url' => new moodle_url('/course/view.php', ['id' => $course->id]),
                        'image' => $imgurl,
                        'module' => $totalactivemodules,
                        'based_on' => $recommendation['based_on'],
                        'rating' => $rating,
                        'stars' => $stars
                    ];
                } catch (\Exception $e) {
                    continue; // If there's an error, just skip this recommendation
                }
            }
        }

        // Get visible course categories
        $categories = $DB->get_records('course_categories', ['visible' => 1], 'sortorder ASC');
        foreach ($categories as $category) {
            $coursedata->categories[] = [
                'id' => $category->id,
                'name' => format_string($category->name),
                'url' => new moodle_url('/course/index.php', ['categoryid' => $category->id])
            ];
        }

        return $coursedata;
    }

    /**
     * Get the course image from the course context.
     *
     * @param context_course $coursecontext
     * @return string URL of the course image.
     */
    protected function get_course_image($courseid) {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        // Default image if no image is found
        $context = context_course::instance($courseid);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0);

        foreach ($files as $f) {
            if ($f->is_valid_image()) {
                $url = moodle_url::make_pluginfile_url($f->get_contextid(), $f->get_component(), $f->get_filearea(), null,
                $f->get_filepath(), $f->get_filename(), false);
            }
        }

        return $url;
    }

    /**
     * Get recommended courses for a user, excluding already enrolled ones.
     *
     * @param int $userid
     * @return array
     */
    protected function get_user_recommendations($userid) {
        global $DB;

        // Enrolled courses.
        $enrolled_courses = $DB->get_records_sql("
            SELECT c.id
            FROM {course} c
            JOIN {enrol} e ON e.courseid = c.id
            JOIN {user_enrolments} ue ON ue.enrolid = e.id
            WHERE ue.userid = ? AND c.visible = 1
        ", [$userid]);

        $enrolled_course_ids = array_keys($enrolled_courses);

        if (empty($enrolled_course_ids)) {
            return []; // No enrolled courses.
        }

        // Get course mapping from custom table.
        $recommendations = $DB->get_records_sql("
            SELECT m.courseid, m.mapcourse, c.fullname
            FROM {block_courserecommend_map} m
            JOIN {course} c ON c.id = m.courseid
            WHERE m.courseid IN (" . implode(',', $enrolled_course_ids) . ")
        ");

        $result = [];

        foreach ($recommendations as $rec) {
            $mapped_course_ids = explode(',', $rec->mapcourse);

            foreach ($mapped_course_ids as $mapped_id) {
                if (in_array($mapped_id, $enrolled_course_ids)) {
                    continue; // Skip already enrolled courses.
                }

                $course = $DB->get_record('course', ['id' => $mapped_id, 'visible' => 1]);
                if ($course) {
                    $result[] = [
                        'recommended_course' => $course,
                        'based_on' => $rec->fullname
                    ];
                }
            }
        }

        return $result;
    }
}
