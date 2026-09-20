<?php
// This file is part of Moodle - http://moodle.org/
//
// Kursmodule ist Freie Software: Sie duerfen es unter den Bedingungen
// der GNU General Public License, wie von der Free Software Foundation,
// Version 3 der Lizenz oder (nach Ihrer Wahl) jeder spaeteren
// veroeffentlichten Version, weiterverbreiten und/oder modifizieren.
//
// HINWEIS: Dieses Plugin ist NICHT frei fuer kommerzielle Nutzung und wird
// bewusst nicht im offiziellen moodle.org/plugins Verzeichnis gefuehrt.
// Verteilung erfolgt ausschliesslich ueber GitHub Releases.

/**
 * Version details fuer mod_kursmodule.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'mod_kursmodule';
$plugin->version   = 2026092004;
$plugin->requires  = 2022041900; // Moodle 4.0+.
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '1.3.3';
