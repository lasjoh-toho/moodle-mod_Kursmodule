<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_kursmodule\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Formular zum Anlegen/Bearbeiten einer einzelnen Kursverknuepfung.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class link_form extends \moodleform {

    /**
     * @return void
     */
    public function definition() {
        global $DB, $USER;

        $mform = $this->_form;
        $excludecourseid = $this->_customdata['excludecourseid'] ?? 0;
        $linkid = $this->_customdata['linkid'] ?? 0;
        $cmid = $this->_customdata['cmid'];
        $currentcourseid = (int) ($this->_customdata['currentcourseid'] ?? 0);

        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'linkid', $linkid);
        $mform->setType('linkid', PARAM_INT);

        // Kurs-Filter-Checkboxen: nur sichtbar, wenn ein Admin sie in den
        // Plugin-Einstellungen freigeschaltet hat. Ueber den No-Submit-
        // Button "Anwenden" wird das Formular ohne Validierung neu
        // aufgebaut (Standard-Moodle-Muster fuer dynamische Auswahllisten) -
        // die aktuellen Haken werden deshalb per optional_param() aus dem
        // POST gelesen, nicht ueber $mform selbst.
        $enableteacherfilter = (bool) get_config('mod_kursmodule', 'enableteacherfilter');
        $fieldshortname = trim((string) get_config('mod_kursmodule', 'fieldfiltershortname'));
        $enablefieldfilter = (bool) get_config('mod_kursmodule', 'enablefieldfilter') && $fieldshortname !== '';

        if ($enableteacherfilter || $enablefieldfilter) {
            $mform->addElement('header', 'coursefilterheader', get_string('coursefilterheader', 'mod_kursmodule'));
            $mform->setExpanded('coursefilterheader', true);

            if ($enableteacherfilter) {
                $mform->addElement('advcheckbox', 'filterteacher', '', get_string('filterteacher', 'mod_kursmodule'));
            }
            if ($enablefieldfilter) {
                $fielddisplay = \mod_kursmodule\course_filter::get_field_display_name($fieldshortname);
                $mform->addElement('advcheckbox', 'filterfield', '', get_string('filterfield', 'mod_kursmodule', $fielddisplay));
            }

            $mform->addElement('submit', 'applyfilter', get_string('applyfilter', 'mod_kursmodule'));
            $mform->registerNoSubmitButton('applyfilter');
        }

        $filterteacher = $enableteacherfilter && optional_param('filterteacher', 0, PARAM_BOOL);
        $filterfield = $enablefieldfilter && optional_param('filterfield', 0, PARAM_BOOL);

        $courses = $DB->get_records_select(
            'course',
            'id <> 1 AND id <> ?',
            [$excludecourseid],
            'fullname ASC',
            'id, fullname, shortname'
        );

        if ($filterteacher) {
            $allowed = array_flip(\mod_kursmodule\course_filter::get_teacher_courseids($USER->id));
            foreach (array_keys($courses) as $cid) {
                if ($cid !== $currentcourseid && !isset($allowed[$cid])) {
                    unset($courses[$cid]);
                }
            }
        }

        if ($filterfield) {
            $mainvalue = \mod_kursmodule\course_filter::get_custom_field_value($excludecourseid, $fieldshortname);
            $allowed = $mainvalue !== null
                ? array_flip(\mod_kursmodule\course_filter::get_courseids_with_field_value($fieldshortname, $mainvalue))
                : [];
            foreach (array_keys($courses) as $cid) {
                if ($cid !== $currentcourseid && !isset($allowed[$cid])) {
                    unset($courses[$cid]);
                }
            }
        }

        // Beim Bearbeiten bleibt der aktuell verlinkte Kurs immer waehlbar,
        // auch wenn ein Filter ihn eigentlich herausfiltern wuerde - sonst
        // wuerde ein Speichern ungewollt den Zielkurs aendern.
        if ($currentcourseid && !isset($courses[$currentcourseid])) {
            $currentcourse = $DB->get_record('course', ['id' => $currentcourseid], 'id, fullname, shortname');
            if ($currentcourse) {
                $courses[$currentcourseid] = $currentcourse;
            }
        }

        // Leere Option voranstellen: ohne sie waehlt ein natives <select> immer
        // den ersten Eintrag automatisch vor, das Feld war dadurch beim
        // Anlegen eines neuen Links nie wirklich leer.
        $options = ['' => get_string('searchcourse', 'mod_kursmodule')];
        foreach ($courses as $course) {
            $options[$course->id] = format_string($course->fullname) . ' (' . $course->shortname . ')';
        }

        // Standard-Select mit clientseitiger Live-Suche (autocomplete), damit die
        // Liste auch bei vielen Kursen bedienbar bleibt.
        $mform->addElement('autocomplete', 'courseid', get_string('course', 'mod_kursmodule'), $options, [
            'multiple' => false,
            'noselectionstring' => get_string('searchcourse', 'mod_kursmodule'),
        ]);
        $mform->addRule('courseid', get_string('errorcourseinvalid', 'mod_kursmodule'), 'required', null, 'client');

        $mform->addElement('text', 'title', get_string('title', 'mod_kursmodule'), ['size' => '48']);
        $mform->setType('title', PARAM_TEXT);
        $mform->addHelpButton('title', 'title', 'mod_kursmodule');

        $mform->addElement('filemanager', 'imagefile', get_string('image', 'mod_kursmodule'), null, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['web_image'],
        ]);
        $mform->addHelpButton('imagefile', 'image', 'mod_kursmodule');

        $mform->addElement('select', 'enrolrole', get_string('role', 'mod_kursmodule'), [
            'student' => get_string('role_student', 'mod_kursmodule'),
            'guest' => get_string('role_guest', 'mod_kursmodule'),
        ]);
        $mform->addHelpButton('enrolrole', 'role', 'mod_kursmodule');

        $mform->addElement('advcheckbox', 'active', get_string('active', 'mod_kursmodule'));
        $mform->setDefault('active', 1);
        $mform->addHelpButton('active', 'active', 'mod_kursmodule');

        $this->add_action_buttons(true, get_string('savechanges', 'mod_kursmodule'));
    }

    /**
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        if (empty($data['courseid']) || !$DB->record_exists('course', ['id' => $data['courseid']])) {
            $errors['courseid'] = get_string('errorcourseinvalid', 'mod_kursmodule');
        }

        return $errors;
    }
}
