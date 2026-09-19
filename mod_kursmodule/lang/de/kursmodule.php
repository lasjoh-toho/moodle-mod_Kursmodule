<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Deutsche Sprachdatei fuer mod_kursmodule.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Kursmodule';
$string['modulenameplural'] = 'Kursmodule';
$string['modulename_help'] = 'Mit der Aktivität "Kursmodule" verlinken Sie mehrere Kurse als sortierbare Banner. Lernende werden automatisch in die verlinkten Kurse ein- und bei Entfernen des Links wieder ausgeschrieben. Ein Link kann als "aktuell" hervorgehoben werden.';
$string['pluginname'] = 'Kursmodule';
$string['pluginadministration'] = 'Kursmodule-Administration';
$string['kursmodulename'] = 'Name der Aktivität';
$string['kursmodulesettings'] = 'Einstellungen';

$string['links'] = 'Kursverknüpfungen';
$string['managelinks_hint'] = 'Nach dem Speichern können Sie die verlinkten Kurse, Banner-Bilder, Rollen und die Reihenfolge auf der eigenen Verwaltungsseite der Aktivität pflegen.';
$string['managelinks'] = 'Kursverknüpfungen verwalten';
$string['manage_title'] = 'Kursverknüpfungen verwalten: {$a}';
$string['backtoactivity'] = 'Zurück zur Aktivität';

$string['addlink'] = 'Kurs verknüpfen';
$string['editlink'] = 'Verknüpfung bearbeiten';
$string['deletelink'] = 'Verknüpfung entfernen';
$string['deletelink_confirm'] = 'Verknüpfung zu "{$a}" wirklich entfernen? Alle darüber automatisch eingeschriebenen Lernenden werden aus diesem Kurs wieder ausgeschrieben (sofern sie nicht über einen anderen Weg dort eingeschrieben sind).';

$string['course'] = 'Kurs';
$string['searchcourse'] = 'Kurs suchen';
$string['title'] = 'Anzeigetitel (optional)';
$string['title_help'] = 'Wird kein eigener Titel angegeben, zeigt das Banner den Kursnamen.';
$string['image'] = 'Banner-Bild';
$string['image_help'] = 'Wird angezeigt, bevor der Titel folgt. Ohne eigenes Bild wird das Kursbild verwendet, falls vorhanden.';
$string['role'] = 'Rolle im Zielkurs';
$string['role_student'] = 'Teilnehmer/in';
$string['role_guest'] = 'Gast';
$string['role_help'] = 'Legt fest, mit welcher Rolle Lernende automatisch in diesen Kurs eingeschrieben werden. Die Rolle gilt ausschließlich für diesen einen Kurs.';
$string['active'] = 'Aktiv';
$string['active_help'] = 'Ein deaktivierter Link wird nicht mehr angezeigt und alle darüber erzeugten Einschreibungen werden automatisch entfernt.';
$string['setcurrent'] = 'Als aktuell markieren';
$string['iscurrent'] = 'Aktuell';
$string['currentbadge'] = 'Aktueller Kurs';

$string['savechanges'] = 'Speichern';
$string['cancel'] = 'Abbrechen';
$string['linkadded'] = 'Kursverknüpfung wurde hinzugefügt.';
$string['linkupdated'] = 'Kursverknüpfung wurde aktualisiert.';
$string['linkdeleted'] = 'Kursverknüpfung wurde entfernt.';
$string['reordersaved'] = 'Reihenfolge gespeichert.';
$string['currentset'] = 'Als aktueller Kurs markiert.';

$string['errorcourseinvalid'] = 'Bitte wählen Sie einen gültigen Kurs aus.';
$string['errorcoursealreadylinked'] = 'Dieser Kurs ist bereits verknüpft.';

$string['nolinksyet'] = 'Es sind noch keine Kursverknüpfungen angelegt.';
$string['viewnolinks'] = 'Für diese Aktivität wurden noch keine Kursverknüpfungen angelegt.';

$string['tablinks'] = 'Kursverknüpfungen';
$string['tabgrades'] = 'Bewertungen';

$string['gradestitle'] = 'Bewertungsübersicht';
$string['gradesexport'] = 'Als Excel-Datei exportieren';
$string['gradesmatrixtoggle'] = 'Detaillierte Einzelbewertungen anzeigen';
$string['gradesoverall'] = 'Gesamtnote';
$string['gradesnodata'] = 'Für diese Verknüpfungen liegen noch keine Bewertungen vor.';
$string['gradesstudent'] = 'Schüler/in';
$string['gradesnogradeitem'] = '–';

$string['tasksync'] = 'Kursmodule-Einschreibungen abgleichen';

$string['privacy:metadata:kursmodule_enrol'] = 'Nachverfolgung, welche Person über welche Kursverknüpfung automatisch in welchem Kurs eingeschrieben wurde.';
$string['privacy:metadata:kursmodule_enrol:userid'] = 'Die ID der automatisch eingeschriebenen Person.';
$string['privacy:metadata:kursmodule_enrol:courseid'] = 'Der Zielkurs der automatischen Einschreibung.';
$string['privacy:metadata:kursmodule_enrol:roleid'] = 'Die zugewiesene Rolle (Teilnehmer/in oder Gast).';
$string['privacy:metadata:kursmodule_enrol:timecreated'] = 'Zeitpunkt der automatischen Einschreibung.';

$string['kursmodule:addinstance'] = 'Neue Kursmodule-Aktivität anlegen';
$string['kursmodule:view'] = 'Kursmodule-Aktivität ansehen';
$string['kursmodule:manage'] = 'Kursverknüpfungen verwalten';
$string['kursmodule:viewgrades'] = 'Bewertungsübersicht ansehen';
