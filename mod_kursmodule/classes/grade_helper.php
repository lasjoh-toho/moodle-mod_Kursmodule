<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_kursmodule;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/grade/grade_item.php');
require_once($CFG->libdir . '/grade/grade_grade.php');

/**
 * Liest Bewertungen aus den Gradebooks der verlinkten Kurse fuer die
 * Bewertungsuebersicht (grades.php) und den Excel-Export aus.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade_helper {

    /**
     * Gesamtnote je Kurs und Person.
     *
     * @param int[] $courseids
     * @param int[] $userids
     * @return array [courseid][userid] => formatierte Note (string)
     */
    public static function get_overall_grades(array $courseids, array $userids): array {
        $result = [];

        foreach ($courseids as $courseid) {
            $result[$courseid] = [];
            foreach ($userids as $userid) {
                $coursegrade = grade_get_course_grade($userid, $courseid);
                $result[$courseid][$userid] = ($coursegrade && $coursegrade->str_grade !== null)
                    ? $coursegrade->str_grade
                    : get_string('gradesnogradeitem', 'mod_kursmodule');
            }
        }

        return $result;
    }

    /**
     * Einzelbewertungen (alle bewertbaren Aktivitaeten) je Kurs.
     *
     * @param int[] $courseids
     * @param int[] $userids
     * @return array [courseid] => ['items' => [itemid => name], 'grades' => [userid][itemid] => formatierte Note]
     */
    public static function get_detailed_grades(array $courseids, array $userids): array {
        $result = [];

        foreach ($courseids as $courseid) {
            $items = \grade_item::fetch_all(['courseid' => $courseid, 'itemtype' => 'mod']);
            $items = $items ?: [];

            $itemnames = [];
            $grades = [];
            foreach ($items as $item) {
                $itemnames[$item->id] = $item->get_name();
            }

            foreach ($userids as $userid) {
                foreach ($items as $item) {
                    $gradegrade = \grade_grade::fetch(['itemid' => $item->id, 'userid' => $userid]);
                    if ($gradegrade) {
                        $grades[$userid][$item->id] = grade_format_gradevalue(
                            $gradegrade->finalgrade,
                            $item,
                            true
                        );
                    } else {
                        $grades[$userid][$item->id] = get_string('gradesnogradeitem', 'mod_kursmodule');
                    }
                }
            }

            $result[$courseid] = ['items' => $itemnames, 'grades' => $grades];
        }

        return $result;
    }
}
