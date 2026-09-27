<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Admin-Einstellungen fuer mod_kursmodule: legt fest, welche Kurse
 * Trainer/innen ueberhaupt verlinken duerfen (classes/course_filter.php).
 * Die Einschraenkungen sind fuer Trainer/innen nicht abschaltbar - nur
 * Admins koennen sie hier konfigurieren. Website-Admins selbst sind von
 * beiden Einschraenkungen immer ausgenommen.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'mod_kursmodule/coursefilters',
        get_string('settings_coursefilters', 'mod_kursmodule'),
        get_string('settings_coursefilters_desc', 'mod_kursmodule')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_kursmodule/restrictroleediting',
        get_string('settings_restrictroleediting', 'mod_kursmodule'),
        get_string('settings_restrictroleediting_desc', 'mod_kursmodule'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_kursmodule/restrictroleteacher',
        get_string('settings_restrictroleteacher', 'mod_kursmodule'),
        get_string('settings_restrictroleteacher_desc', 'mod_kursmodule'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_kursmodule/enablefieldfilter',
        get_string('settings_enablefieldfilter', 'mod_kursmodule'),
        get_string('settings_enablefieldfilter_desc', 'mod_kursmodule'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'mod_kursmodule/fieldfiltershortname',
        get_string('settings_fieldfiltershortname', 'mod_kursmodule'),
        get_string('settings_fieldfiltershortname_desc', 'mod_kursmodule'),
        '',
        PARAM_ALPHANUMEXT
    ));
}
