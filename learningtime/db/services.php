<?php
// blocks/learningtime/db/services.php

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_learningtime_get_learning_time_data' => [
        'classname' => 'block_learningtime\external',
        'methodname' => 'get_learning_time_data',
        'classpath' => 'blocks/learningtime/classes/externallib.php',
        'description' => 'Get learning time data for a user',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ]
];