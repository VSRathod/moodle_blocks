<?php
class block_user_management_renderer extends plugin_renderer_base {

    public function render_search_form($search = '') {
        $output = html_writer::start_tag('form', [
            'method' => 'get',
            'action' => ''
        ]);

        // Preserve existing query parameters except 'search'.
        foreach ($_GET as $key => $value) {
            if ($key !== 'search') {
                $output .= html_writer::empty_tag('input', [
                    'type' => 'hidden',
                    'name' => $key,
                    'value' => $value
                ]);
            }
        }

        // Search input field.
        
        return $output;
    }

    public function render_user_table($search = '', $page = 1, $perpage = 5) {
        global $DB, $PAGE;

        $params = [];
        $offset = ($page - 1) * $perpage;

        $sql = "SELECT u.id, u.firstname, u.lastname, u.username, u.lastaccess, u.timecreated, 
                       r.shortname AS role, r.archetype
                FROM {user} u
                JOIN {role_assignments} ra ON ra.userid = u.id
                JOIN {role} r ON ra.roleid = r.id
                WHERE u.deleted = 0 AND u.id != 1";

        if (!empty($search)) {
            $sql .= " AND (LOWER(u.firstname) LIKE :search OR LOWER(u.lastname) LIKE :search)";
            $params['search'] = '%' . strtolower($search) . '%';
        }

        $countsql = "SELECT COUNT(DISTINCT u.id) " . substr($sql, strpos($sql, 'FROM'));
        $totalusers = $DB->count_records_sql($countsql, $params);

        $sql .= " GROUP BY u.id, r.shortname, r.archetype ORDER BY u.timecreated DESC";
        $sql .= " LIMIT $perpage OFFSET $offset";

        $users = $DB->get_records_sql($sql, $params);
        $userlist = [];

        foreach ($users as $user) {
            $fullname = $user->firstname . ' ' . $user->lastname;
            $isactive = ($user->lastaccess > (time() - 60 * 60 * 24 * 30));
            $status = $isactive ? 'Active' : 'Inactive';
            $statusclass = $isactive ? 'active' : 'inactive';
            $lastlogin = $user->lastaccess ? userdate($user->lastaccess, '%d %B %Y, %I:%M %p') : 'Never';
            $courses = count(enrol_get_users_courses($user->id));

            $role = !empty($user->archetype) ? ucfirst($user->archetype) : ucfirst($user->role);
            if ($role === 'User') $role = 'Learner';
            if ($role === 'Coursecreator') $role = 'Manager';

            $userlist[] = [
                'id' => $user->id,
                'name' => $fullname,
                'role' => $role,
                'enrol_date' => userdate($user->timecreated, '%d %B %Y'),
                'status' => $status,
                'status_class' => $statusclass,
                'courses_enrolled' => $courses,
                'last_login' => $lastlogin
            ];
        }

        $pagination = $this->get_pagination_data($page, $perpage, $totalusers);

        // ✅ Include the JS file.
        $PAGE->requires->js_call_amd('block_user_management/user_actions', 'init');

        return $this->render_from_template('block_user_management/user_table', [
            'searchform' => $this->render_search_form($search),
            'users' => $userlist,
            'pagination' => $pagination,
            'totalusers' => $totalusers
        ]);
    }

    protected function get_pagination_data($currentpage, $perpage, $total) {
        $pages = ceil($total / $perpage);
        if ($pages <= 1) {
            return null;
        }

        $pagination = [
            'pages' => [],
            'prev' => $currentpage > 1,
            'prev_page' => $currentpage - 1,
            'next' => $currentpage < $pages,
            'next_page' => $currentpage + 1
        ];

        $start = 1;
        $end = min($pages, 6);

        if ($currentpage > 4 && $pages > 6) {
            $start = max(1, min($currentpage - 2, $pages - 5));
            $end = min($pages, $start + 5);
        }

        for ($i = $start; $i <= $end; $i++) {
            $pagination['pages'][] = [
                'page' => $i,
                'page_formatted' => sprintf("%02d", $i),
                'active' => ($i == $currentpage)
            ];
        }

        return $pagination;
    }
}
