<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Upgrade-Schritte fuer enrol_kursmodule.
 *
 * @package     enrol_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @param int $oldversion
 * @return bool
 */
function xmldb_enrol_kursmodule_upgrade($oldversion) {
    // Erste Version - noch keine Upgrade-Schritte notwendig.
    return true;
}
