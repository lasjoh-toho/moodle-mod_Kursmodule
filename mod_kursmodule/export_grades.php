<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Excel-Export der Bewertungsuebersicht (siehe grades.php). Nutzt die
 * eingebaute Moodle-Dataformat-API (lib/dataformatlib.php), die bereits
 * fuer saemtliche core-Reports verwendet wird - dadurch kein eigener
 * Excel-Code noetig.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/dataformatlib.php');

use mod_kursmodule\link_manager;
use mod_kursmodule\grade_helper;

$id = required_param('id', PARAM_INT);
$detailed = optional_param('detailed', 0, PARAM_BOOL);

$cm = get_coursemodule_from_id('kursmodule', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$kursmodule = $DB->get_record('kursmodule', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/kursmodule:viewgrades', $context);

$links = link_manager::get_links($kursmodule->id, true);
$userids = link_manager::get_student_ids_in_main_course($kursmodule->id);
$courseids = array_values(array_unique(array_map(function($link) {
    return (int) $link->courseid;
}, $links)));

$coursenames = [];
foreach ($DB->get_records_list('course', 'id', $courseids, '', 'id, fullname') as $c) {
    $coursenames[$c->id] = format_string($c->fullname);
}

$usernames = [];
foreach ($DB->get_records_list('user', 'id', $userids, '', 'id, ' . implode(', ', \core_user\fields::get_name_fields())) as $u) {
    $usernames[$u->id] = fullname($u);
}
asort($usernames);

$columns = [get_string('gradesstudent', 'mod_kursmodule')];
$rows = [];

if (!$detailed) {
    $grades = grade_helper::get_overall_grades($courseids, $userids);

    foreach ($courseids as $courseid) {
        $columns[] = $coursenames[$courseid] ?? '?';
    }
    foreach ($usernames as $userid => $name) {
        $row = [$name];
        foreach ($courseids as $courseid) {
            $row[] = $grades[$courseid][$userid] ?? get_string('gradesnogradeitem', 'mod_kursmodule');
        }
        $rows[] = $row;
    }
} else {
    $detailedgrades = grade_helper::get_detailed_grades($courseids, $userids);

    $flatitems = []; // [ [courseid, itemid, label], ... ].
    foreach ($courseids as $courseid) {
        $items = $detailedgrades[$courseid]['items'] ?? [];
        foreach ($items as $itemid => $itemname) {
            $label = ($coursenames[$courseid] ?? '?') . ': ' . $itemname;
            $flatitems[] = [$courseid, $itemid, $label];
            $columns[] = $label;
        }
    }
    foreach ($usernames as $userid => $name) {
        $row = [$name];
        foreach ($flatitems as [$courseid, $itemid, $label]) {
            $row[] = $detailedgrades[$courseid]['grades'][$userid][$itemid] ?? get_string('gradesnogradeitem', 'mod_kursmodule');
        }
        $rows[] = $row;
    }
}

$filename = clean_filename('kursmodule_' . $kursmodule->id . '_bewertungen');

download_as_dataformat($filename, 'excel', $columns, new ArrayIterator($rows));
