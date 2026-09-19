<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_kursmodule\event;

defined('MOODLE_INTERNAL') || die();

/**
 * "Aktivitaet angesehen"-Event fuer mod_kursmodule.
 *
 * \core\event\course_module_viewed ist in aktuellen Moodle-Versionen eine
 * abstrakte Klasse - jedes Aktivitaetsmodul muss eine eigene, konkrete
 * Unterklasse mit init() bereitstellen. Ohne diese Datei bricht der
 * Aufruf in view.php mit "Cannot instantiate abstract class" ab.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_module_viewed extends \core\event\course_module_viewed {

    /**
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'kursmodule';
    }
}
