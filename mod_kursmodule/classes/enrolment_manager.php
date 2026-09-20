<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_kursmodule;

defined('MOODLE_INTERNAL') || die();

/**
 * Kapselt saemtliche Ein-/Ausschreibe-Operationen in Zielkursen. Nutzt die
 * "Manuelle Einschreibung" (enrol_manual) jedes Zielkurses - dadurch
 * braucht Kursmodule kein eigenes zweites Plugin. Jede hier erzeugte
 * Einschreibung wird in {kursmodule_enrol} nachverfolgt, damit spaeter nur
 * die selbst erzeugten Einschreibungen wieder entfernt werden.
 *
 * WICHTIGE EINSCHRAENKUNG (bewusste Design-Entscheidung): weil die
 * Einschreibung ueber dieselbe "Manuelle Einschreibung"-Instanz laeuft,
 * die auch Lehrende im Zielkurs selbst nutzen koennen, kann Moodle NICHT
 * unterscheiden, ob eine konkrete Rollenzuweisung ueber diesen Weg von
 * Kursmodule oder manuell von einer Lehrkraft erzeugt wurde. Wird eine
 * Person, die zusaetzlich manuell in genau denselben Zielkurs
 * eingetragen wurde, ueber einen Kursmodule-Link wieder entfernt, wird
 * sie deshalb vollstaendig ausgeschrieben (auch aus der manuellen
 * Einschreibung). Bei Bedarf einer strikt isolierten, niemals
 * ueberschneidenden Einschreibung braeuchte es wieder ein eigenes
 * Einschreibe-Plugin (siehe README).
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrolment_manager {

    /** @var int|null zwischengespeicherte Rollen-ID fuer "student" */
    private static $studentroleid = null;

    /** @var int|null zwischengespeicherte Rollen-ID fuer "guest" */
    private static $guestroleid = null;

    /**
     * Liefert (und aktiviert/erzeugt bei Bedarf) die "Manuelle
     * Einschreibung"-Instanz eines Zielkurses.
     *
     * @param int $courseid
     * @return \stdClass enrol-Instanzdatensatz
     */
    public static function get_or_create_instance(int $courseid): \stdClass {
        global $DB;

        $instance = $DB->get_record('enrol', ['enrol' => 'manual', 'courseid' => $courseid]);
        $plugin = enrol_get_plugin('manual');

        if ($instance) {
            if ($instance->status != ENROL_INSTANCE_ENABLED) {
                $plugin->update_status($instance, ENROL_INSTANCE_ENABLED);
                $instance = $DB->get_record('enrol', ['id' => $instance->id]);
            }
            return $instance;
        }

        // Regulaere Kurse haben praktisch immer bereits eine manuelle
        // Einschreibe-Instanz (wird bei Kursanlage automatisch erzeugt) -
        // dieser Zweig ist nur ein Sicherheitsnetz fuer den Ausnahmefall.
        $course = get_course($courseid);
        $instanceid = $plugin->add_instance($course, ['status' => ENROL_INSTANCE_ENABLED]);

        return $DB->get_record('enrol', ['id' => $instanceid]);
    }

    /**
     * Ermittelt die Rollen-ID fuer 'student' bzw. 'guest'.
     *
     * @param string $enrolrole 'student' oder 'guest'
     * @return int
     */
    public static function resolve_roleid(string $enrolrole): int {
        global $DB;

        if ($enrolrole === 'guest') {
            if (self::$guestroleid === null) {
                $role = $DB->get_record('role', ['shortname' => 'guest'], '*', MUST_EXIST);
                self::$guestroleid = (int) $role->id;
            }
            return self::$guestroleid;
        }

        if (self::$studentroleid === null) {
            $role = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
            self::$studentroleid = (int) $role->id;
        }
        return self::$studentroleid;
    }

    /**
     * Stellt sicher, dass ein/e Nutzer/in fuer einen bestimmten Link im
     * Zielkurs eingeschrieben ist, mit der fuer den Link konfigurierten
     * Rolle. Bereits bestehende, ueber diesen Link erzeugte Einschreibungen
     * werden bei Rollenwechsel angepasst statt doppelt angelegt.
     *
     * @param \stdClass $link Datensatz aus kursmodule_link
     * @param int $userid
     * @return void
     */
    public static function ensure_enrolled(\stdClass $link, int $userid): void {
        global $DB;

        $roleid = self::resolve_roleid($link->enrolrole);
        $tracked = $DB->get_record('kursmodule_enrol', [
            'linkid' => $link->id,
            'userid' => $userid,
            'courseid' => $link->courseid,
        ]);

        $instance = self::get_or_create_instance($link->courseid);
        $plugin = enrol_get_plugin('manual');

        if ($tracked) {
            if ((int) $tracked->roleid !== $roleid) {
                $context = \context_course::instance($link->courseid);
                role_unassign($tracked->roleid, $userid, $context->id, 'enrol_manual', $instance->id);
                role_assign($roleid, $userid, $context->id, 'enrol_manual', $instance->id);
                $tracked->roleid = $roleid;
                $DB->update_record('kursmodule_enrol', $tracked);
            }
            return;
        }

        // enrol_user() ist idempotent: ist die Person bereits (z. B. manuell
        // von einer Lehrkraft) im Zielkurs eingeschrieben, wird nur die
        // Rolle ergaenzt statt eine zweite Einschreibung zu erzeugen.
        $plugin->enrol_user($instance, $userid, $roleid);

        $record = new \stdClass();
        $record->linkid = $link->id;
        $record->userid = $userid;
        $record->courseid = $link->courseid;
        $record->roleid = $roleid;
        $record->enrolid = $instance->id;
        $record->timecreated = time();
        $DB->insert_record('kursmodule_enrol', $record);
    }

    /**
     * Entfernt eine ueber einen Link erzeugte Einschreibung fuer eine
     * Person wieder. Die tatsaechliche Einschreibung im Zielkurs wird nur
     * dann vollstaendig aufgehoben, wenn kein anderer aktiver Link
     * (derselben oder einer anderen Kursmodule-Instanz) dieselbe Person
     * noch im selben Zielkurs benoetigt. Siehe Klassenkommentar zur
     * Einschraenkung bei parallel bestehender manueller Einschreibung.
     *
     * @param int $linkid
     * @param int $userid
     * @param int $courseid
     * @return void
     */
    public static function remove_tracked_enrolment(int $linkid, int $userid, int $courseid): void {
        global $DB;

        $tracked = $DB->get_record('kursmodule_enrol', [
            'linkid' => $linkid,
            'userid' => $userid,
            'courseid' => $courseid,
        ]);
        if (!$tracked) {
            return;
        }

        $DB->delete_records('kursmodule_enrol', ['id' => $tracked->id]);

        $stillneeded = $DB->record_exists('kursmodule_enrol', [
            'userid' => $userid,
            'courseid' => $courseid,
        ]);
        if ($stillneeded) {
            // Ein anderer Link haelt diese Einschreibung weiterhin - nicht anfassen.
            return;
        }

        $instance = $DB->get_record('enrol', ['id' => $tracked->enrolid]);
        if (!$instance) {
            return;
        }
        $plugin = enrol_get_plugin('manual');
        $plugin->unenrol_user($instance, $userid);
    }

    /**
     * Alle Nutzer/innen, die ueber einen bestimmten Link eingeschrieben
     * wurden.
     *
     * @param int $linkid
     * @return array userid => Trackingdatensatz
     */
    public static function get_tracked_users(int $linkid): array {
        global $DB;
        return $DB->get_records('kursmodule_enrol', ['linkid' => $linkid], '', 'userid, id, linkid, courseid, roleid, enrolid');
    }
}
