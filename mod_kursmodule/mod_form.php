<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Instanz-Einstellungsformular fuer mod_kursmodule.
 *
 * Die Kursverknuepfungen selbst (Banner, Reihenfolge, Rollen) werden nicht
 * hier, sondern ueber die eigene Verwaltungsseite (manage.php) gepflegt,
 * da dort Drag & Drop und Bild-Upload pro Link deutlich praktikabler sind
 * als in einem repeat_elements()-Formular.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_kursmodule_mod_form extends moodleform_mod {

    /**
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general'));

        $mform->addElement('text', 'name', get_string('kursmodulename', 'mod_kursmodule'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        if (!empty($this->current->id)) {
            $mform->addElement(
                'static',
                'managelinks',
                get_string('links', 'mod_kursmodule'),
                get_string('managelinks_hint', 'mod_kursmodule')
            );
        }

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
