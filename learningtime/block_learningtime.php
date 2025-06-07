<?php
// This file is part of the Learning Time Tracker block for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Learning Time Tracker block.
 *
 * @package    block_learningtime
 * @copyright  2023 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_learningtime extends block_base {

    /**
     * Initializes the block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_learningtime');
    }

    /**
     * Returns the contents.
     *
     * @return stdClass contents of block
     */
    public function get_content() {
        global $CFG, $PAGE;
    
        if ($this->content !== null) {
            return $this->content;
        }
    
        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';
    
        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }
    
        // Load Chart.js library.
        $PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/chart.js'));
        
        // Load our JavaScript module.
        $PAGE->requires->js_call_amd('block_learningtime/learningtime', 'init');
        
        // Add CSS.
        $PAGE->requires->css('/blocks/learningtime/styles.css');
    
        // Create the renderable.
        $renderable = new \block_learningtime\output\main($this->config, $this->context);
        
        // Get the renderer and render the content - THIS IS THE FIXED PART
        $renderer = $this->page->get_renderer('block_learningtime');
        $this->content->text = $renderer->render($renderable);
    
        return $this->content;
    }

    /**
     * Locations where block can be displayed.
     *
     * @return array
     */
    public function applicable_formats() {
        return array(
            'my' => true,
            'site' => true,
            'course-view' => true
        );
    }

    /**
     * Allow multiple instances of the block.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Allow configuration of the block.
     *
     * @return bool
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     * Return the plugin config settings for external functions.
     *
     * @return stdClass the configs for both the block instance and plugin
     * @since Moodle 3.8
     */
    public function get_config_for_external() {
        // Return all settings for all users since it is safe (no private keys, etc..).
        $configs = !empty($this->config) ? $this->config : new stdClass();

        return (object) [
            'instance' => $configs,
            'plugin' => new stdClass(),
        ];
    }
}