<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/kursmodule/backup/moodle2/restore_kursmodule_stepslib.php');

/**
 * Restore-Task fuer mod_kursmodule.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_kursmodule_activity_task extends restore_activity_task {

    /**
     * @return void
     */
    protected function define_my_settings() {
        // Keine zusaetzlichen Einstellungen.
    }

    /**
     * @return void
     */
    protected function define_my_steps() {
        $this->add_step(new restore_kursmodule_activity_structure_step('kursmodule_structure', 'kursmodule.xml'));
    }

    /**
     * @return array
     */
    public static function define_decode_contents() {
        return [
            new restore_decode_content('kursmodule', ['intro'], 'kursmodule'),
        ];
    }

    /**
     * @return array
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('KURSMODULEVIEWBYID', '/mod/kursmodule/view.php?id=$1', 'course_module'),
        ];
    }

    /**
     * @return array
     */
    public static function define_restore_log_rules() {
        return [
            new restore_log_rule('kursmodule', 'view', 'view.php?id={course_module}', '{kursmodule}'),
        ];
    }
}
