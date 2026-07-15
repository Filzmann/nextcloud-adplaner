# AdPlaner

Monatliche Wunschdienstplanung für Assistenzteams. Urlaubsplanung liegt ausschließlich in der separaten App `adurlaub`.

## Staging-Kompatibilität

- Nextcloud 34
- PHP 8.3 oder neuer innerhalb des von Nextcloud 34 unterstützten Bereichs
- Laufzeitbasis: `localbase`; `orgsuite` ist ab zwei AD-Fachprodukten optional aktiv
- App-ID und Installationsordner: `adplaner`

## Installation

Für Staging und Auslieferung das Produktbundle `ad-product-adplaner-<release>.tar.gz` und dessen enthaltenes `install.sh` verwenden. Es prüft und installiert LocalBase automatisch; ab dem zweiten AD-Fachprodukt aktiviert es OrgSuite.

AdPlaner funktioniert einzeln; optionale Abwesenheits- oder Kalenderhinweise entfallen ohne die jeweilige Fachapp, ohne den Monatsplan zu blockieren.

Assistenzteams werden aus den zentral konfigurierten Nextcloud-Gruppen abgeleitet. Teambezogene Schichtkonfigurationen werden durch berechtigte Einsatzbegleitungen gepflegt.

Installations-, Betriebs- und Abnahmeunterlagen stehen im öffentlichen [AD-Suite-Projekt](https://github.com/Filzmann/ad-suite).
