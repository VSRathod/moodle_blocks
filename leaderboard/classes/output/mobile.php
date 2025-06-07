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
 * Mobile output class for block_lp_mycourses.
 *
 * @package  block_lp_mycourses
 * @copyright 2019-onward Mike Churchward (mike.churchward@poetopensource.org)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_leaderboard\output;

defined('MOODLE_INTERNAL') || die();

class mobile {

    /**
     * Returns the initial page when viewing the block for the mobile app.
     *
     * @param  array $args Arguments from tool_mobile_get_content WS
     * @return array HTML, javascript and other data
     */
    public static function mobile_view_block($args) {
        global $CFG, $PAGE, $OUTPUT, $DB, $USER;
        $currentcompany = $DB->get_record('company_users', array('userid'=>$USER->id));
        $xplist = $DB->get_records_sql('Select u.firstname, u.lastname, u.id, xp.userid, xp.xp, xp.lvl from {block_xp} xp join mdl_user u on u.id = xp.userid and u.suspended = 0 and u.deleted = 0 join {company_users} cu on cu.userid = xp.userid where courseid =1 and cu.companyid = '.$currentcompany->companyid.' ORDER BY xp DESC LIMIT 0,5');
        $xplist = array_values($xplist);
        $html = $OUTPUT->render_from_template('block_leaderboard/mobile_view_block', []);
        $return = [
            'templates' => [
                [
                    'id' => 'leaderboard',
                    'html' => $html
                ],
            ],
            'javascript' => file_get_contents($CFG->dirroot . '/blocks/leaderboard/appjs/addon.js'),
            'otherdata' => [
                'block_id' => 'leaderboard',
                'title'=> get_string('pluginname', 'block_leaderboard'),
                'content'=> json_encode($xplist)
            ], 
            'files' => null
        ];
        return $return;
    }
}