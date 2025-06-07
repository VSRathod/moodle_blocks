<?php
defined('MOODLE_INTERNAL') || die();

$capabilities =  array(
    'block/login_activity:addinstance'=> array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => ['manager', 'teacher']
    ),
    'block/login_activity:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => ['manager', 'teacher']
    ),
);
