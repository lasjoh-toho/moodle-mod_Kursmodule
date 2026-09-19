<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_kursmodule;

defined('MOODLE_INTERNAL') || die();

/**
 * Event-Beobachter: schreibt Personen, die den Hauptkurs verlassen oder
 * dort ihre "student"-Rolle verlieren, automatisch aus allen darueber
 * erzeugten Einschreibungen in verlinkten Kursen wieder aus.
 *
 * Es gibt bewusst KEINE Beobachter fuer neue Einschreibungen/Rollen im
 * Hauptkurs - eine Einschreibung in einen verlinkten Kurs entsteht
 * ausschliesslich per Klick auf den jeweiligen Banner (siehe go.php,
 * link_manager::handle_click()), nicht proaktiv im Hintergrund.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Ausschreibung aus dem (moeglichen) Hauptkurs.
     *
     * @param \core\event\user_enrolment_deleted $event
     * @return void
     */
    public static function user_enrolment_deleted(\core\event\user_enrolment_deleted $event): void {
        link_manager::sync_user_leave((int) $event->courseid, (int) $event->relateduserid);
    }

    /**
     * Rollenentzug (deckt z. B. "student"-Rolle entzogen, aber weiterhin
     * eingeschrieben, ab).
     *
     * @param \core\event\role_unassigned $event
     * @return void
     */
    public static function role_unassigned(\core\event\role_unassigned $event): void {
        if ($event->contextlevel != CONTEXT_COURSE) {
            return;
        }
        if ((int) $event->objectid !== enrolment_manager::resolve_roleid('student')) {
            return;
        }
        if (user_still_has_student_role((int) $event->courseid, (int) $event->relateduserid)) {
            return;
        }
        link_manager::sync_user_leave((int) $event->courseid, (int) $event->relateduserid);
    }
}

/**
 * Hilfsfunktion (Namespace-Funktion): prueft direkt in der DB, ob eine
 * Person nach einem role_unassigned-Event evtl. noch ueber eine andere
 * Zuweisung die student-Rolle im Kurs haelt (z. B. mehrere Rollenquellen).
 *
 * @param int $courseid
 * @param int $userid
 * @return bool
 */
function user_still_has_student_role(int $courseid, int $userid): bool {
    $context = \context_course::instance($courseid);
    $roleid = enrolment_manager::resolve_roleid('student');
    $users = get_role_users($roleid, $context, false, 'u.id', null, true);
    return isset($users[$userid]);
}
