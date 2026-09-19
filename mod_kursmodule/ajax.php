<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Leichtgewichtiger AJAX-Endpunkt fuer die Verwaltungsseite: aktuell nur
 * fuer das Speichern der per Drag & Drop geaenderten Reihenfolge.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

use mod_kursmodule\link_manager;

require_sesskey();
require_login();

header('Content-Type: application/json; charset=utf-8');

$action = required_param('action', PARAM_ALPHA);
$cmid = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('kursmodule', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$kursmodule = $DB->get_record('kursmodule', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/kursmodule:manage', $context);

$result = ['success' => false];

if ($action === 'reorder') {
    $orderjson = required_param('order', PARAM_RAW);
    $order = json_decode($orderjson, true);
    if (is_array($order)) {
        $order = array_map('intval', $order);
        link_manager::reorder($kursmodule->id, $order);
        $result['success'] = true;
    }
}

echo json_encode($result);
