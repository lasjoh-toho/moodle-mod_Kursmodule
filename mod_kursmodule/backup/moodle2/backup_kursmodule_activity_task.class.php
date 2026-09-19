<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/kursmodule/backup/moodle2/backup_kursmodule_stepslib.php');

/**
 * Backup-Task fuer mod_kursmodule.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_kursmodule_activity_task extends backup_activity_task {

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
        $this->add_step(new backup_kursmodule_activity_structure_step('kursmodule_structure', 'kursmodule.xml'));
    }

    /**
     * @param string $content
     * @return string
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        $content = preg_replace(
            "/(" . $base . "\/mod\/kursmodule\/view.php\?id\=)([0-9]+)/",
            '$@KURSMODULEVIEWBYID*$2@$',
            $content
        );

        return $content;
    }
}
