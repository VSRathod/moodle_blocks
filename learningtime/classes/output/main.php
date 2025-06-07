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
 * Main output class for the Learning Time Tracker block.
 *
 * @package    block_learningtime
 * @copyright  2023 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_learningtime\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use stdClass;

/**
 * Main output class for the Learning Time Tracker block.
 *
 * @package    block_learningtime
 * @copyright  2023 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class main implements renderable, templatable {

    /** @var stdClass The block instance configuration. */
    protected $config;

    /** @var context The context of the block. */
    protected $context;

    /**
     * Constructor.
     *
     * @param stdClass $config The block instance configuration.
     * @param context $context The block context.
     */
    public function __construct($config, $context) {
        $this->config = $config;
        $this->context = $context;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $USER, $CFG;

        $data = new stdClass();
        $data->userid = $USER->id;
        $data->sesskey = sesskey();
        $data->wwwroot = $CFG->wwwroot;
        
        // Add language strings.
        $data->str_title = get_string('title', 'block_learningtime');
        $data->str_today = get_string('today', 'block_learningtime');
        $data->str_last7days = get_string('last7days', 'block_learningtime');
        $data->str_last30days = get_string('last30days', 'block_learningtime');
        $data->str_last6months = get_string('last6months', 'block_learningtime');
        $data->str_currentyear = get_string('currentyear', 'block_learningtime');
        $data->str_timeperiod = get_string('timeperiod', 'block_learningtime');
        $data->str_total = get_string('total', 'block_learningtime');
        $data->str_average = get_string('average', 'block_learningtime');
        $data->str_minutes = get_string('minutes', 'block_learningtime');
        $data->str_hours = get_string('hours', 'block_learningtime');
        $data->str_noactivity = get_string('noactivity', 'block_learningtime');
        $data->str_loading = get_string('loading', 'block_learningtime');

        return $data;
    }
}