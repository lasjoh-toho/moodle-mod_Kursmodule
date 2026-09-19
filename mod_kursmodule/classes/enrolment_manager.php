<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_kursmodule;

defined('MOODLE_INTERNAL') || die();

/**
 * Kapselt saemtliche Ein-/Ausschreibe-Operationen in Zielkursen ueber die
 * isolierte Einschreibemethode enrol_kursmodule. Jede hier erzeugte
 * Einschreibung wird in {kursmodule_enrol} nachverfolgt, damit spaeter
 * garantiert NUR selbst erzeugte Einschreibungen wieder entfernt werden -
 * manuelle Einschreibungen von Lehrenden oder andere Methoden bleiben
 * immer unberuehrt.
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
     * Liefert (und erzeugt bei Bedarf) die enrol_kursmodule-Instanz eines
     * Zielkurses.
     *
     * @param int $courseid
     * @return stdClass enrol-Instanzdatensatz
     */
    public static function get_or_create_instance(int $courseid): \stdClass {
        global $DB;

        $instance = $DB->get_record('enrol', ['enrol' => 'kursmodule', 'courseid' => $courseid]);
        if ($instance) {
            if ($instance->status != ENROL_INSTANCE_ENABLED) {
                $plugin = enrol_get_plugin('kursmodule');
                $plugin->update_status($instance, ENROL_INSTANCE_ENABLED);
                $instance = $DB->get_record('enrol', ['id' => $instance->id]);
            }
            return $instance;
        }

        $course = get_course($courseid);
        $plugin = enrol_get_plugin('kursmodule');
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
        $plugin = enrol_get_plugin('kursmodule');

        if ($tracked) {
            if ((int) $tracked->roleid !== $roleid) {
                $context = \context_course::instance($link->courseid);
                role_unassign($tracked->roleid, $userid, $context->id, 'enrol_kursmodule', $instance->id);
                role_assign($roleid, $userid, $context->id, 'enrol_kursmodule', $instance->id);
                $tracked->roleid = $roleid;
                $DB->update_record('kursmodule_enrol', $tracked);
            }
            return;
        }

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
     * noch im selben Zielkurs benoetigt.
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
        $plugin = enrol_get_plugin('kursmodule');
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
