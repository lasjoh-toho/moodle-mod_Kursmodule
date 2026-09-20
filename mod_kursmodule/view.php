<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Schueler/innen-Ansicht: zeigt die aktiven Kursverknuepfungen als
 * Banner-Leiste. Ein Klick fuehrt ueber go.php in den verlinkten Kurs -
 * die Einschreibung entsteht dort erst im Moment des Klicks.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/kursmodule/lib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('kursmodule', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$kursmodule = $DB->get_record('kursmodule', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/kursmodule:view', $context);

$event = \mod_kursmodule\event\course_module_viewed::create([
    'objectid' => $kursmodule->id,
    'context' => $context,
]);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('kursmodule', $kursmodule);
$event->trigger();

$pageurl = new moodle_url('/mod/kursmodule/view.php', ['id' => $id]);
$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_title(format_string($kursmodule->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_activity_record($kursmodule);
$PAGE->requires->css(new moodle_url('/mod/kursmodule/styles.css'));

$canmanage = has_capability('mod/kursmodule:manage', $context);
$cangrades = has_capability('mod/kursmodule:viewgrades', $context);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($kursmodule->name));

if ($kursmodule->intro) {
    echo $OUTPUT->box(format_module_intro('kursmodule', $kursmodule, $cm->id), 'generalbox mod_introbox');
}

if ($canmanage) {
    echo html_writer::div(
        html_writer::link(
            new moodle_url('/mod/kursmodule/manage.php', ['id' => $id]),
            get_string('managelinks', 'mod_kursmodule'),
            ['class' => 'btn btn-secondary']
        ),
        'mb-3'
    );
}

// Tabs (nur fuer Lehrende mit Bewertungszugriff relevant).
if ($cangrades) {
    $tabs = [
        new tabobject('links', $pageurl->out(false), get_string('tablinks', 'mod_kursmodule')),
        new tabobject('grades', (new moodle_url('/mod/kursmodule/grades.php', ['id' => $id]))->out(false), get_string('tabgrades', 'mod_kursmodule')),
    ];
    echo $OUTPUT->tabtree($tabs, 'links');
}

echo \mod_kursmodule\link_renderer::render_table($kursmodule->id, $context, $id, true, false);

echo $OUTPUT->footer();
