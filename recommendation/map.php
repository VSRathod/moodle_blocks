<?php
require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

global $DB, $PAGE, $OUTPUT, $USER;

require_login();
// At the top of the file after require_login():
if ($message = optional_param('message', '', PARAM_TEXT)) {
    $type = optional_param('notify', 'success', PARAM_ALPHA);
    \core\notification::add($message, constant('\core\output\notification::NOTIFY_' . strtoupper($type)));
}
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_url(new moodle_url('/blocks/recommendation/map.php'));
$PAGE->set_context($context);
$PAGE->set_title('Course Recommendation Mapping');
$PAGE->set_heading('Course Recommendation Mapping');
$PAGE->requires->css(new moodle_url('/blocks/recommendation/css/select2.min.css'));
$PAGE->requires->css(new moodle_url('/blocks/recommendation/css/style.css'));
$PAGE->requires->js_call_amd('block_recommendation/custom', 'init');

// Fetch all courses (excluding site course)
$courses = $DB->get_records_sql("
    SELECT id, fullname 
    FROM {course} 
    WHERE id > 1 AND visible = 1
    ORDER BY fullname
");

// Prepare course list
$course_list = [];
foreach ($courses as $course) {
    $course_list[] = [
        'id' => $course->id,
        'name' => format_string($course->fullname)
    ];
}

// Fetch existing mappings
$mapped_courses = [];
$mappings = $DB->get_records('block_courserecommend_map');
$index = 0;

foreach ($mappings as $map) {
    $mapped_ids = array_filter(explode(',', $map->mapcourse));
    
    $mapped_courses[] = [
        'index' => $index,
        'courseid' => $map->courseid,
        'mapped_ids' => $mapped_ids
    ];
    $index++;
}

// Prepare template data
$template_data = [
    'config' => $CFG,
    'sesskey' => sesskey(),
    'all_courses' => $course_list,
    'mappings' => [],
    'mappings_count' => count($mapped_courses)
];

// Prepare existing mappings for template
foreach ($mapped_courses as $mapping) {
    $courses_with_flags = [];
    foreach ($course_list as $course) {
        $courses_with_flags[] = [
            'id' => $course['id'],
            'name' => $course['name'],
            'selected' => ($course['id'] == $mapping['courseid']),
            'mapped' => in_array($course['id'], $mapping['mapped_ids'])
        ];
    }
    
    $template_data['mappings'][] = [
        'index' => $mapping['index'],
        'all_courses' => $courses_with_flags
    ];
}

// If no mappings exist, add one empty row
if (empty($template_data['mappings'])) {
    $courses_with_flags = [];
    foreach ($course_list as $course) {
        $courses_with_flags[] = [
            'id' => $course['id'],
            'name' => $course['name'],
            'selected' => false,
            'mapped' => false
        ];
    }
    
    $template_data['mappings'][] = [
        'index' => 0,
        'all_courses' => $courses_with_flags
    ];
    $template_data['mappings_count'] = 1;
}

// Output the page
echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('block_recommendation');
echo $renderer->render_from_template('block_recommendation/map_form', $template_data);

echo $OUTPUT->footer();