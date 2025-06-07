<?php
class block_login_activity extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_login_activity');
    }

    public function get_content() {
        global $OUTPUT, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        
        // Dropdown filter
        $filters = ['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'userwise' => 'User-wise'];
        $filterHtml = '<select id="filter" class="custom-filter">';
        foreach ($filters as $key => $value) {
            $filterHtml .= "<option value='$key'>$value</option>";
        }
        $filterHtml .= '</select>';

        // Chart container
        $chartHtml = '<div id="chart-container"><canvas id="loginChart"></canvas></div>';

        // Include JavaScript
        $PAGE->requires->js_call_amd('block_login_activity/chart_loader', 'init');

        $this->content->text = $filterHtml . $chartHtml;
        return $this->content;
    }
}
