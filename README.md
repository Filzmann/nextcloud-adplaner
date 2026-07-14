# AdPlaner

Monatliche Wunschdienstplanung für Assistenzteams. Urlaubsplanung liegt ausschließlich in der separaten App `adurlaub`.

## Staging-Kompatibilität

- Nextcloud 34
- PHP 8.3 oder neuer innerhalb des von Nextcloud 34 unterstützten Bereichs
- Abhängigkeiten: `localbase`, `orgsuite`
- App-ID und Installationsordner: `adplaner`

## Installation

```bash
sudo -u www-data php occ app:enable localbase
sudo -u www-data php occ app:enable orgsuite
sudo -u www-data php occ app:enable adplaner
```

Assistenzteams werden aus den zentral konfigurierten Nextcloud-Gruppen abgeleitet. Teambezogene Schichtkonfigurationen werden durch berechtigte Einsatzbegleitungen gepflegt.
