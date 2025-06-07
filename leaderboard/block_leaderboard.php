<?php
// File: block_leaderboard_xp/block_leaderboard_xp.php

/**
 * Leaderboard XP block definition.
 *
 * @package   block_leaderboard_xp
 * @copyright Your Name
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use block_xp\di;
use block_xp\local\sql\limit;
use block_xp\local\ranking\ranking;
class block_leaderboard extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_leaderboard');
    }

    public function applicable_formats() {
        return array(
            'my' => true,
            'site' => true,
            'course-view' => true
        );
    }

    public function get_content() {
        global $COURSE,  $DB, $PAGE, $OUTPUT, $CFG;;
        require_once($CFG->dirroot . '/user/lib.php');
        if ($this->content !== null) {
            return $this->content;
        }
        
        // Get contextid (SITEID = Global XP)
        $contextid = context_system::instance()->id;
        
        $sql = "SELECT u.id, u.firstname, u.lastname, u.picture, x.xp
          FROM {block_xp} x
          JOIN {user} u ON u.id = x.userid
         WHERE u.deleted = 0
      ORDER BY x.xp DESC";

$params = [];

$records = $DB->get_records_sql($sql, $params);

$topusers = array_slice($records, 0, 3); // Only top 3 users

$data = [];
$index = 0;
$max_xp = max(array_map(function($user) {
    return $user->xp;
}, $topusers));
foreach ($topusers as $user) {
    $userData = \core_user::get_user($user->id);
   $userpic = new user_picture($userData);
    $userpic->size = 1;

    $data[] = [
        'rank' => $index + 1,
        'fullname' => fullname($user),
        'points' => $user->xp,
        'height' => ($max_xp > 0) ? round(($user->xp / $max_xp) * 160) : 0,
        'picture' => $userpic->get_url($PAGE)->out(false),
    ];
    $index++;
}
        
        $this->content = new stdClass();
        $this->content->text = $OUTPUT->render_from_template('block_leaderboard/leaderboard', ['users' => $data]);

        return $this->content;
    }
}
