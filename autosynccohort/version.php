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
 * Version file for local_autosynccohort plugin.
 *
 * @package    local_autosynccohort
 * @copyright  2025 Vikas Rathod <rathodvikas010@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link       https://github.com/VSrathod
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_autosynccohort';
$plugin->version   = 2025091601;
$plugin->requires  = 2022112800; // Moodle 4.1+
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = 'v1.0 (2025-09-17)';
