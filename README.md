# moodle-mod_Kursmodule

Moodle-Aktivität **Kursmodule**: verlinkt mehrere Kurse als sortierbare
Banner (Bild + Titel). Ein Klick auf einen Banner schreibt die Person
(sofern sie im Hauptkurs "Schüler/in" ist) im selben Moment in den
verlinkten Kurs ein (Rolle pro Link wählbar: *Teilnehmer/in* oder
*Gast*) und leitet direkt dorthin weiter - eingeschrieben wird also nur,
wer einen Kurs auch tatsächlich besucht. Einmal erzeugt, bleibt die
Einschreibung bestehen, bis der Link deaktiviert/entfernt wird oder die
Person den Hauptkurs verlässt. Ein Link kann als "aktuell" markiert
werden (Glow-Hervorhebung), die übrigen bleiben sichtbar abgedunkelt,
aber weiterhin voll funktionsfähig. Für Lehrende gibt es einen eigenen
Bewertungs-Tab mit Gesamtnoten-Matrix, aufklappbaren Einzelbewertungen
und Excel-Export.

## Installation

Ein einziges Plugin: `mod_kursmodule/` nach `<moodle>/mod/kursmodule`
kopieren, danach als Admin die Moodle-Update-Seite aufrufen
(`admin/index.php`). Keine weitere Konfiguration nötig.

Kursmodule nutzt für die Ein-/Ausschreibung die ohnehin in jedem
Zielkurs vorhandene **"Manuelle Einschreibung"** (`enrol_manual`) - es
gibt bewusst kein zweites, eigenes Einschreibe-Plugin mehr (das war in
frühen Versionen anders, siehe unten). Das macht die Installation
einfacher, hat aber eine Kehrseite:

> **Einschränkung:** Weil dieselbe "Manuelle Einschreibung"-Instanz
> auch von Lehrenden direkt im Zielkurs genutzt werden kann, kann Moodle
> nicht unterscheiden, ob eine konkrete Rollenzuweisung über Kursmodule
> oder manuell entstanden ist. Wird eine Person, die zusätzlich manuell
> in genau denselben Zielkurs eingetragen wurde, über einen
> Kursmodule-Link wieder entfernt, wird sie deshalb vollständig
> ausgeschrieben (auch aus der manuellen Einschreibung). Für die
> allermeisten Setups - eine Schule, wenige Admins, seltene
> Überschneidung - ein vertretbarer Kompromiss gegen die einfachere
> Installation. Wer das nicht will: siehe Git-Historie vor Version
> 1.3.0, dort gab es ein zweites, isoliertes `enrol_kursmodule`-Plugin.

## Funktionsweise (Kurzüberblick)

- Eine Kursmodule-Instanz enthält beliebig viele **Links** (Tabelle
  `kursmodule_link`), jeder verweist auf einen Zielkurs mit optionalem
  eigenem Titel/Bild, einer Rolle (`student`/`guest`) und einem
  Aktiv-Status.
- Ein Klick auf einen aktiven Banner führt über `go.php`, das - sofern
  die Person im Hauptkurs "Schüler/in" ist - synchron
  `link_manager::handle_click()` aufruft und danach in den Zielkurs
  weiterleitet. Lehrende/Verwaltende, die einen Link nur ansehen, werden
  dabei bewusst **nicht** eingeschrieben.
- Event-Beobachter (`classes/observer.php`, `db/events.php`) reagieren
  ausschließlich auf **Verlassen** des Hauptkurses bzw. Verlust der
  "student"-Rolle dort und schreiben die Person dann sofort aus allen
  darüber verwalteten Einschreibungen aus. Es gibt bewusst keine
  Beobachter, die proaktiv einschreiben.
- Eine geplante Aufgabe (`classes/task/sync_task.php`, alle 30 Minuten)
  entfernt zusätzlich verwaiste Einschreibungen (Personen, die inzwischen
  keine Schüler/innen des Hauptkurses mehr sind) - ein Sicherheitsnetz für
  Massenoperationen (Kohorten-Sync, Bulk-Ausschreibung), bei denen
  einzelne Events ausbleiben könnten. Sie schreibt niemanden neu ein.
- Wird ein Link deaktiviert oder gelöscht, werden alle darüber
  erzeugten Einschreibungen automatisch wieder entfernt
  (`enrolment_manager::remove_tracked_enrolment()`), Einschreibungen
  aus anderen Quellen bleiben unberührt.
- Rollenzuweisung ist immer auf den jeweiligen Zielkurs-Kontext
  beschränkt (Moodle-Standardverhalten bei Rollenzuweisungen) - ein
  über Kursmodule zugewiesenes "Gast"/"Teilnehmer/in" gilt nie in einem
  anderen Kurs.

## Bekannte Einschränkungen

- Die Kursauswahl in der Verwaltungsseite ist ein einfaches
  durchsuchbares Auswahlfeld über alle Kurse der Instanz - bei sehr
  vielen hundert Kursen ggf. spürbar, aber funktional.
- Beim Moodle2-Restore auf eine **andere** Moodle-Instanz kann ein Link
  nicht automatisch auf den richtigen Zielkurs abgebildet werden (der
  Zielkurs ist kein Teil der gesicherten Aktivität). Existiert nach dem
  Restore kein Kurs mit der ursprünglichen ID mehr, wird der Link beim
  Restore übersprungen statt auf einen falschen Kurs zu zeigen und muss
  danach manuell neu angelegt werden.
- Zeigen zwei verschiedene Links auf denselben Zielkurs mit
  unterschiedlicher Rolle, gewinnt beim Entfernen des ersten Links die
  vom zweiten Link zuletzt gesetzte Rolle nicht automatisch neu - ein
  seltener Randfall, der für v1 bewusst einfach gehalten wurde.
- Da die Einschreibung erst per Klick entsteht, zeigt die
  Bewertungsübersicht für einen Kurs, den noch niemand angeklickt hat,
  naturgemäß noch keine Daten - die Zeilen der Matrix basieren aber
  weiterhin auf allen Schüler/innen des Hauptkurses, unabhängig vom
  Klick-Status.

## Lizenz

GNU GPL v3 (siehe Kopfzeilen der Dateien). Dieses Plugin ist **nicht
frei für die kommerzielle Nutzung** und wird bewusst nicht im
offiziellen `moodle.org/plugins`-Verzeichnis geführt. Verteilung
ausschließlich über die [Releases](../../releases) dieses Repositories.
