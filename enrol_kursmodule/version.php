<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Version details fuer enrol_kursmodule.
 *
 * Begleit-Plugin zu mod_kursmodule: stellt eine eigene, isolierte
 * Einschreibemethode bereit, damit die Kursmodule-Aktivitaet Schueler
 * automatisch in verlinkte Kurse ein-/aussschreiben kann, ohne jemals
 * manuelle oder andere Einschreibungen von Lehrenden zu beruehren.
 *
 * @package     enrol_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'enrol_kursmodule';
$plugin->version   = 2026091900;
$plugin->requires  = 2022041900; // Moodle 4.0+.
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '1.0.0';
$plugin->dependencies = [
    'mod_kursmodule' => 2026091900,
];
