# AGENTS.md - AdPlaner

## Projekt

Nextcloud-App `adplaner` fuer Assistenzdienst-/Dienstplanungsablaeufe.

Lokale App-URL in der gemeinsamen DDEV-Umgebung:

    https://nextcloud-dev.ddev.site/apps/adplaner/

Nextcloud-App-ID:

    adplaner

## Zielsetzung

AdPlaner soll Dienstplanung und Urlaubsplanung in Assistenzteams abbilden.

Kernprozess:

- Assistenznehmer werden aus Nextcloud-Gruppen `ad-ASN-<Kuerzel>` abgeleitet.
- EB-Rechte erhalten Nutzer*innen, die zugleich im Team und in einer Gruppe `ad-EB-*` sind.
- Monatliche Wunschplaene werden je Assistenzteam erstellt.
- Die Schichtliste ist variabel konfigurierbar; Standard ist 08-14, 14-20 und 20-08.
- Luecken und Ueberlappungen in Schichtdefinitionen sind moeglich und duerfen nicht vorschnell wegvalidiert werden.
- Zuweisungen je Schicht folgen der Rollenlogik: eigene Eintraege durch Assistenz, fremde Zuweisungen nur durch EB.
- EB-Konten selbst sind nicht schichtfaehig.
- Jahres-Urlaubsplanung zeigt alle Tage als Spalten und Assistenzkraefte als Zeilen.
- Urlaubswuensche sind globale Eintraege pro Assistenz und in allen Teams der Person sichtbar.
- Optionale Urlaubssichtbarkeit laeuft ueber `ad-ASN-<Kuerzel>-Urlaub`; ohne diese Gruppe wird die Assistenznehmer-Gruppe selbst verwendet.
- Statuswechsel wie `planned` und `approved` erfolgen nur durch EB.

Offene Zielbereiche:

- Produktive Rechte- und Datenschutzpruefung.
- Feingranulare Urlaubsteilung, wenn nur ein Tag innerhalb eines Bereichs geaendert wird.
- Export, Benachrichtigungen und Dienstplan-Festschreibung.
- Spaetere Konfigurierbarkeit dort ausbauen, wo konkrete Teams unterschiedliche Regeln brauchen.

## Git- und Arbeitsregeln

- Dieses Verzeichnis ist ein eigenstaendiges Git-Repository fuer die AD-App `adplaner`.
- Andere eigene Nextcloud-Apps, zum Beispiel `brtop`, leben in eigenen Repositories.
- Keine Commits, kein Push und kein Deployment ohne ausdrueckliche Freigabe durch Simon.
- Vor Commits immer `git status --short`, `git diff --stat` und `git diff --name-only` zeigen.
- Nicht `git add .` verwenden; Dateien gezielt stagen.
- Aenderungen klein, pruefbar und rueckbaubar halten.
- Fuer groessere Refactorings, neue Datenmodelle oder neue Services soll ein eigener Branch vorgeschlagen werden.

## DDEV

Die gemeinsame lokale Nextcloud-DDEV-Umgebung liegt ausserhalb dieses Repos:

    ~/projects/br-nextcloud-apps/nextcloud-dev

AdPlaner nutzt gemeinsame Basisbausteine aus der Hilfsapp `localbase`. In der lokalen Nextcloud muss `localbase` aktiviert sein, bevor AdPlaner vollstaendig lauffaehig ist.

Wichtige Pruefungen:

    ddev exec -d /var/www/html/html php occ app:list | grep -i localbase
    ddev exec -d /var/www/html/html php occ status
    ddev exec -d /var/www/html/html php occ app:list
    ddev exec -d /var/www/html/html php occ upgrade

Diese lokale Nextcloud-Version hat keinen `occ migrations:migrate`-Befehl. App-Migrationen laufen beim Aktivieren der App bzw. ueber `occ upgrade`, wenn Nextcloud einen DB-Upgrade-Bedarf meldet.

In Codex-Sessions koennen DDEV-Befehle im normalen Sandbox-Kontext nicht zuverlaessig auf Docker zugreifen. Wenn `ddev` mit Docker-/Stream-FD-Fehlern scheitert, den gleichen Befehl mit eskaliertem Zugriff erneut ausfuehren.

## Fachkontext

Gruppenschema fuer `adplaner`:

- Assistenznehmer-Gruppen: `ad-ASN-<Kuerzel>`, zum Beispiel `ad-ASN-TeamB`, `ad-ASN-TeamA`, `ad-ASN-TeamC`.
- `<Kuerzel>` ist das Kuerzel eines Assistenznehmers und darf Unicode-Buchstaben sowie Ziffern enthalten.
- Optionale Urlaubssichtbarkeitsgruppe: `ad-ASN-<Kuerzel>-Urlaub`.
- EB-Rechte: Nutzer*innen, die zugleich in der Assistenznehmer-Gruppe und einer Gruppe nach `ad-EB-*` sind.

## Architekturregeln

- Controller bleiben duenn.
- Fachlogik, Datenzugriff, Darstellung und Dateiablage werden getrennt.
- Wiederkehrende Logik wird nicht mehrfach in Controllern oder `main.js` dupliziert.
- Persistente Kernobjekte bekommen Modelle/DTOs oder Value Objects.
- Modelle/DTOs werden bei Neu- und Weiterentwicklungen in PHP und JavaScript einheitlich angefasst: `get(...)` fuer ein einzelnes Payload/Row/Objekt, `get_all([...])` fuer Listen, `toArray()` fuer Serialisierung und `save()` nur fuer wirklich persistierbare, store-gebundene Modelle. Nicht persistierbare DTOs duerfen `save()` bewusst mit klarer Fehlermeldung blockieren.
- Modell-Hydration wird von aussen ueber `get(...)` und `get_all([...])` aufgerufen. Hilfsmethoden wie `fromArray` oder `fromRow` bleiben, falls noetig, interne/protected Implementierungsdetails und sind keine oeffentliche Modell-API.
- Neue Modellarbeit fuehrt keine neuen `fromApi`-/`toApi`-Kompatibilitaetsaliase ein. Bestehende PHP-`toApiArray()`-Call-sites duerfen schrittweise auf `toArray()` migriert werden, wenn die betroffene Schicht ohnehin angefasst wird.
- Datenzugriffe laufen ueber Repository-, Store- oder Service-Klassen.
- Services arbeiten bevorzugt mit Modellen/DTOs statt rohen Arrays.
- Groessere HTML-Bloecke werden aus `templates/index.php` in Partials ausgelagert.
- Wiederkehrende Frontend-Logik wird in `js/components/`, `js/modules/` oder `js/repositories/` ausgelagert.
- JavaScript wird gut gekapselt, wiederverwendbar und weitgehend objektorientiert strukturiert. API-Zugriffe gehoeren in Repositories/API-Adapter, Daten in Modelle/ViewModels, Workflows in kleine Services/Controller und Rendering/Eventbindung in Komponenten.
- DRY und KISS gelten gemeinsam: echte Duplizierung wird entfernt, aber einfache Lesbarkeit und klare AdPlaner-Fachgrenzen bleiben wichtiger als fruehe generische Abstraktionen.
- Gemeinsame UI-Helfer oder Komponenten werden erst nach `localbase` verschoben, wenn sie in mindestens zwei Apps dieselbe Semantik, dieselben Zustaende, Events und Accessibility-Regeln haben.
- Fehler werden zentral protokolliert; Nutzer*innen erhalten sichere, knappe Meldungen ohne interne Details.
- Keine Architekturabstraktion wird vorsorglich gebaut. Auslagerung erfolgt, wenn sie konkrete Duplizierung, Testbarkeit oder Wartbarkeit verbessert.

Diese Regeln gelten sinngemaess auch fuer andere eigene Nextcloud-Apps; die fachlichen Anwendungsfaelle bleiben aber getrennt.

## Learnings pflegen

- Wenn bei der Arbeit ein echtes, wiederverwendbares Projekt-Learning entsteht, soll Codex vorschlagen, es in dieser `AGENTS.md` zu ergaenzen.
- Die Ergaenzung erfolgt erst nach ausdruecklicher Freigabe.
- App-spezifische Learnings werden in diesem App-Repo gespeichert.
- App-uebergreifende Learnings werden im Parent-Workspace dokumentiert und bei Bedarf in die App-`AGENTS.md` uebertragen.
- Neue Regeln muessen dort stehen, wo sie gebraucht werden: AdPlaner-Fachlogik hier, DDEV-/Repo-/Neue-App-Regeln im Parent bzw. in allen betroffenen App-Repos.

## Tests

Vor groesseren Refactorings zuerst Charakterisierungstests fuer das bestehende gewuenschte Verhalten schreiben oder aktualisieren.

- Schnelle PHP-Suite: `php tests/run.php`
- Schnelle JavaScript-Suite: `node tests/run-js.mjs`
- Nach LocalBase-Aenderungen mindestens die betroffenen AdPlaner-Smoke-/Contract-Tests laufen lassen.
- Bei Controller-, DI-, Migrations- oder Nextcloud-Container-Aenderungen zusaetzlich gezielte DDEV-/`occ`-Checks ausfuehren.

Wichtige lokale Pruefungen:

    php tests/run.php
    node tests/run-js.mjs

Einzelne Checks, die durch die Testlaeufer gebuendelt werden:

    find js -name '*.js' -print0 | xargs -0 -n1 node --check
    node tests/js/model-smoke.js
    node tests/js/plan-repository-smoke.js
    for f in tests/Service/*.php; do php "$f"; done
