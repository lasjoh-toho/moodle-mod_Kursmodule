<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Bewertungs-Tab: Matrix-Ansicht (Schueler/in x verlinkter Kurs) mit
 * Gesamtnote, optional aufklappbar zu Einzelbewertungen je Aktivitaet.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

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

$pageurl = new moodle_url('/mod/kursmodule/grades.php', ['id' => $id]);
$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_title(get_string('gradestitle', 'mod_kursmodule'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->css(new moodle_url('/mod/kursmodule/styles.css'));

$links = link_manager::get_links($kursmodule->id, true);
$userids = link_manager::get_student_ids_in_main_course($kursmodule->id);
$courseids = array_values(array_unique(array_map(function($link) {
    return (int) $link->courseid;
}, $links)));

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($kursmodule->name));

$tabs = [
    new tabobject('links', (new moodle_url('/mod/kursmodule/view.php', ['id' => $id]))->out(false), get_string('tablinks', 'mod_kursmodule')),
    new tabobject('grades', $pageurl->out(false), get_string('tabgrades', 'mod_kursmodule')),
];
echo $OUTPUT->tabtree($tabs, 'grades');

echo html_writer::start_div('d-flex justify-content-between align-items-center mb-3 mt-3');

$toggleurl = new moodle_url($pageurl, ['detailed' => $detailed ? 0 : 1]);
echo html_writer::link(
    $toggleurl,
    get_string('gradesmatrixtoggle', 'mod_kursmodule'),
    ['class' => 'btn btn-outline-secondary']
);

echo html_writer::link(
    new moodle_url('/mod/kursmodule/export_grades.php', ['id' => $id, 'detailed' => $detailed]),
    get_string('gradesexport', 'mod_kursmodule'),
    ['class' => 'btn btn-primary']
);
echo html_writer::end_div();

if (empty($userids) || empty($courseids)) {
    echo $OUTPUT->notification(get_string('gradesnodata', 'mod_kursmodule'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$coursenames = [];
foreach ($DB->get_records_list('course', 'id', $courseids, '', 'id, fullname') as $c) {
    $coursenames[$c->id] = format_string($c->fullname);
}

$usernames = [];
foreach ($DB->get_records_list('user', 'id', $userids, '', 'id, ' . implode(', ', \core_user\fields::get_name_fields())) as $u) {
    $usernames[$u->id] = fullname($u);
}
asort($usernames);

if (!$detailed) {
    $grades = grade_helper::get_overall_grades($courseids, $userids);

    echo html_writer::start_tag('table', ['class' => 'generaltable table table-striped w-100']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('gradesstudent', 'mod_kursmodule'));
    foreach ($courseids as $courseid) {
        echo html_writer::tag('th', s($coursenames[$courseid] ?? '?'));
    }
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');
    foreach ($usernames as $userid => $name) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', s($name));
        foreach ($courseids as $courseid) {
            echo html_writer::tag('td', s($grades[$courseid][$userid] ?? get_string('gradesnogradeitem', 'mod_kursmodule')));
        }
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
} else {
    $detailedgrades = grade_helper::get_detailed_grades($courseids, $userids);

    foreach ($courseids as $courseid) {
        $coursedata = $detailedgrades[$courseid] ?? ['items' => [], 'grades' => []];
        echo $OUTPUT->heading(s($coursenames[$courseid] ?? '?'), 4);

        if (empty($coursedata['items'])) {
            echo $OUTPUT->notification(get_string('gradesnodata', 'mod_kursmodule'), 'info');
            continue;
        }

        echo html_writer::start_tag('table', ['class' => 'generaltable table table-striped w-100 mb-4']);
        echo html_writer::start_tag('thead');
        echo html_writer::start_tag('tr');
        echo html_writer::tag('th', get_string('gradesstudent', 'mod_kursmodule'));
        foreach ($coursedata['items'] as $itemname) {
            echo html_writer::tag('th', s($itemname));
        }
        echo html_writer::end_tag('tr');
        echo html_writer::end_tag('thead');
        echo html_writer::start_tag('tbody');
        foreach ($usernames as $userid => $name) {
            echo html_writer::start_tag('tr');
            echo html_writer::tag('td', s($name));
            foreach (array_keys($coursedata['items']) as $itemid) {
                $value = $coursedata['grades'][$userid][$itemid] ?? get_string('gradesnogradeitem', 'mod_kursmodule');
                echo html_writer::tag('td', s($value));
            }
            echo html_writer::end_tag('tr');
        }
        echo html_writer::end_tag('tbody');
        echo html_writer::end_tag('table');
    }
}

echo $OUTPUT->footer();
