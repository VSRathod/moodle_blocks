<?php
// blocks/learningtime/classes/output/renderer.php

namespace block_learningtime\output;

defined('MOODLE_INTERNAL') || die();

class renderer extends \plugin_renderer_base {

    /**
     * Render the main content of the block.
     *
     * @param main $main The main renderable.
     * @return string HTML string
     */
    public function render_main(main $main) {
        $data = $main->export_for_template($this);
        return $this->render_from_template('block_learningtime/main', $data);
    }
}