<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Upgrade-Schritte fuer mod_kursmodule.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @param int $oldversion
 * @return bool
 */
function xmldb_kursmodule_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2026092001) {
        // Ab hier nutzt Kursmodule die "Manuelle Einschreibung" jedes
        // Zielkurses statt einer eigenen enrol_kursmodule-Instanz (das
        // zweite Plugin entfaellt dadurch komplett). Bereits getrackte
        // Zeilen aus fruehen Testversionen zeigen noch auf die alte,
        // jetzt nicht mehr vorhandene Einschreibemethode - da es sich
        // dabei nur um interne Nachverfolgung handelt (nicht um die
        // tatsaechliche Einschreibung selbst), werden sie hier einfach
        // geleert. Die naechste Aktion (Klick auf einen Banner bzw. die
        // naechste geplante Aufgabe) baut den Tracking-Stand ueber die
        // manuelle Einschreibung sauber neu auf.
        $DB->delete_records('kursmodule_enrol');

        upgrade_mod_savepoint(true, 2026092001, 'kursmodule');
    }

    return true;
}
