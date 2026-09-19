# moodle-mod_Kursmodule

Moodle-Aktivität **Kursmodule**: verlinkt mehrere Kurse als sortierbare
Banner (Bild + Titel), schreibt Schüler/innen automatisch in die
verlinkten Kurse ein (Rolle pro Link wählbar: *Teilnehmer/in* oder
*Gast*) und hält diese Einschreibung dauerhaft synchron zur
Kursmitgliedschaft im Hauptkurs. Ein Link kann als "aktuell" markiert
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
- Sobald ein Link aktiv ist, werden **alle aktuell im Hauptkurs als
  "Schüler/in" eingeschriebenen Personen** automatisch in den Zielkurs
  eingeschrieben (`classes/link_manager.php`).
- Event-Beobachter (`classes/observer.php`, `db/events.php`) reagieren
  auf neue/entfernte Einschreibungen und Rollenänderungen im Hauptkurs
  und schreiben betroffene Personen sofort in alle aktiven Links ein
  bzw. wieder aus.
- Eine geplante Aufgabe (`classes/task/sync_task.php`, alle 30 Minuten)
  gleicht zusätzlich alle aktiven Links vollständig ab - ein
  Sicherheitsnetz für Massenoperationen (Kohorten-Sync, Bulk-Import),
  bei denen einzelne Events ausbleiben könnten.
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
- **Verkettung möglich:** Wenn ein Zielkurs selbst wieder eine
  Kursmodule-Aktivität mit eigenen aktiven Links enthält, lösen die dort
  automatisch erzeugten Einschreibungen/Rollenzuweisungen dieselben
  Events aus und schreiben Lernende transitiv auch in die *dortigen*
  Verknüpfungen ein. Das kann gewünscht sein (mehrstufige Kursketten),
  sollte bei verschachtelten Kursstrukturen aber bewusst eingesetzt
  werden, um keine unerwartet langen Einschreibeketten zu erzeugen.

## Lizenz

GNU GPL v3 (siehe Kopfzeilen der Dateien). Dieses Plugin ist **nicht
frei für die kommerzielle Nutzung** und wird bewusst nicht im
offiziellen `moodle.org/plugins`-Verzeichnis geführt. Verteilung
ausschließlich über die [Releases](../../releases) dieses Repositories.
