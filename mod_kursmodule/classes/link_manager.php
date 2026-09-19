<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_kursmodule;

defined('MOODLE_INTERNAL') || die();

/**
 * Verwaltet die Kursverknuepfungs-Banner (kursmodule_link) einer
 * Kursmodule-Instanz und haelt die Einschreibungen in den verlinkten
 * Unterkursen dauerhaft synchron zur Mitgliedschaft (Rolle "Schueler/in")
 * im Hauptkurs: ein Link ist ab dem Anlegen aktiv und bleibt es, bis er im
 * Modul deaktiviert oder entfernt wird (dann automatische Aus-Schreibung).
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class link_manager {

    /**
     * Alle Links einer Instanz, sortiert.
     *
     * @param int $kursmoduleid
     * @param bool $onlyactive
     * @return array
     */
    public static function get_links(int $kursmoduleid, bool $onlyactive = false): array {
        global $DB;

        $conditions = ['kursmoduleid' => $kursmoduleid];
        if ($onlyactive) {
            $conditions['active'] = 1;
        }
        return $DB->get_records('kursmodule_link', $conditions, 'sortorder ASC, id ASC');
    }

    /**
     * @param int $linkid
     * @return \stdClass
     */
    public static function get_link(int $linkid): \stdClass {
        global $DB;
        return $DB->get_record('kursmodule_link', ['id' => $linkid], '*', MUST_EXIST);
    }

    /**
     * Legt einen neuen Kursverknuepfungs-Link an und schreibt sofort alle
     * aktuellen Schueler/innen des Hauptkurses in den Zielkurs ein.
     *
     * @param int $kursmoduleid
     * @param int $courseid Zielkurs (Unterkurs)
     * @param string|null $title optionaler Anzeigetitel
     * @param string $enrolrole 'student' oder 'guest'
     * @return int id des neuen Links
     */
    public static function add_link(int $kursmoduleid, int $courseid, ?string $title, string $enrolrole): int {
        global $DB;

        $maxsort = $DB->get_field_sql(
            'SELECT MAX(sortorder) FROM {kursmodule_link} WHERE kursmoduleid = ?',
            [$kursmoduleid]
        );

        $record = new \stdClass();
        $record->kursmoduleid = $kursmoduleid;
        $record->courseid = $courseid;
        $record->title = $title !== '' ? $title : null;
        $record->sortorder = ((int) $maxsort) + 1;
        $record->iscurrent = 0;
        $record->enrolrole = $enrolrole === 'guest' ? 'guest' : 'student';
        $record->active = 1;
        $record->timecreated = time();
        $record->timemodified = $record->timecreated;

        $linkid = $DB->insert_record('kursmodule_link', $record);
        $record->id = $linkid;

        self::sync_link($record);

        return $linkid;
    }

    /**
     * Aktualisiert einen Link. Reagiert automatisch auf Rollenwechsel,
     * Aktivierung/Deaktivierung und Zielkurswechsel, indem die
     * Einschreibungen entsprechend nachgezogen werden.
     *
     * @param int $linkid
     * @param \stdClass $data Felder title, courseid, enrolrole, active (nur gesetzte werden uebernommen)
     * @return void
     */
    public static function update_link(int $linkid, \stdClass $data): void {
        global $DB;

        $link = self::get_link($linkid);
        $oldcourseid = (int) $link->courseid;
        $wasactive = (int) $link->active === 1;

        if (isset($data->title)) {
            $link->title = $data->title !== '' ? $data->title : null;
        }
        if (isset($data->enrolrole)) {
            $link->enrolrole = $data->enrolrole === 'guest' ? 'guest' : 'student';
        }
        if (isset($data->courseid)) {
            $link->courseid = (int) $data->courseid;
        }
        if (isset($data->active)) {
            $link->active = (int) $data->active === 1 ? 1 : 0;
        }
        $link->timemodified = time();

        $DB->update_record('kursmodule_link', $link);

        $nowactive = (int) $link->active === 1;
        $targetchanged = $oldcourseid !== (int) $link->courseid;

        if ($targetchanged && $wasactive) {
            // Aus allen bisher getrackten Einschreibungen im alten Zielkurs entfernen.
            foreach (enrolment_manager::get_tracked_users($linkid) as $tracked) {
                enrolment_manager::remove_tracked_enrolment($linkid, (int) $tracked->userid, $oldcourseid);
            }
        }

        if ($wasactive && !$nowactive) {
            self::unsync_link($link);
        } else if ($nowactive) {
            // Deckt sowohl Reaktivierung als auch Rollen-/Zielkurswechsel ab.
            self::sync_link($link);
        }
    }

    /**
     * Entfernt einen Link vollstaendig: schreibt alle darueber
     * eingeschriebenen Personen wieder aus und loescht Datensatz + Bild.
     *
     * @param int $linkid
     * @return void
     */
    public static function remove_link(int $linkid): void {
        global $DB;

        $link = $DB->get_record('kursmodule_link', ['id' => $linkid]);
        if (!$link) {
            return;
        }

        self::unsync_link($link);

        $kursmodule = $DB->get_record('kursmodule', ['id' => $link->kursmoduleid]);
        if ($kursmodule) {
            $cm = get_coursemodule_from_instance('kursmodule', $kursmodule->id, $kursmodule->course);
            if ($cm) {
                $context = \context_module::instance($cm->id);
                $fs = get_file_storage();
                $fs->delete_area_files($context->id, 'mod_kursmodule', 'linkimage', $linkid);
            }
        }

        $DB->delete_records('kursmodule_link', ['id' => $linkid]);
    }

    /**
     * Setzt die Sortierreihenfolge einer Instanz neu (Drag & Drop).
     *
     * @param int $kursmoduleid
     * @param int[] $orderedlinkids Link-IDs in gewuenschter Reihenfolge
     * @return void
     */
    public static function reorder(int $kursmoduleid, array $orderedlinkids): void {
        global $DB;

        $position = 1;
        foreach ($orderedlinkids as $linkid) {
            $DB->set_field_select(
                'kursmodule_link',
                'sortorder',
                $position,
                'id = ? AND kursmoduleid = ?',
                [(int) $linkid, $kursmoduleid]
            );
            $position++;
        }
    }

    /**
     * Markiert genau einen Link einer Instanz als "aktuell".
     *
     * @param int $kursmoduleid
     * @param int $linkid
     * @return void
     */
    public static function set_current(int $kursmoduleid, int $linkid): void {
        global $DB;

        $DB->set_field('kursmodule_link', 'iscurrent', 0, ['kursmoduleid' => $kursmoduleid]);
        $DB->set_field('kursmodule_link', 'iscurrent', 1, ['id' => $linkid, 'kursmoduleid' => $kursmoduleid]);
    }

    /**
     * Schreibt alle Schueler/innen des Hauptkurses in den Zielkurs des
     * Links ein (Vollabgleich, z. B. nach Anlegen/Reaktivieren des Links).
     *
     * @param \stdClass $link
     * @return void
     */
    public static function sync_link(\stdClass $link): void {
        foreach (self::get_student_ids_in_main_course($link->kursmoduleid) as $userid) {
            enrolment_manager::ensure_enrolled($link, $userid);
        }
    }

    /**
     * Entfernt alle ueber diesen Link erzeugten Einschreibungen wieder.
     *
     * @param \stdClass $link
     * @return void
     */
    public static function unsync_link(\stdClass $link): void {
        foreach (enrolment_manager::get_tracked_users($link->id) as $tracked) {
            enrolment_manager::remove_tracked_enrolment($link->id, (int) $tracked->userid, (int) $link->courseid);
        }
    }

    /**
     * Reagiert auf eine neue Schueler/innen-Einschreibung im Hauptkurs:
     * schreibt die Person in alle aktiven Links aller Kursmodule-Instanzen
     * dieses Kurses ein.
     *
     * @param int $maincourseid
     * @param int $userid
     * @return void
     */
    public static function sync_user_join(int $maincourseid, int $userid): void {
        global $DB;

        if (!self::is_student_in_course($maincourseid, $userid)) {
            return;
        }

        $kursmodules = $DB->get_records('kursmodule', ['course' => $maincourseid], '', 'id');
        foreach ($kursmodules as $kursmodule) {
            foreach (self::get_links((int) $kursmodule->id, true) as $link) {
                enrolment_manager::ensure_enrolled($link, $userid);
            }
        }
    }

    /**
     * Reagiert auf das Verlassen/die Ausschreibung aus dem Hauptkurs:
     * entfernt die Person aus allen darueber verwalteten Einschreibungen.
     *
     * @param int $maincourseid
     * @param int $userid
     * @return void
     */
    public static function sync_user_leave(int $maincourseid, int $userid): void {
        global $DB;

        $kursmodules = $DB->get_records('kursmodule', ['course' => $maincourseid], '', 'id');
        foreach ($kursmodules as $kursmodule) {
            foreach (self::get_links((int) $kursmodule->id) as $link) {
                enrolment_manager::remove_tracked_enrolment((int) $link->id, $userid, (int) $link->courseid);
            }
        }
    }

    /**
     * Vollstaendiger Soll/Ist-Abgleich fuer einen aktiven Link: schreibt
     * fehlende Schueler/innen ein UND entfernt Einschreibungen von
     * Personen, die inzwischen keine Schueler/innen des Hauptkurses mehr
     * sind. Dient als Sicherheitsnetz fuer die geplante Aufgabe, falls
     * einzelne Events verpasst wurden.
     *
     * @param \stdClass $link
     * @return void
     */
    public static function reconcile_link(\stdClass $link): void {
        $shouldids = self::get_student_ids_in_main_course($link->kursmoduleid);
        $shouldidsflip = array_flip($shouldids);

        foreach ($shouldids as $userid) {
            enrolment_manager::ensure_enrolled($link, $userid);
        }

        foreach (enrolment_manager::get_tracked_users($link->id) as $tracked) {
            if (!isset($shouldidsflip[(int) $tracked->userid])) {
                enrolment_manager::remove_tracked_enrolment($link->id, (int) $tracked->userid, (int) $link->courseid);
            }
        }
    }

    /**
     * userids aller Personen mit der Rolle "student" im Hauptkurs einer
     * Kursmodule-Instanz (aktive Einschreibung vorausgesetzt).
     *
     * @param int $kursmoduleid
     * @return int[]
     */
    public static function get_student_ids_in_main_course(int $kursmoduleid): array {
        global $DB;

        $kursmodule = $DB->get_record('kursmodule', ['id' => $kursmoduleid], 'course', MUST_EXIST);
        $context = \context_course::instance($kursmodule->course);
        $roleid = enrolment_manager::resolve_roleid('student');

        $users = get_role_users($roleid, $context, false, 'u.id', 'u.id ASC', true);
        return array_map('intval', array_keys($users));
    }

    /**
     * Prueft, ob eine Person im Hauptkurs (aktiv) die Rolle "student" hat.
     *
     * @param int $courseid
     * @param int $userid
     * @return bool
     */
    private static function is_student_in_course(int $courseid, int $userid): bool {
        $context = \context_course::instance($courseid);
        $roleid = enrolment_manager::resolve_roleid('student');
        $users = get_role_users($roleid, $context, false, 'u.id', null, true);
        return isset($users[$userid]);
    }
}
