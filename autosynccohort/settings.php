<?php
defined('MOODLE_INTERNAL') || die();
if ($hassiteconfig) {
    $settings = new admin_settingpage('local_autosynccohort', get_string('pluginname', 'local_autosynccohort'));
    $settings->add(new admin_setting_configcheckbox('local_autosynccohort/enable_institution',
        'Enable institution->cohorts', '', 1));
    $settings->add(new admin_setting_configcheckbox('local_autosynccohort/enable_department',
        'Enable department->cohorts', '', 1));
    $settings->add(new admin_setting_configcheckbox('local_autosynccohort/enable_usertype',
        'Enable usertype->cohorts', '', 1));
    $ADMIN->add('localplugins', $settings);
}
