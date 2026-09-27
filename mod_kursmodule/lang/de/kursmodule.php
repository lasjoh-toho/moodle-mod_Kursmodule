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

$string['copytoclipboard'] = 'Einstellungen in den Speicher kopieren';
$string['pastefromclipboard'] = 'Module aus dem Speicher einfügen';
$string['clipboardcopied'] = '{$a} Kursverknüpfung(en) in den Speicher kopiert. Der Speicher gehört Ihnen persönlich und gilt für alle Kursmodule-Aktivitäten, auch in anderen Kursen.';
$string['clipboardempty'] = 'Der Speicher ist leer. Kopieren Sie zuerst die Verknüpfungen einer anderen Kursmodule-Aktivität.';
$string['clipboardpasted'] = '{$a->added} von {$a->total} Kursverknüpfung(en) eingefügt.';
$string['clipboardpastedskipped'] = '{$a->added} von {$a->total} Kursverknüpfung(en) eingefügt, {$a->skipped} übersprungen (Zielkurs gelöscht oder identisch mit dem Hauptkurs dieser Aktivität).';

$string['errorcourseinvalid'] = 'Bitte wählen Sie einen gültigen Kurs aus.';
$string['errorcoursealreadylinked'] = 'Dieser Kurs ist bereits verknüpft.';

$string['errorcoursenotallowed'] = 'Dieser Kurs steht Ihnen laut den Einstellungen der Website-Administration nicht zur Verknüpfung zur Verfügung.';

$string['onlyteacherrole'] = 'Nur Kurse anzeigen, in denen ich Trainer/in bin';
$string['applyfilter'] = 'Filter anwenden';

$string['settings_coursefilters'] = 'Kursauswahl einschränken';
$string['settings_coursefilters_desc'] = 'Legt fest, welche Kurse Trainer/innen überhaupt verknüpfen dürfen. Grundvoraussetzung ist immer die Rolle "Trainer/in mit Bearbeitungsrecht" - fest und nicht abschaltbar. Die folgenden Einstellungen erweitern das optional. Sie gelten automatisch und sind für Trainer/innen nicht abschaltbar - nur hier von Admins konfigurierbar. Website-Admins selbst sind immer ausgenommen.';
$string['settings_restrictroleteacher'] = 'Auch Verknüpfung durch Trainer/innen ohne Bearbeitungsrecht erlauben';
$string['settings_restrictroleteacher_desc'] = 'Erlaubt zusätzlich zur Grundvoraussetzung (Trainer/in mit Bearbeitungsrecht) das Verknüpfen von Kursen, in denen die Person als Trainer/in ohne Bearbeitungsrecht eingeschrieben ist.';
$string['settings_restrictrolestudent'] = 'Auch Verknüpfung durch Kurse erlauben, in denen die Person nur Teilnehmer/in ist';
$string['settings_restrictrolestudent_desc'] = 'Erlaubt zusätzlich zur Grundvoraussetzung (Trainer/in mit Bearbeitungsrecht) das Verknüpfen von Kursen, in denen die Person lediglich als Teilnehmer/in eingeschrieben ist. Nur wenn diese Einstellung aktiviert ist, bekommen Trainer/innen im Verknüpfungsformular die optionale Anzeige-Option "Nur Kurse anzeigen, in denen ich Trainer/in bin".';
$string['settings_fieldfiltershortnames'] = 'Kurznamen der Kurs-Zusatzfelder';
$string['settings_fieldfiltershortnames_desc'] = 'Kommagetrennte Liste von Kurznamen (nicht Anzeigenamen) von Feldern, die unter Website-Administration → Kurse → Kurs-Standardeinstellungen → Kurs-Zusatzfelder angelegt wurden, z. B. "schule,standort". Ein Kurs darf nur verknüpft werden, wenn er bei JEDEM angegebenen Feld denselben Wert hat wie der Hauptkurs der Aktivität (UND-Verknüpfung) - z. B. um in einem System mit vielen Schulen die Auswahl auf die eigene Schule zu begrenzen. Funktioniert zuverlässig für Text- und Auswahl-Felder. Für Trainer/innen nicht abschaltbar. Leer lassen, um diesen Filter nicht zu verwenden.';

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
