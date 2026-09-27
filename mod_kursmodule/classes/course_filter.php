<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_kursmodule;

defined('MOODLE_INTERNAL') || die();

/**
 * Hilfsfunktionen fuer die Kurs-Filter-Checkboxen im Verknuepfungsformular
 * (classes/form/link_form.php): welche Kurse darf eine Person ueberhaupt
 * zur Auswahl bekommen. Die Filter selbst werden erst wirksam, wenn sie
 * ueber die Plugin-Einstellungen (settings.php) freigeschaltet sind.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_filter {

    /**
     * Kurse, in denen eine Person mindestens als Trainer/in ohne
     * Bearbeitungsrecht ("teacher") eingeschrieben ist - "mindestens",
     * weil eine Trainer/in mit Bearbeitungsrecht ("editingteacher")
     * automatisch mit eingeschlossen ist.
     *
     * @param int $userid
     * @return int[] Kurs-IDs
     */
    public static function get_teacher_courseids(int $userid): array {
        global $DB;

        $sql = "SELECT DISTINCT c.id
                  FROM {role_assignments} ra
                  JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :contextlevel
                  JOIN {course} c ON c.id = ctx.instanceid
                  JOIN {role} r ON r.id = ra.roleid
                 WHERE ra.userid = :userid AND r.archetype IN ('teacher', 'editingteacher')";

        return array_keys($DB->get_records_sql($sql, ['contextlevel' => CONTEXT_COURSE, 'userid' => $userid]));
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

    /**
     * Anzeigename eines Kurs-Zusatzfeldes (fuer das Label der Filter-
     * Checkbox). Faellt auf den Kurznamen zurueck, falls das Feld nicht
     * (mehr) existiert - z. B. weil die Einstellung veraltet ist.
     *
     * @param string $shortname
     * @return string
     */
    public static function get_field_display_name(string $shortname): string {
        global $DB;

        $sql = "SELECT cff.name
                  FROM {customfield_field} cff
                  JOIN {customfield_category} cfc ON cfc.id = cff.categoryid
                 WHERE cfc.component = 'core_course' AND cfc.area = 'course' AND cff.shortname = :shortname";

        $name = $DB->get_field_sql($sql, ['shortname' => $shortname]);

        return $name !== false && $name !== null ? format_string($name) : $shortname;
    }
}
