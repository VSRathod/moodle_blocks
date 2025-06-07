<?php
function get_user_enrolled_courses($userid) {
    global $DB;

    $sql = "SELECT c.id, c.fullname, c.shortname 
            FROM {course} c
            JOIN {enrol} e ON e.courseid = c.id
            JOIN {user_enrolments} ue ON ue.enrolid = e.id
            WHERE ue.userid = :userid
            ORDER BY c.fullname ASC";

    return $DB->get_records_sql($sql, ['userid' => $userid]);
}
