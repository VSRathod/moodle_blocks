<?php
class block_recommendation extends block_base {
	
    public function init() {
        $this->title = get_string('pluginname', 'block_recommendation');
    }

    function specialization() {
        if (!empty($this->config->title)) {
            $this->title = $this->title = format_string($this->config->title, true, ['context' => $this->context]);
        } else {
            $this->title = get_string('recommendation', 'block_recommendation');
        }
    }
	
	public function get_content() {
		
        global $PAGE,$DB, $CFG;

        if ($this->content !== NULL) {
            return $this->content;
        }

        if (!isloggedin() or isguestuser()) {
            // Only real users can access recommendation block.
            return;
        }
        $customdata = $this->config;
        $this->config  = new stdClass();
        $this->config->blockid = $this->instance->id;
     //   $this->config->most_enrolled = $customdata->most_enrolled;
      //  $this->config->top_rated = $customdata->top_rated;
        //$this->config->rules_engine = $customdata->rules_engine;
        //$this->config->department_wise = $customdata->department_wise;
        $this->config->tags = $customdata->tags;
        $this->config->tags = $customdata->map;
       // $this->config->usesql = $customdata->usesql;
       // $this->config->querysql = $customdata->querysql;
       
        $PAGE->requires->js_call_amd('block_recommendation/coursesslider');
        $renderable = new \block_recommendation\output\recommendation($this->config);
        $renderer = $this->page->get_renderer('block_recommendation');

        $this->content = new stdClass();
        $this->content->text = $renderer->render($renderable);
        $this->content->footer = '';
        
        return $this->content;
    }
    // The PHP tag and the curly bracket for the class definition 
    // will only be closed after there is another function added in the next section.

    function has_config() {
        return true;
    }

    function applicable_formats() {
        return array('all' => true);
    }

    function instance_allow_multiple() {
        return false;
    }
}