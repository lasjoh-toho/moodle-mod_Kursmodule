<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Restore-Struktur fuer eine mod_kursmodule-Instanz.
 *
 * WICHTIGE EINSCHRAENKUNG: courseid in einem Link zeigt auf einen ANDEREN
 * Kurs als den gerade wiederhergestellten und ist daher kein regulaeres
 * Backup/Restore-Mapping-Ziel. Existiert nach der Wiederherstellung auf
 * dieser Moodle-Instanz (weiterhin) ein Kurs mit dieser ID, bleibt der
 * Link erhalten; existiert er nicht (z. B. Restore auf einer anderen
 * Moodle-Instanz), wird der Link uebersprungen, statt auf einen falschen
 * oder nicht existierenden Kurs zu verweisen. In diesem Fall muss die
 * Verknuepfung nach der Wiederherstellung manuell neu angelegt werden.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_kursmodule_activity_structure_step extends restore_activity_structure_step {

    /**
     * @return array
     */
    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('kursmodule', '/activity/kursmodule');
        $paths[] = new restore_path_element('kursmodule_link', '/activity/kursmodule/links/link');

        return $this->prepare_activity_structure($paths);
    }

    /**
     * @param array $data
     * @return void
     */
    protected function process_kursmodule($data) {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();
        $data->timecreated = $data->timecreated ?? time();
        $data->timemodified = $data->timemodified ?? time();

        $newitemid = $DB->insert_record('kursmodule', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * @param array $data
     * @return void
     */
    protected function process_kursmodule_link($data) {
        global $DB;

        $data = (object) $data;
        $oldlinkid = $data->id;
        unset($data->id);

        $data->kursmoduleid = $this->get_new_parentid('kursmodule');

        if (!$DB->record_exists('course', ['id' => $data->courseid])) {
            // Zielkurs existiert auf dieser Instanz nicht (mehr) - Link
            // bewusst NICHT anlegen, um keinen verwaisten/falschen Verweis
            // zu erzeugen. Siehe Klassenkommentar oben.
            debugging(
                'mod_kursmodule: Kursverknuepfung uebersprungen, Zielkurs ' . $data->courseid . ' existiert auf dieser Instanz nicht.',
                DEBUG_DEVELOPER
            );
            return;
        }

        $data->timecreated = $data->timecreated ?? time();
        $data->timemodified = $data->timemodified ?? time();

        $newlinkid = $DB->insert_record('kursmodule_link', $data);
        $this->set_mapping('kursmodule_link', $oldlinkid, $newlinkid, true);
    }

    /**
     * @return void
     */
    protected function after_execute() {
        $this->add_related_files('mod_kursmodule', 'linkimage', 'kursmodule_link');
    }
}
