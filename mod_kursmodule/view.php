<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Schueler/innen-Ansicht: zeigt die aktiven Kursverknuepfungen als
 * Banner-Leiste. Ein Klick fuehrt direkt in den verlinkten Kurs - die
 * Einschreibung ist zu diesem Zeitpunkt bereits automatisch erfolgt.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/kursmodule/lib.php');

use mod_kursmodule\link_manager;

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('kursmodule', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$kursmodule = $DB->get_record('kursmodule', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/kursmodule:view', $context);

$event = \core\event\course_module_viewed::create([
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

$links = link_manager::get_links($kursmodule->id, true);

if (empty($links)) {
    echo $OUTPUT->notification(get_string('viewnolinks', 'mod_kursmodule'), 'info');
    echo $OUTPUT->footer();
    exit;
}

echo html_writer::start_div('kursmodule-banner-list');
foreach ($links as $link) {
    $targetcourse = $DB->get_record('course', ['id' => $link->courseid], 'id, fullname, visible');
    if (!$targetcourse) {
        continue;
    }

    $title = $link->title !== null && $link->title !== '' ? $link->title : format_string($targetcourse->fullname);

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_kursmodule', 'linkimage', $link->id, 'itemid', false);
    $imageurl = null;
    foreach ($files as $file) {
        $imageurl = moodle_url::make_pluginfile_url(
            $context->id, 'mod_kursmodule', 'linkimage', $link->id, $file->get_filepath(), $file->get_filename()
        );
        break;
    }
    // Kein Fallback auf ein automatisch ermitteltes Kursbild - dessen
    // interne API unterscheidet sich zu sehr zwischen Moodle-Versionen.
    // Ohne eigenes Banner-Bild wird stattdessen ein einfacher Platzhalter
    // mit dem Anfangsbuchstaben des Kursnamens gezeigt.

    // Fuehrt ueber go.php: dort wird erst im Moment des Klicks eingeschrieben.
    $courseurl = new moodle_url('/mod/kursmodule/go.php', ['id' => $id, 'linkid' => $link->id]);
    $rowclass = 'kursmodule-banner';
    $rowclass .= $link->iscurrent ? ' kursmodule-banner-current' : ' kursmodule-banner-dim';

    echo html_writer::start_tag('a', ['href' => $courseurl, 'class' => $rowclass]);
    if ($imageurl) {
        echo html_writer::empty_tag('img', ['src' => $imageurl, 'class' => 'kursmodule-banner-img', 'alt' => '']);
    } else {
        $initial = core_text::strtoupper(core_text::substr(format_string($targetcourse->fullname), 0, 1));
        echo html_writer::div(s($initial), 'kursmodule-banner-img kursmodule-banner-img-placeholder');
    }
    echo html_writer::span(s($title), 'kursmodule-banner-title');
    if ($link->iscurrent) {
        echo html_writer::span(get_string('currentbadge', 'mod_kursmodule'), 'kursmodule-banner-badge');
    }
    echo html_writer::end_tag('a');
}
echo html_writer::end_div();

echo $OUTPUT->footer();
