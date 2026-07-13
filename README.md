# adplaner 0.1.6

Nextcloud-App-Prototyp für die Wunschdienstplanung in Assistenzteams. Urlaubsplanung liegt ausschließlich in der separaten App `adurlaub`.

## Status

Development-Prototyp. Nicht produktiv und nicht rechtssicher.

Enthalten:

- Assistenzteams aus Nextcloud-Gruppen mit dem gemeinsam konfigurierten Präfix, standardmäßig `ad-ASN-<Kürzel>`.
- EB-Recht für Nutzer*innen, die zugleich im Team und in der gemeinsam konfigurierten EB-Rollengruppe sind.
- Monatlicher Wunschplan mit variabler Schichtliste; Standard ist 08–14, 14–20 und 20–08. Lücken und Überschneidungen sind möglich.
- Zuweisungen je Schicht: eigene Einträge durch Assistenz, fremde Zuweisungen nur durch EB; EB-Konten selbst sind nicht schichtfähig.
- Urlaub wird aus `adurlaub` gelesen; AdPlaner besitzt keine parallele Urlaubspersistenz und keine zusätzlichen `-Urlaub`-Gruppen.
- Statuswechsel `planned`/`approved` nur durch EB.

Noch offen:

- Produktive Rechte- und Datenschutzprüfung.
- Export, Benachrichtigungen und Dienstplan-Festschreibung.

## Installation

```bash
cd /var/www/vhosts/betriebsrat-ad.de/cloud.betriebsrat-ad.de/apps
cp -R /pfad/zu/adplaner .
cd /var/www/vhosts/betriebsrat-ad.de/cloud.betriebsrat-ad.de
sudo -u betriebsrat php occ app:enable adplaner
sudo -u betriebsrat php occ status
```

Nextcloud 34 hat keinen `occ migrations:migrate`-Befehl. App-Migrationen laufen beim Aktivieren der App bzw. ueber `occ upgrade`, wenn `occ status` `needsDbUpgrade: true` meldet.

Dann in Nextcloud oeffnen:

```text
/apps/adplaner
```
