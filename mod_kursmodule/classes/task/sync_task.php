<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_kursmodule\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Geplante Aufgabe: entfernt regelmaessig Einschreibungen von Personen,
 * die inzwischen keine Schueler/innen des jeweiligen Hauptkurses mehr
 * sind. Dient als Sicherheitsnetz, falls durch Massenoperationen
 * (Bulk-Ausschreibung, Kohorten-Sync, Import) einzelne Events nicht
 * ausgeloest wurden. Schreibt NIEMANDEN neu ein - das geschieht
 * ausschliesslich per Klick auf einen Banner.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_task extends \core\task\scheduled_task {

    /**
     * @return string
     */
    public function get_name() {
        return get_string('tasksync', 'mod_kursmodule');
    }

    /**
     * Bereinigt fuer alle aktiven Links verwaiste Einschreibungen.
     *
     * @return void
     */
    public function execute() {
        global $DB;

        $links = $DB->get_records('kursmodule_link', ['active' => 1]);
        $count = 0;
        foreach ($links as $link) {
            \mod_kursmodule\link_manager::reconcile_link($link);
            $count++;
        }

        mtrace("mod_kursmodule: {$count} aktive Links bereinigt.");
    }
}
