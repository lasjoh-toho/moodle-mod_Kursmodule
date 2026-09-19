<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Einschreibe-Plugin-Klasse fuer enrol_kursmodule.
 *
 * Diese Methode wird ausschliesslich programmatisch durch
 * mod_kursmodule\\link_manager verwaltet (eine Instanz pro Zielkurs,
 * angelegt beim ersten aktiven Link auf diesen Kurs). Sie taucht in der
 * Teilnehmer/innen-Liste als eigene, klar erkennbare Einschreibequelle auf
 * und wird nie mit manuellen oder anderen automatischen Einschreibungen
 * vermischt - dadurch kann mod_kursmodule beim Entfernen eines Links exakt
 * nur die selbst erzeugten Einschreibungen wieder entfernen.
 *
 * @package     enrol_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class enrol_kursmodule_plugin extends enrol_plugin {

    /**
     * Jede eingeschriebene Person kann eine eigene Rolle haben
     * (Teilnehmer/in ODER Gast), daher keine geschuetzte Standardrolle.
     *
     * @return bool
     */
    public function roles_protected() {
        return false;
    }

    /**
     * Instanzen entstehen ausschliesslich programmatisch aus
     * mod_kursmodule heraus, nie manuell ueber die UI.
     *
     * @param stdClass $course
     * @return bool
     */
    public function can_add_instance($courseid) {
        return false;
    }

    /**
     * Keine manuelle Einschreibe-UI - die Zuordnung wird vollstaendig
     * durch mod_kursmodule anhand der Hauptkurs-Mitgliedschaft gesteuert.
     *
     * @param stdClass $instance
     * @return bool
     */
    public function allow_manage($instance) {
        return false;
    }

    /**
     * Ein-/Ausschreiben ist nur ueber die eigene API erlaubt (siehe
     * mod_kursmodule\\enrolment_manager), nicht ueber die Teilnehmer-UI.
     *
     * @param stdClass $instance
     * @return bool
     */
    public function allow_enrol($instance) {
        return false;
    }

    /**
     * @param stdClass $instance
     * @return bool
     */
    public function allow_unenrol($instance) {
        return false;
    }

    /**
     * Anzeigename der Instanz in der Teilnehmer/innen-Liste.
     *
     * @param stdClass $instance
     * @return string
     */
    public function get_instance_name($instance) {
        return get_string('pluginname', 'enrol_kursmodule');
    }

    /**
     * Eigenes Icon in der Uebersicht (faellt sonst auf Standard zurueck).
     *
     * @param stdClass $instance
     * @return array
     */
    public function get_info_icons(array $instances) {
        return [new pix_icon('icon', get_string('pluginname', 'enrol_kursmodule'), 'enrol_kursmodule')];
    }
}
