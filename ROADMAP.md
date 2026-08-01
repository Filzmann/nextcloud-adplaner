# Roadmap – AdPlaner

Diese Datei bündelt geplante Erweiterungen und offene Produktentscheidungen. Verbindliche Fach-, Sicherheits- und Architekturregeln stehen in `AGENTS.md`.

## Freigegebene Umsetzungsaufgaben

### ADP-L10N – Assistenzplanung vollständig lokalisieren

Status: als l10n-Pilot geeignet

- Manuelle Monats-/Wochentagsnamen und sichtbare UI-, Status-, Validierungs-
  und Fehlermeldungen auf aktive Nextcloud-Locale und Nextcloud-l10n
  umstellen.
- ISO-Daten, Monatsnummern, Schichtzeiten, Statuswerte, Teamcodes und
  API-Schlüssel unverändert lassen; Abkürzungen nicht durch Abschneiden
  bilden.
- Deutsche Ausgabe, eine weitere Locale, Fallback, Monats-/Jahresgrenzen,
  Pluralformen, Platzhalter und Escaping in PHP und JavaScript testen.
- Erst nach vollständiger Pilotmigration den app-eigenen Rohtext-Check
  verbindlich schalten und seinen Vertrag für die weiteren Apps
  dokumentieren.

## Aktueller Fokus

- Die manuellen Prüfungen werden im ausfüllbaren
  [`docs/manual-acceptance.md`](docs/manual-acceptance.md) dokumentiert.
- Produktive Rechte- und Datenschutzprüfung der Wunschdienstplanung.
- Monatsplan, variable Schichten, EB-Koordination und Standalone-Betrieb auf einem realitätsnahen Staging fachlich abnehmen.

## Geplante Erweiterungen

- Persönliche Monatsansicht „Alle meine Einsätze“ mit PDF-Export und optionaler Verbindung zu gängigen Kalendern.
- Benachrichtigungen für relevante Planungs- und Statusänderungen.
- Fachlich eindeutige Festschreibung eines Dienstplans.
- Teambezogene Konfigurierbarkeit nur dort erweitern, wo konkrete Teams unterschiedliche Regeln benötigen.

## Vor der Umsetzung zu klären

- Exportformate, Zielsysteme und Datenschutzumfang.
- Benachrichtigungskanäle, Empfänger*innen und auslösende Ereignisse.
- Bedeutung, Rechte und Rückbau einer Festschreibung sowie der Umgang mit späteren Änderungen.
