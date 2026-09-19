<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Upgrade-Schritte fuer mod_kursmodule.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @param int $oldversion
 * @return bool
 */
function xmldb_kursmodule_upgrade($oldversion) {
    // Erste Version - noch keine Upgrade-Schritte notwendig.
    return true;
}
