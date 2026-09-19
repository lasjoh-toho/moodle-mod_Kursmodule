<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Klick-Ziel eines Banners: schreibt die Person (sofern sie im Hauptkurs
 * die Rolle "student" hat) synchron in den verlinkten Kurs ein und leitet
 * anschliessend dorthin weiter. Die Einschreibung entsteht bewusst erst
 * hier und nicht schon vorher im Hintergrund.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use mod_kursmodule\link_manager;

$id = required_param('id', PARAM_INT);
$linkid = required_param('linkid', PARAM_INT);

$cm = get_coursemodule_from_id('kursmodule', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$kursmodule = $DB->get_record('kursmodule', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/kursmodule:view', $context);

$link = $DB->get_record('kursmodule_link', ['id' => $linkid, 'kursmoduleid' => $kursmodule->id]);
if (!$link || !$link->active) {
    redirect(
        new moodle_url('/mod/kursmodule/view.php', ['id' => $id]),
        get_string('viewnolinks', 'mod_kursmodule'),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

$targetcourse = $DB->get_record('course', ['id' => $link->courseid]);
if (!$targetcourse) {
    redirect(new moodle_url('/mod/kursmodule/view.php', ['id' => $id]));
}

link_manager::handle_click($link, (int) $USER->id);

redirect(new moodle_url('/course/view.php', ['id' => $targetcourse->id]));
