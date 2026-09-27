<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Admin-Einstellungen fuer mod_kursmodule: legt fest, welche Kurse
 * Trainer/innen ueberhaupt verlinken duerfen (classes/course_filter.php).
 * Grundvoraussetzung ist immer die Rolle "Trainer/in mit Bearbeitungsrecht"
 * (editingteacher) - fest und nicht abschaltbar. Die beiden Checkboxen
 * hier erweitern das optional auf "Trainer/in ohne Bearbeitungsrecht" bzw.
 * auf reine Teilnehmer/innen. Die Einschraenkungen sind fuer Trainer/innen
 * nicht abschaltbar - nur Admins koennen sie hier konfigurieren.
 * Website-Admins selbst sind immer ausgenommen.
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
        'mod_kursmodule/restrictroleteacher',
        get_string('settings_restrictroleteacher', 'mod_kursmodule'),
        get_string('settings_restrictroleteacher_desc', 'mod_kursmodule'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_kursmodule/restrictrolestudent',
        get_string('settings_restrictrolestudent', 'mod_kursmodule'),
        get_string('settings_restrictrolestudent_desc', 'mod_kursmodule'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'mod_kursmodule/fieldfiltershortnames',
        get_string('settings_fieldfiltershortnames', 'mod_kursmodule'),
        get_string('settings_fieldfiltershortnames_desc', 'mod_kursmodule'),
        '',
        PARAM_NOTAGS
    ));
}
