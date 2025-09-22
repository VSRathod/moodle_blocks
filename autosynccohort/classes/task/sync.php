<?php
namespace local_autosynccohort\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use context_system;

/**
 * Scheduled task: sync users into cohorts based on profile fields.
 */
class sync extends scheduled_task {

    /**
     * Name shown in scheduled tasks UI.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('autosynctask', 'local_autosynccohort');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB, $CFG;

        // Ensure cohort helper functions are available.
        require_once($CFG->dirroot . '/cohort/lib.php');

        $syscontext = context_system::instance();

        // Get all non-deleted users except guest (id=1) to avoid adding guest.
        $sql = "SELECT id, institution, department
                  FROM {user}
                 WHERE deleted = 0 AND id <> 1";
        $users = $DB->get_records_sql($sql);

        if (empty($users)) {
            return;
        }

        // Pre-cache user_info_field mapping to find custom profile field 'usertype'.
        $profilefields = $DB->get_records_menu('user_info_field', null, '', 'id,shortname');

        foreach ($users as $user) {
            // Company / Institution: use exact value as cohort name.
            if (!empty($user->institution)) {
                $this->ensure_user_in_cohort($user->id, trim($user->institution), $syscontext);
            }

            // Department
            if (!empty($user->department)) {
                $this->ensure_user_in_cohort($user->id, trim($user->department), $syscontext);
            }

            // Custom profile field: shortname 'usertype'
            $usertype = $this->get_profile_field_value($user->id, 'usertype', $profilefields);
            if (!empty($usertype)) {
                $this->ensure_user_in_cohort($user->id, trim($usertype), $syscontext);
            }
        }
    }

    /**
     * Ensure a user is a member of cohort named exactly $cohortname.
     * If cohort doesn't exist it's created. Cohort idnumber is a deterministic hash to avoid duplicates.
     *
     * @param int $userid
     * @param string $cohortname
     * @param \context_system $syscontext
     * @return void
     */
    private function ensure_user_in_cohort(int $userid, string $cohortname, $syscontext) {
        global $DB;

        $cohortname = trim($cohortname);
        if ($cohortname === '') {
            return;
        }

        // Use deterministic idnumber so we can find cohorts even if name collisions can occur.
        $idnumber = 'autosync_' . md5($cohortname);

        // Try find by exact name first (user required exact-match), else by idnumber.
        $cohort = $DB->get_record('cohort', ['name' => $cohortname]);
        if (!$cohort) {
            $cohort = $DB->get_record('cohort', ['idnumber' => $idnumber]);
        }

        // Create cohort if missing.
        if (!$cohort) {
            $cohortobj = new \stdClass();
            $cohortobj->contextid = $syscontext->id;
            $cohortobj->name = $cohortname;
            $cohortobj->idnumber = $idnumber;
            $cohortobj->description = 'Auto-created cohort for ' . $cohortname;
            $cohortobj->descriptionformat = FORMAT_MARKDOWN;
            $cohortobj->component = 'local_autosynccohort';

            // cohort_add_cohort returns the new id (or false on failure).
            $newid = cohort_add_cohort($cohortobj);
            if ($newid === false) {
                // Creation failed for some reason (permissions / DB). Skip.
                return;
            }
            // Fetch newly created cohort record.
            $cohort = $DB->get_record('cohort', ['id' => $newid], '*', MUST_EXIST);
        }

        // Add user as member if not already.
        if (!$DB->record_exists('cohort_members', ['cohortid' => $cohort->id, 'userid' => $userid])) {
            cohort_add_member($cohort->id, $userid);
        }
    }

    /**
     * Get a custom profile field value for a user by shortname.
     *
     * @param int $userid
     * @param string $shortname
     * @param array $profilefields mapping id => shortname (optional prefetch)
     * @return string|null
     */
    private function get_profile_field_value(int $userid, string $shortname, array $profilefields = []) {
        global $DB;

        // If profilefields provided, find field id matching shortname.
        $fieldid = null;
        if (!empty($profilefields)) {
            foreach ($profilefields as $id => $sname) {
                if ($sname === $shortname) {
                    $fieldid = (int)$id;
                    break;
                }
            }
        }

        if ($fieldid) {
            $rec = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => $fieldid], 'data');
            return $rec ? trim($rec->data) : null;
        }

        // Fallback: join tables to find the field by shortname.
        $sql = "SELECT uid.data
                  FROM {user_info_data} uid
                  JOIN {user_info_field} uif ON uif.id = uid.fieldid
                 WHERE uid.userid = :uid AND uif.shortname = :shortname
                 LIMIT 1";
        return $DB->get_field_sql($sql, ['uid' => $userid, 'shortname' => $shortname]);
    }
    
}

