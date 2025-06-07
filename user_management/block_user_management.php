<?php
class block_user_management extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_user_management');
    }

    public function get_content() {
        global $OUTPUT, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->page->requires->css(new moodle_url('/blocks/user_management/styles.css'));

        $this->content = new stdClass();

        // Capture search input
        $search = optional_param('searchuser', '', PARAM_TEXT);

        $renderer = $PAGE->get_renderer('block_user_management');

        // Render search form and user table (filtered)
        $this->content->text = $renderer->render_search_form($search);
        $this->content->text .= $renderer->render_user_table($search);

        return $this->content;
    }
}
