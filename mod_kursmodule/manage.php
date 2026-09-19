<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Verwaltungsseite fuer Kursverknuepfungen (Banner) einer
 * Kursmodule-Instanz: anlegen, bearbeiten, entfernen, sortieren,
 * als "aktuell" markieren.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/kursmodule/classes/form/link_form.php');

use mod_kursmodule\link_manager;

$cmid = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$linkid = optional_param('linkid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('kursmodule', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$kursmodule = $DB->get_record('kursmodule', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/kursmodule:manage', $context);

$pageurl = new moodle_url('/mod/kursmodule/manage.php', ['id' => $cmid]);
$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('manage_title', 'mod_kursmodule', format_string($kursmodule->name)));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->css(new moodle_url('/mod/kursmodule/styles.css'));
$PAGE->requires->js(new moodle_url('/mod/kursmodule/js/manage.js'), true);

// Loeschen (mit Bestaetigung).
if ($action === 'delete' && $linkid) {
    require_sesskey();
    $link = $DB->get_record('kursmodule_link', ['id' => $linkid, 'kursmoduleid' => $kursmodule->id], '*', MUST_EXIST);
    $targetcourse = $DB->get_record('course', ['id' => $link->courseid], 'fullname');

    if (optional_param('confirm', 0, PARAM_BOOL)) {
        link_manager::remove_link($linkid);
        redirect($pageurl, get_string('linkdeleted', 'mod_kursmodule'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('deletelink_confirm', 'mod_kursmodule', $targetcourse ? format_string($targetcourse->fullname) : '?'),
        new moodle_url($pageurl, ['action' => 'delete', 'linkid' => $linkid, 'confirm' => 1, 'sesskey' => sesskey()]),
        $pageurl
    );
    echo $OUTPUT->footer();
    exit;
}

// Als "aktuell" markieren.
if ($action === 'setcurrent' && $linkid) {
    require_sesskey();
    link_manager::set_current($kursmodule->id, $linkid);
    redirect($pageurl, get_string('currentset', 'mod_kursmodule'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// Formular fuer Anlegen/Bearbeiten.
$editingid = 0;
if ($action === 'edit' && $linkid) {
    $editingid = $linkid;
} else if ($action === 'add') {
    $editingid = 0;
}

$showform = in_array($action, ['add', 'edit'], true);
$form = null;

if ($showform) {
    $existinglink = $editingid ? $DB->get_record('kursmodule_link', ['id' => $editingid, 'kursmoduleid' => $kursmodule->id], '*', MUST_EXIST) : null;

    $draftitemid = file_get_submitted_draft_itemid('imagefile');
    if ($existinglink) {
        file_prepare_draft_area($draftitemid, $context->id, 'mod_kursmodule', 'linkimage', $existinglink->id, ['subdirs' => 0, 'maxfiles' => 1]);
    } else {
        file_prepare_draft_area($draftitemid, null, 'mod_kursmodule', 'linkimage', null, ['subdirs' => 0, 'maxfiles' => 1]);
    }

    $form = new \mod_kursmodule\form\link_form(null, [
        'excludecourseid' => $course->id,
        'linkid' => $editingid,
        'cmid' => $cmid,
    ]);

    $formdefaults = ['cmid' => $cmid, 'linkid' => $editingid, 'imagefile' => $draftitemid];
    if ($existinglink) {
        $formdefaults['courseid'] = $existinglink->courseid;
        $formdefaults['title'] = $existinglink->title;
        $formdefaults['enrolrole'] = $existinglink->enrolrole;
        $formdefaults['active'] = $existinglink->active;
    }
    $form->set_data($formdefaults);

    if ($form->is_cancelled()) {
        redirect($pageurl);
    } else if ($data = $form->get_data()) {
        if ($data->linkid) {
            $updatedata = (object) [
                'courseid' => $data->courseid,
                'title' => trim($data->title ?? ''),
                'enrolrole' => $data->enrolrole,
                'active' => !empty($data->active) ? 1 : 0,
            ];
            link_manager::update_link($data->linkid, $updatedata);
            $newlinkid = $data->linkid;
            $successmsg = get_string('linkupdated', 'mod_kursmodule');
        } else {
            $newlinkid = link_manager::add_link($kursmodule->id, $data->courseid, trim($data->title ?? ''), $data->enrolrole);
            if (empty($data->active)) {
                link_manager::update_link($newlinkid, (object) ['active' => 0]);
            }
            $successmsg = get_string('linkadded', 'mod_kursmodule');
        }

        file_save_draft_area_files(
            $data->imagefile,
            $context->id,
            'mod_kursmodule',
            'linkimage',
            $newlinkid,
            ['subdirs' => 0, 'maxfiles' => 1]
        );

        redirect($pageurl, $successmsg, null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manage_title', 'mod_kursmodule', format_string($kursmodule->name)));

if ($showform) {
    $form->display();
    echo html_writer::div(
        html_writer::link($pageurl, get_string('cancel', 'mod_kursmodule')),
        'mt-2'
    );
    echo $OUTPUT->footer();
    exit;
}

$links = link_manager::get_links($kursmodule->id);

echo html_writer::start_div('kursmodule-manage-toolbar mb-3');
echo html_writer::link(
    new moodle_url($pageurl, ['action' => 'add']),
    get_string('addlink', 'mod_kursmodule'),
    ['class' => 'btn btn-primary']
);
echo html_writer::link(
    new moodle_url('/mod/kursmodule/view.php', ['id' => $cmid]),
    get_string('backtoactivity', 'mod_kursmodule'),
    ['class' => 'btn btn-secondary ml-2']
);
echo html_writer::end_div();

if (empty($links)) {
    echo $OUTPUT->notification(get_string('nolinksyet', 'mod_kursmodule'), 'info');
} else {
    echo html_writer::start_tag('ul', [
        'id' => 'kursmodule-link-list',
        'class' => 'list-group',
        'data-cmid' => $cmid,
        'data-sesskey' => sesskey(),
        'data-reorderurl' => (new moodle_url('/mod/kursmodule/ajax.php'))->out(false),
    ]);
    foreach ($links as $link) {
        $targetcourse = $DB->get_record('course', ['id' => $link->courseid], 'id, fullname');
        $title = $link->title !== null && $link->title !== '' ? $link->title : ($targetcourse ? format_string($targetcourse->fullname) : '?');

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_kursmodule', 'linkimage', $link->id, 'itemid', false);
        $imageurl = null;
        foreach ($files as $file) {
            $imageurl = moodle_url::make_pluginfile_url(
                $context->id, 'mod_kursmodule', 'linkimage', $link->id, $file->get_filepath(), $file->get_filename()
            );
            break;
        }

        $roleclass = $link->enrolrole === 'guest' ? 'badge-secondary' : 'badge-primary';
        $rolelabel = get_string('role_' . $link->enrolrole, 'mod_kursmodule');

        echo html_writer::start_tag('li', [
            'class' => 'list-group-item d-flex align-items-center kursmodule-link-row' . ($link->active ? '' : ' kursmodule-link-inactive'),
            'data-linkid' => $link->id,
            'draggable' => 'true',
        ]);

        echo html_writer::span('☰', 'kursmodule-drag-handle mr-3', ['title' => get_string('links', 'mod_kursmodule')]);

        if ($imageurl) {
            echo html_writer::empty_tag('img', ['src' => $imageurl, 'class' => 'kursmodule-thumb mr-3', 'alt' => '']);
        }

        echo html_writer::start_div('flex-grow-1');
        echo html_writer::tag('strong', s($title));
        echo ' ';
        echo html_writer::span($rolelabel, 'badge ' . $roleclass . ' ml-1');
        if ($link->iscurrent) {
            echo html_writer::span(get_string('currentbadge', 'mod_kursmodule'), 'badge badge-success ml-1');
        }
        if (!$link->active) {
            echo html_writer::span(get_string('active', 'mod_kursmodule') . ': ' . get_string('no'), 'badge badge-dark ml-1');
        }
        echo html_writer::end_div();

        echo html_writer::start_div('kursmodule-link-actions');
        if (!$link->iscurrent) {
            echo html_writer::link(
                new moodle_url($pageurl, ['action' => 'setcurrent', 'linkid' => $link->id, 'sesskey' => sesskey()]),
                get_string('setcurrent', 'mod_kursmodule'),
                ['class' => 'btn btn-sm btn-outline-success mr-1']
            );
        }
        echo html_writer::link(
            new moodle_url($pageurl, ['action' => 'edit', 'linkid' => $link->id]),
            get_string('editlink', 'mod_kursmodule'),
            ['class' => 'btn btn-sm btn-outline-secondary mr-1']
        );
        echo html_writer::link(
            new moodle_url($pageurl, ['action' => 'delete', 'linkid' => $link->id]),
            get_string('deletelink', 'mod_kursmodule'),
            ['class' => 'btn btn-sm btn-outline-danger']
        );
        echo html_writer::end_div();

        echo html_writer::end_tag('li');
    }
    echo html_writer::end_tag('ul');
}

echo $OUTPUT->footer();
