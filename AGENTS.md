# AGENTS.md - AdPlaner

## Projekt

Nextcloud-App `adplaner` fuer Assistenzdienst-/Dienstplanungsablaeufe.

Lokale App-URL in der gemeinsamen DDEV-Umgebung:

    https://nextcloud-dev.ddev.site/apps/adplaner/

Nextcloud-App-ID:

    adplaner

## Zielsetzung

AdPlaner bildet die Wunschdienstplanung in Assistenzteams ab. Urlaubsplanung gehört ausschließlich in die separate App `adurlaub`.

Kernprozess:

- Assistenznehmer werden aus Nextcloud-Gruppen mit dem gemeinsam konfigurierten Assistenzteam-Präfix abgeleitet.
- EB-Rechte erhalten Nutzer*innen, die zugleich im Team und in der gemeinsam konfigurierten EB-Rollengruppe sind.
- Monatliche Wunschplaene werden je Assistenzteam erstellt.
- Die Schichtliste ist variabel konfigurierbar; Standard ist 08-14, 14-20 und 20-08.
- Luecken und Ueberlappungen in Schichtdefinitionen sind moeglich und duerfen nicht vorschnell wegvalidiert werden.
- Zuweisungen je Schicht folgen der Rollenlogik: eigene Eintraege durch Assistenz, fremde Zuweisungen nur durch EB.
- EB-Konten selbst sind nicht schichtfaehig.
- Statuswechsel wie `planned` und `approved` erfolgen nur durch EB.

Offene Zielbereiche:

- Produktive Rechte- und Datenschutzpruefung.
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

Die folgenden IDs sind initiale Standardwerte. Assistenzteam-Präfix, sichtbarer Teamname, maximale Kürzellänge, EB-Rollengruppe und Bereiche stammen aus der gemeinsamen `AdOrganizationDefinition` und dürfen nicht zusätzlich im AdPlaner festverdrahtet werden.

- Assistenznehmer-Gruppen: `ad-ASN-<Kuerzel>`, zum Beispiel `ad-ASN-TeamA`, `ad-ASN-TeamB`, `ad-ASN-TeamC`.
- `<Kuerzel>` ist das Kuerzel eines Assistenznehmers und darf Unicode-Buchstaben sowie Ziffern enthalten.
- EB-Rechte: Nutzer*innen, die zugleich in der Assistenznehmer-Gruppe und der gemeinsamen Rollengruppe `ad-EB` sind. Rollen-/Bereichskombinationen werden nicht als eigene Gruppen akzeptiert.
- Bereichszuordnungen werden app-uebergreifend separat als `ad-Bereich-<Name>` gepflegt; kombinierte Rollen-/Bereichsgruppen werden dynamisch abgeleitet.
- AdPlaner und AD Urlaub verwenden dieselben Assistenzteam-Gruppen; separate Suffix-Gruppen werden nicht unterstützt.
- Schichten werden ausschließlich über die strukturierte Schichtkonfiguration verwaltet. Frühere einzelne Legacy-Parameter für Früh-, Spät- oder Nachtschichten werden nicht weitergeführt.
- Die Schichtkonfiguration eines Assistenzteams ist eine delegierte fachliche Teamkonfiguration und wird durch die zuständige EB im AdPlaner gepflegt. Sie ist keine ausschließlich für Nextcloud-Admins bestimmte organisationsweite Einstellung und gehört deshalb nicht in den Suite-Adminbereich.

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

### Gemeinsame Suite-Navigation

- Ohne aktive OrgSuite registriert AdPlaner einen eigenen Nextcloud-Hauptnavigationseintrag. Ab zwei AD-Produkten ersetzt `orgsuite` diesen durch den gemeinsamen Einstieg `AD`.
- Das Template stellt den optionalen Menühost mit `data-suite="ad"` und `data-current-app="adplaner"` bereit, lädt aber keine OrgSuite-Assets direkt.
- Ohne AD Urlaub oder AD Kalender bleibt die Assistenzplanung eigenständig nutzbar; optionale Abwesenheits- und Konflikthinweise dürfen den Monatsplan nicht blockieren.
- Team- und Planungsrechte bleiben ausschliesslich serverseitig im AdPlaner; Menuesichtbarkeit ist keine Berechtigung.
- Der deckende Hintergrund und das vertikale Scrolling liegen am App-Root `#adplaner-app`; globale Nextcloud-Container wie `#content` werden nicht ueberschrieben.

- Wenn bei der Arbeit ein echtes, wiederverwendbares Projekt-Learning entsteht, soll Codex vorschlagen, es in dieser `AGENTS.md` zu ergaenzen.
- Die Ergaenzung erfolgt erst nach ausdruecklicher Freigabe.
- App-spezifische Learnings werden in diesem App-Repo gespeichert.
- App-uebergreifende Learnings werden im Parent-Workspace dokumentiert und bei Bedarf in die App-`AGENTS.md` uebertragen.
- Neue Regeln muessen dort stehen, wo sie gebraucht werden: AdPlaner-Fachlogik hier, DDEV-/Repo-/Neue-App-Regeln im Parent bzw. in allen betroffenen App-Repos.

## Tests

Vor groesseren Refactorings zuerst Charakterisierungstests fuer das bestehende gewuenschte Verhalten schreiben oder aktualisieren.

- Tests sind Teil der Architekturarbeit und kein optionaler Nachtrag. Neue oder refaktorierte AdPlaner-Fachlogik bekommt passende Charakterisierungs-, Unit-, Contract- oder Smoke-Tests, bevor darauf weiter aufgebaut wird.
- Schnelle PHP-Suite: `php tests/run.php`
- Schnelle JavaScript-Suite: `node tests/run-js.mjs`
- Authentifizierter DOM-/CSRF-/API-Smoke: `ADP_BASE_URL=... ADP_USER=... ADP_PASSWORD=... tests/http-smoke.sh`
- Nach LocalBase-Aenderungen mindestens die betroffenen AdPlaner-Smoke-/Contract-Tests laufen lassen.
- Gemeinsame LocalBase-Test-Helper nutzen, wenn dadurch echte Setup-Duplizierung verschwindet, ohne die Lesbarkeit des einzelnen Tests zu verschlechtern.
- Bei Controller-, DI-, Migrations- oder Nextcloud-Container-Aenderungen zusaetzlich gezielte DDEV-/`occ`-Checks ausfuehren.

Wichtige lokale Pruefungen:

    php tests/run.php
    node tests/run-js.mjs

Einzelne Checks, die durch die Testlaeufer gebuendelt werden:

    find js -name '*.js' -print0 | xargs -0 -n1 node --check
    node tests/js/model-smoke.js
    node tests/js/plan-repository-smoke.js
    for f in tests/Service/*.php; do php "$f"; done
