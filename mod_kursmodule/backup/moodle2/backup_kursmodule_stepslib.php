<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Backup-Struktur fuer eine einzelne mod_kursmodule-Instanz.
 *
 * Gesichert werden die Instanz selbst und alle Links samt Banner-Bild.
 * NICHT gesichert wird die Tracking-Tabelle {kursmodule_enrol} - die
 * automatischen Einschreibungen werden nach einer Wiederherstellung durch
 * die geplante Aufgabe bzw. die naechste Einschreibe-Aenderung im
 * Hauptkurs neu aufgebaut, da eine wiederhergestellte Instanz ohnehin auf
 * einem moeglicherweise anderen Stand der Kursmitgliedschaft steht.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_kursmodule_activity_structure_step extends backup_activity_structure_step {

    /**
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $kursmodule = new backup_nested_element('kursmodule', ['id'], [
            'name', 'intro', 'introformat', 'timecreated', 'timemodified',
        ]);

        $links = new backup_nested_element('links');
        $link = new backup_nested_element('link', ['id'], [
            'courseid', 'title', 'sortorder', 'iscurrent', 'enrolrole', 'active',
            'timecreated', 'timemodified',
        ]);

        $kursmodule->add_child($links);
        $links->add_child($link);

        $link->set_source_table('kursmodule_link', ['kursmoduleid' => backup::VAR_PARENTID], 'sortorder ASC');

        // courseid verweist auf einen ANDEREN Kurs als den Sicherungskurs und
        // kann daher nicht ueber die uebliche activity-annotate-Logik
        // aufgeloest werden - der Wert wird roh gesichert/uebernommen (siehe
        // Hinweis in der restore_stepslib zu den Grenzen dieser Zuordnung).
        $link->annotate_ids('course', 'courseid');

        $link->annotate_files('mod_kursmodule', 'linkimage', 'id');

        return $this->prepare_activity_structure($kursmodule);
    }
}
