<?php
// This file is part of Moodle - http://moodle.org/
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
 * recommendation block rendrer
 *
 * @package    block_recommendation

 */

namespace block_recommendation\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;
class renderer extends plugin_renderer_base {
  
    public function render_recommendation(recommendation $recommendation) {
        return $this->render_from_template('block_recommendation/recommendation', $recommendation->export_for_template($this));
    }

   
}
