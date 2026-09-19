<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Event-Beobachter-Registrierung fuer mod_kursmodule.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\core\event\user_enrolment_created',
        'callback'    => '\mod_kursmodule\observer::user_enrolment_created',
    ],
    [
        'eventname'   => '\core\event\user_enrolment_deleted',
        'callback'    => '\mod_kursmodule\observer::user_enrolment_deleted',
    ],
    [
        'eventname'   => '\core\event\role_assigned',
        'callback'    => '\mod_kursmodule\observer::role_assigned',
    ],
    [
        'eventname'   => '\core\event\role_unassigned',
        'callback'    => '\mod_kursmodule\observer::role_unassigned',
    ],
];
