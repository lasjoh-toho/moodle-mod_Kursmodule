<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_kursmodule;

defined('MOODLE_INTERNAL') || die();

/**
 * Setzt durch, welche Kurse eine Person ueberhaupt verlinken darf. Die
 * Einschraenkungen selbst werden ausschliesslich von Admins ueber die
 * Plugin-Einstellungen (settings.php) festgelegt - Trainer/innen koennen
 * sie weder einsehen noch abschalten, sie greifen automatisch sowohl in
 * der Kursauswahl des Formulars (classes/form/link_form.php::definition())
 * als auch serverseitig bei der Validierung (::validation()).
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_filter {

    /**
     * Welche Rollen-Archetypen einer Person erlauben, einen Kurs zu
     * verlinken. "editingteacher" (Trainer/in mit Bearbeitungsrecht) ist
     * die feste Grundvoraussetzung und nicht abschaltbar; die beiden
     * Admin-Einstellungen erweitern sie optional auf schwaechere Rollen.
     *
     * @return string[]
     */
    public static function get_allowed_archetypes(): array {
        $archetypes = ['editingteacher'];

        if ((bool) get_config('mod_kursmodule', 'restrictroleteacher')) {
            $archetypes[] = 'teacher';
        }
        if ((bool) get_config('mod_kursmodule', 'restrictrolestudent')) {
            $archetypes[] = 'student';
        }

        return $archetypes;
    }

    /**
     * Die im Adminpanel hinterlegten Kurs-Zusatzfeld-Kurznamen, aus einer
     * kommagetrennten Liste geparst (Leerzeichen und leere Eintraege
     * werden entfernt).
     *
     * @return string[]
     */
    public static function get_field_shortnames(): array {
        $raw = (string) get_config('mod_kursmodule', 'fieldfiltershortnames');

        return array_values(array_filter(array_map('trim', explode(',', $raw)), fn($s) => $s !== ''));
    }

    /**
     * Wendet die von Admins konfigurierten Einschraenkungen auf eine
     * Liste von Kursen an. Website-Admins (is_siteadmin()) sind davon
     * immer ausgenommen - die Einschraenkung gilt fuer Trainer/innen,
     * nicht fuer die Verwaltung des Systems. Der aktuell verlinkte Kurs
     * bleibt beim Bearbeiten einer bestehenden Verknuepfung immer
     * erhalten, damit eine spaetere Verschaerfung der Einstellungen kein
     * Speichern unbeabsichtigt auf einen anderen Zielkurs umbiegt.
     *
     * @param \stdClass[] $courses Kursdatensaetze, indiziert nach Kurs-ID
     * @param int $maincourseid Hauptkurs der Kursmodule-Aktivitaet
     * @param int $currentcourseid aktuell verlinkter Kurs beim Bearbeiten (0 beim Anlegen)
     * @param int $userid
     * @param bool $onlyteachingroles true blendet zusaetzlich eine evtl. erlaubte
     *        Teilnehmer/in-Rolle wieder aus - reine Anzeige-Verfeinerung fuer die
     *        optionale Checkbox im Formular, KEINE eigene Sicherheitsgrenze
     *        (siehe link_form::validation(), die diesen Parameter bewusst nie setzt).
     * @return \stdClass[]
     */
    public static function apply_restrictions(
        array $courses,
        int $maincourseid,
        int $currentcourseid,
        int $userid,
        bool $onlyteachingroles = false
    ): array {
        global $DB;

        if (is_siteadmin($userid)) {
            return $courses;
        }

        $archetypes = self::get_allowed_archetypes();
        if ($onlyteachingroles) {
            $archetypes = array_diff($archetypes, ['student']);
        }

        $allowed = array_flip(self::get_courseids_by_roles($userid, $archetypes));
        foreach (array_keys($courses) as $cid) {
            if ($cid !== $currentcourseid && !isset($allowed[$cid])) {
                unset($courses[$cid]);
            }
        }

        // Kurs-Zusatzfelder: ein Kurs muss bei JEDEM konfigurierten Feld
        // denselben Wert wie der Hauptkurs haben (UND-Verknuepfung).
        foreach (self::get_field_shortnames() as $shortname) {
            $mainvalue = self::get_custom_field_value($maincourseid, $shortname);
            $matching = $mainvalue !== null
                ? array_flip(self::get_courseids_with_field_value($shortname, $mainvalue))
                : [];
            foreach (array_keys($courses) as $cid) {
                if ($cid !== $currentcourseid && !isset($matching[$cid])) {
                    unset($courses[$cid]);
                }
            }
        }

        if ($currentcourseid && !isset($courses[$currentcourseid])) {
            $currentcourse = $DB->get_record('course', ['id' => $currentcourseid], 'id, fullname, shortname');
            if ($currentcourse) {
                $courses[$currentcourseid] = $currentcourse;
            }
        }

        return $courses;
    }

    /**
     * Kurse, in denen eine Person mindestens eine der angegebenen
     * Rollen-Archetypen innehat (z. B. 'teacher', 'editingteacher').
     *
     * @param int $userid
     * @param string[] $archetypes
     * @return int[] Kurs-IDs
     */
    public static function get_courseids_by_roles(int $userid, array $archetypes): array {
        global $DB;

        if (empty($archetypes)) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal($archetypes, SQL_PARAMS_NAMED, 'arch');
        $sql = "SELECT DISTINCT c.id
                  FROM {role_assignments} ra
                  JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :contextlevel
                  JOIN {course} c ON c.id = ctx.instanceid
                  JOIN {role} r ON r.id = ra.roleid
                 WHERE ra.userid = :userid AND r.archetype $insql";
        $params = array_merge(['contextlevel' => CONTEXT_COURSE, 'userid' => $userid], $inparams);

        return array_keys($DB->get_records_sql($sql, $params));
    }

    /**
     * Liest den Wert eines Kurs-Zusatzfeldes (Custom Course Field) fuer
     * einen bestimmten Kurs aus. Unterstuetzt Text- und Auswahl-Felder
     * zuverlaessig; andere Feldtypen (z. B. Checkbox) nicht.
     *
     * @param int $courseid
     * @param string $shortname Kurzname des Zusatzfeldes
     * @return string|null null, wenn der Kurs keinen Wert fuer dieses Feld hat
     */
    public static function get_custom_field_value(int $courseid, string $shortname): ?string {
        global $DB;

        $sql = "SELECT cfd.value, cfd.shortcharvalue
                  FROM {customfield_data} cfd
                  JOIN {customfield_field} cff ON cff.id = cfd.fieldid
                  JOIN {customfield_category} cfc ON cfc.id = cff.categoryid
                 WHERE cfc.component = 'core_course' AND cfc.area = 'course'
                   AND cff.shortname = :shortname AND cfd.instanceid = :courseid";

        $record = $DB->get_record_sql($sql, ['shortname' => $shortname, 'courseid' => $courseid]);
        if (!$record) {
            return null;
        }

        $value = ($record->shortcharvalue !== null && $record->shortcharvalue !== '') ? $record->shortcharvalue : $record->value;
        return ($value !== null && $value !== '') ? (string) $value : null;
    }

    /**
     * Alle Kurse, deren Zusatzfeld $shortname genau den Wert $value hat.
     *
     * @param string $shortname
     * @param string $value
     * @return int[] Kurs-IDs
     */
    public static function get_courseids_with_field_value(string $shortname, string $value): array {
        global $DB;

        $sql = "SELECT DISTINCT cfd.instanceid
                  FROM {customfield_data} cfd
                  JOIN {customfield_field} cff ON cff.id = cfd.fieldid
                  JOIN {customfield_category} cfc ON cfc.id = cff.categoryid
                 WHERE cfc.component = 'core_course' AND cfc.area = 'course'
                   AND cff.shortname = :shortname
                   AND (cfd.shortcharvalue = :value1 OR cfd.value = :value2)";

        return array_keys($DB->get_records_sql($sql, ['shortname' => $shortname, 'value1' => $value, 'value2' => $value]));
    }
}
