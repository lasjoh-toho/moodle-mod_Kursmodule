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

        $courses = $DB->get_records_select(
            'course',
            'id <> 1 AND id <> ?',
            [$excludecourseid],
            'fullname ASC',
            'id, fullname, shortname'
        );

        // Von Admins festgelegte Einschraenkungen, welche Kurse ueberhaupt
        // verlinkt werden duerfen (siehe settings.php) - nicht optional
        // fuer Trainer/innen, daher hier ohne Checkbox automatisch
        // angewendet. Website-Admins sind ausgenommen (course_filter::apply_restrictions()).
        $courses = \mod_kursmodule\course_filter::apply_restrictions($courses, $excludecourseid, $currentcourseid, (int) $USER->id);

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
        global $DB, $USER;

        $errors = parent::validation($data, $files);

        $courseid = (int) ($data['courseid'] ?? 0);
        if (empty($courseid) || !$DB->record_exists('course', ['id' => $courseid])) {
            $errors['courseid'] = get_string('errorcourseinvalid', 'mod_kursmodule');
            return $errors;
        }

        // Serverseitig dieselbe Einschraenkung wie in definition() erzwingen -
        // die gefilterte Auswahlliste allein waere keine echte Sicherung
        // gegen eine manuell zusammengebaute Anfrage.
        $excludecourseid = $this->_customdata['excludecourseid'] ?? 0;
        $currentcourseid = (int) ($this->_customdata['currentcourseid'] ?? 0);

        if ($courseid !== $currentcourseid) {
            $courserecord = $DB->get_record('course', ['id' => $courseid], 'id, fullname, shortname');
            $allowed = \mod_kursmodule\course_filter::apply_restrictions(
                [$courseid => $courserecord],
                $excludecourseid,
                $currentcourseid,
                (int) $USER->id
            );
            if (!isset($allowed[$courseid])) {
                $errors['courseid'] = get_string('errorcoursenotallowed', 'mod_kursmodule');
            }
        }

        return $errors;
    }
}
