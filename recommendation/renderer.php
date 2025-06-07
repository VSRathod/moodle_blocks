<?php
namespace block_recommendation\output;

defined('MOODLE_INTERNAL') || die();

class renderer extends \plugin_renderer_base {
    public function render_map_form(map_form $formdata) {
        return $this->render_from_template('block_recommendation/map_form', $formdata->export_for_template($this));
    }
}
