<?php
namespace block_recommendation\output;

use renderable;
use templatable;
use renderer_base;
use stdClass;

class map_form implements renderable, templatable {
    private $courses;
    private $mapped_courses;

    public function __construct($courses, $mapped_courses) {
        $this->courses = $courses;
        $this->mapped_courses = $mapped_courses;
    }

    public function export_for_template(renderer_base $output) {
        return [
            'courses' => array_values($this->courses),
            'mapped_courses' => array_values($this->mapped_courses)
        ];
    }
}
