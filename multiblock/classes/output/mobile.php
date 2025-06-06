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

namespace block_multiblock\output;

/**
 * Class mobile
 *
 * @package    block_multiblock
 * @copyright  2025 Sumaiya Javed <sumaiya.javed@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {
    /**
     * Returns the view for the mobile app.
     *
     * @param  array $args Arguments from tool_mobile_get_content WS
     * @return array HTML, javascript and otherdata
     */
    public static function mobile_multiblock_view($args) {
        global $OUTPUT, $CFG, $DB;
        require_once($CFG->libdir . '/blocklib.php');
        $multiblock = [];
        $blockid = $args['blockid'];
        $blockinstance = block_instance_by_id($blockid);
        $presentation = $blockinstance->config->presentation;
        // Only supports accordion/carousel effect.
        if ($presentation == 'carousel') {
            $template = "block_multiblock/carousel-mobile";
        } else {
            $template = "block_multiblock/accordion-mobile";
        }
        $data["multiblockid"] = $blockid;
        $context = $DB->get_record('context', ['contextlevel' => CONTEXT_BLOCK,
                    'instanceid' => $blockinstance->instance->id]);
        $blocks = $DB->get_records('block_instances', [
                    'parentcontextid' => $context->id], 'defaultweight, id');
        $active = array_key_first($blocks);
        foreach ($blocks as $id => $block) {
            if (block_load_class($block->blockname)) {
                // Make the proxy class we'll need.
                $subblockinstance = block_instance($block->blockname, $block);
                if ($subblockinstance->get_content()->text) {
                    $content = $subblockinstance->get_content()->text;
                } else {
                    $rows = $subblockinstance->get_content();
                    $content = '';
                    foreach ($rows as $key => $items) {
                        if ($key != 'footer') {
                            if (!empty($items) && is_array($items)) {
                                $content = implode(" - ", $items);
                            }
                        }
                    }
                }
                $multiblock[$id]["id"] = $id;
                $multiblock[$id]["title"] = $subblockinstance->get_title();
                $multiblock[$id]["content"] = $content;
            }
        }
        $data = [
          'multiblockid' => $blockid,
          'multiblock' => array_values($multiblock),
          'active' => $active,
        ];
        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template($template, $data),
                ],
            ],
            'javascript' => '',
        ];
    }
}
