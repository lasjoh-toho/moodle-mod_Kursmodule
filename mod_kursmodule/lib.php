<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Kern-Callbacks fuer mod_kursmodule.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Gibt an, welche Features dieses Modul unterstuetzt.
 *
 * @param string $feature FEATURE_xx Konstante
 * @return mixed true/false/null
 */
function kursmodule_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_PURPOSE:
            return defined('MOD_PURPOSE_CONTENT') ? MOD_PURPOSE_CONTENT : null;
        default:
            return null;
    }
}

/**
 * Legt eine neue Kursmodule-Instanz an.
 *
 * @param stdClass $data
 * @param mixed $mform
 * @return int id der neuen Instanz
 */
function kursmodule_add_instance($data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;

    $data->id = $DB->insert_record('kursmodule', $data);

    return $data->id;
}

/**
 * Aktualisiert eine bestehende Kursmodule-Instanz.
 *
 * @param stdClass $data
 * @param mixed $mform
 * @return bool
 */
function kursmodule_update_instance($data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    return $DB->update_record('kursmodule', $data);
}

/**
 * Loescht eine Kursmodule-Instanz inkl. aller Links und synct saemtliche
 * automatisch erzeugten Einschreibungen ab (kein verwaistes Enrolment).
 *
 * @param int $id
 * @return bool
 */
function kursmodule_delete_instance($id) {
    global $DB;

    $kursmodule = $DB->get_record('kursmodule', ['id' => $id]);
    if (!$kursmodule) {
        return false;
    }

    $links = $DB->get_records('kursmodule_link', ['kursmoduleid' => $id]);
    foreach ($links as $link) {
        \mod_kursmodule\link_manager::remove_link($link->id);
    }

    $DB->delete_records('kursmodule', ['id' => $id]);

    return true;
}

/**
 * Liefert die Icon-Zuordnung fuer den Aktivitaets-Index/das Kurs-Grid.
 *
 * @param cm_info $cm
 * @return string
 */
function kursmodule_get_coursemodule_info($cm) {
    global $DB;

    $kursmodule = $DB->get_record('kursmodule', ['id' => $cm->instance], 'id, name, intro, introformat');
    if (!$kursmodule) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $kursmodule->name;

    if ($cm->showdescription) {
        $info->content = format_module_intro('kursmodule', $kursmodule, $cm->id, false);
    }

    return $info;
}

/**
 * Zeigt die Kursverknuepfungen direkt auf der Kursseite an (unterhalb des
 * Aktivitaetsnamens), damit Lernende und Trainer/innen sie sehen, ohne die
 * Aktivitaet erst oeffnen zu muessen. Trainer/innen mit Verwalten-Recht
 * sehen darunter zusaetzlich einen "Verwalten"-Button.
 *
 * @param cm_info $cm
 * @return void
 */
function kursmodule_cm_info_view(cm_info $cm) {
    global $DB;

    $kursmodule = $DB->get_record('kursmodule', ['id' => $cm->instance]);
    if (!$kursmodule) {
        return;
    }

    $context = context_module::instance($cm->id);
    $canmanage = has_capability('mod/kursmodule:manage', $context);

    $html = \mod_kursmodule\link_renderer::render_table(
        (int) $kursmodule->id,
        $context,
        $cm->id,
        true,
        $canmanage,
        true
    );

    $cm->set_content($html, true);
}

/**
 * Ermittelt die URL des Kursbilds eines Kurses (Dateibereich
 * 'course'/'overviewfiles', itemid 0) - derselbe Mechanismus, den auch
 * die Kurskachel-Ansicht auf dem Dashboard nutzt. Direkter Zugriff auf
 * die File-API statt einer Wrapper-Funktion, deren Signatur sich
 * zwischen Moodle-Versionen unterscheidet.
 *
 * @param int $courseid
 * @return moodle_url|null
 */
function kursmodule_get_courseimage_url(int $courseid): ?moodle_url {
    $coursecontext = context_course::instance($courseid, IGNORE_MISSING);
    if (!$coursecontext) {
        return null;
    }

    $fs = get_file_storage();
    $files = $fs->get_area_files($coursecontext->id, 'course', 'overviewfiles', 0, 'sortorder DESC, id ASC', false);

    foreach ($files as $file) {
        $mimetype = $file->get_mimetype();
        if ($mimetype && strpos($mimetype, 'image/') === 0) {
            return moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            );
        }
    }

    return null;
}

/**
 * Datei-Zugriff fuer Banner-Bilder (Dateibereich 'linkimage', itemid = link id).
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function kursmodule_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    if ($filearea !== 'linkimage') {
        return false;
    }

    $itemid = (int) array_shift($args);
    $link = $DB->get_record('kursmodule_link', ['id' => $itemid, 'kursmoduleid' => $cm->instance]);
    if (!$link) {
        return false;
    }

    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_kursmodule', 'linkimage', $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 86400, 0, $forcedownload, $options);
}
