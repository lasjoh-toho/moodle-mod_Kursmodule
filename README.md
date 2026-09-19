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

## Enthaltene Plugins

Dieses Repository enthält **zwei** Moodle-Plugins, die zusammengehören:

| Ordner              | Moodle-Zielverzeichnis     | Zweck                                                                 |
|----------------------|-----------------------------|------------------------------------------------------------------------|
| `mod_kursmodule/`    | `mod/kursmodule`            | Die Aktivität selbst (Banner, Verwaltung, Bewertungs-Tab)              |
| `enrol_kursmodule/`  | `enrol/kursmodule`          | Isolierte, rein programmatisch gesteuerte Einschreibemethode           |

**Beide Ordner müssen installiert werden**, `mod_kursmodule` erklärt
`enrol_kursmodule` in seiner `version.php` als Abhängigkeit.

Die eigene Einschreibemethode existiert bewusst als separates Plugin:
dadurch landen automatisch erzeugte Einschreibungen nie in derselben
"Manuelle Einschreibung"-Instanz, die Lehrende für eigene, unabhängige
Einschreibungen nutzen - beim Entfernen eines Links wird garantiert
*ausschließlich* das entfernt, was Kursmodule selbst angelegt hat.

## Installation

1. `mod_kursmodule/` nach `<moodle>/mod/kursmodule` kopieren.
2. `enrol_kursmodule/` nach `<moodle>/enrol/kursmodule` kopieren.
3. Als Admin die Moodle-Update-Seite aufrufen (`admin/index.php`).
4. Fertig - keine weitere Konfiguration nötig. Die Einschreibemethode
   `enrol_kursmodule` muss **nicht** manuell in einem Kurs aktiviert
   werden, sie wird bei Bedarf automatisch pro Zielkurs angelegt.

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

## Bekannte Einschränkungen (v1.0.0)

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
