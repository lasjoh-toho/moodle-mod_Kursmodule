<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_kursmodule;

defined('MOODLE_INTERNAL') || die();

/**
 * Verwaltet die Kursverknuepfungs-Banner (kursmodule_link) einer
 * Kursmodule-Instanz. Die Einschreibung in einen verlinkten Unterkurs
 * erfolgt bewusst NICHT mehr automatisch fuer alle Schueler/innen auf
 * einmal, sondern erst im Moment, in dem eine Person tatsaechlich auf den
 * jeweiligen Banner klickt (siehe go.php / handle_click()) - dadurch
 * landen nur Lernende, die einen Kurs auch wirklich besuchen, dort auch
 * als eingeschrieben. Einmal erzeugt, bleibt die Einschreibung dauerhaft
 * bestehen, bis der Link deaktiviert/entfernt wird oder die Person den
 * Hauptkurs verlaesst (dann automatische Aus-Schreibung).
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
     * Legt einen neuen Kursverknuepfungs-Link an. Es wird bewusst NIEMAND
     * sofort eingeschrieben - das passiert erst per Klick (handle_click()).
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

        return $DB->insert_record('kursmodule_link', $record);
    }

    /**
     * Aktualisiert einen Link. Eine Deaktivierung oder ein Zielkurswechsel
     * schreibt betroffene, bereits per Klick eingeschriebene Personen
     * sofort wieder aus. Eine (Re-)Aktivierung oder ein Rollenwechsel
     * schreibt dagegen NIEMANDEN proaktiv neu ein - das passiert weiterhin
     * erst wieder per Klick (handle_click()).
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

    /** @var string User-Preference-Schluessel fuer den Verknuepfungs-Zwischenspeicher. */
    const CLIPBOARD_PREF = 'mod_kursmodule_clipboard';

    /**
     * Kopiert alle Links einer Instanz (Zielkurs, Titel, Rolle,
     * Aktiv-Status) in einen persoenlichen Zwischenspeicher
     * (User-Preference). Der Speicher gehoert bewusst der Person, nicht
     * dem Kurs, damit er beim Wechsel in eine andere Instanz - auch in
     * einem ganz anderen Kurs - erhalten bleibt. Banner-Bilder werden
     * nicht mitkopiert.
     *
     * @param int $kursmoduleid
     * @return int Anzahl kopierter Links
     */
    public static function copy_to_clipboard(int $kursmoduleid): int {
        $links = self::get_links($kursmoduleid);

        $data = [];
        foreach ($links as $link) {
            $data[] = [
                'courseid' => (int) $link->courseid,
                'title' => $link->title,
                'enrolrole' => $link->enrolrole,
                'active' => (int) $link->active,
            ];
        }

        set_user_preference(self::CLIPBOARD_PREF, json_encode($data));

        return count($data);
    }

    /**
     * Fuegt die im Zwischenspeicher abgelegten Links als neue Links in
     * eine (in aller Regel andere) Instanz ein. Es wird bewusst NIEMAND
     * automatisch eingeschrieben - das passiert wie immer erst per Klick.
     * Links auf inzwischen geloeschte Kurse oder auf den Hauptkurs der
     * Zielinstanz selbst werden uebersprungen.
     *
     * @param int $kursmoduleid Zielinstanz
     * @param int $excludecourseid Hauptkurs der Zielinstanz (wird nie mitverlinkt)
     * @return array{added: int, skipped: int, total: int}
     */
    public static function paste_from_clipboard(int $kursmoduleid, int $excludecourseid): array {
        global $DB;

        $raw = get_user_preferences(self::CLIPBOARD_PREF, '');
        $entries = $raw !== '' ? json_decode($raw, true) : [];
        $entries = is_array($entries) ? $entries : [];

        $added = 0;
        foreach ($entries as $entry) {
            $courseid = (int) ($entry['courseid'] ?? 0);
            if (!$courseid || $courseid === $excludecourseid || !$DB->record_exists('course', ['id' => $courseid])) {
                continue;
            }

            $title = $entry['title'] ?? null;
            $enrolrole = ($entry['enrolrole'] ?? 'student') === 'guest' ? 'guest' : 'student';

            $newlinkid = self::add_link($kursmoduleid, $courseid, $title, $enrolrole);
            if (empty($entry['active'])) {
                self::update_link($newlinkid, (object) ['active' => 0]);
            }
            $added++;
        }

        $total = count($entries);
        return ['added' => $added, 'skipped' => $total - $added, 'total' => $total];
    }

    /**
     * Wird aufgerufen, wenn eine Person tatsaechlich auf einen Banner
     * klickt (siehe go.php): schreibt sie - sofern sie im Hauptkurs die
     * Rolle "student" hat - synchron in den Zielkurs des Links ein, bevor
     * zum Kurs weitergeleitet wird. Lehrende/Verwaltende, die einen Link
     * nur zum Testen anklicken, werden bewusst NICHT eingeschrieben.
     *
     * @param \stdClass $link
     * @param int $userid
     * @return void
     */
    public static function handle_click(\stdClass $link, int $userid): void {
        if ((int) $link->active !== 1) {
            return;
        }
        if (!self::is_student_in_course((int) self::get_main_courseid($link->kursmoduleid), $userid)) {
            return;
        }
        enrolment_manager::ensure_enrolled($link, $userid);
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
     * Sicherheitsnetz fuer die geplante Aufgabe: entfernt Einschreibungen
     * von Personen, die inzwischen keine Schueler/innen des Hauptkurses
     * mehr sind (z. B. weil ein Ausschreibe-Event verpasst wurde). Schreibt
     * bewusst NIEMANDEN neu ein - das passiert ausschliesslich per Klick.
     *
     * @param \stdClass $link
     * @return void
     */
    public static function reconcile_link(\stdClass $link): void {
        $shouldidsflip = array_flip(self::get_student_ids_in_main_course($link->kursmoduleid));

        foreach (enrolment_manager::get_tracked_users($link->id) as $tracked) {
            if (!isset($shouldidsflip[(int) $tracked->userid])) {
                enrolment_manager::remove_tracked_enrolment($link->id, (int) $tracked->userid, (int) $link->courseid);
            }
        }
    }

    /**
     * @param int $kursmoduleid
     * @return int
     */
    private static function get_main_courseid(int $kursmoduleid): int {
        global $DB;
        return (int) $DB->get_field('kursmodule', 'course', ['id' => $kursmoduleid], MUST_EXIST);
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
