# ClubSuite Core

[![Nextcloud Version](https://img.shields.io/badge/Nextcloud-28--32-blue.svg)](https://nextcloud.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.1--8.3-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-AGPL%20v3-green.svg)](LICENSE)

> Das Herzstück der ClubSuite – modularer Vereinsverwaltung für Nextcloud.

## 📋 Übersicht

ClubSuite Core ist die Basis-App für alle ClubSuite-Module. Sie bietet:

- **Mitgliederverwaltung**: Erweiterte Metadaten über Nextcloud-Benutzer hinaus
- **Gruppen & Abteilungen**: Flexible Organisationsstruktur
- **Beitragsgruppen**: Verschiedene Mitgliedschaftsarten mit Beitragssätzen
- **Event-System**: PSR-14 Events für Modul-Kommunikation
- **REST API**: Vollständige API für alle Funktionen

## 🚀 Installation

### Über den Nextcloud App Store (empfohlen)

1. Navigieren Sie zu **Apps** → **Organisation**
2. Suchen Sie nach "ClubSuite Core"
3. Klicken Sie auf **Herunterladen und aktivieren**

### Manuelle Installation

```bash
cd /path/to/nextcloud/apps
git clone https://github.com/ClubSuite-for-Nextcloud/clubsuite-core.git
cd clubsuite-core
composer install --no-dev
npm ci && npm run build
```

Aktivieren Sie die App:
```bash
php occ app:enable clubsuite-core
```

## 📦 Anforderungen

| Komponente | Version |
|------------|---------|
| Nextcloud | 28 - 32 |
| PHP | 8.1 - 8.3 |
| Datenbank | MySQL/MariaDB oder PostgreSQL |

## 🔧 Konfiguration

Nach der Installation können Sie die App unter **Einstellungen** → **Verein** konfigurieren:

- Vereinsname und -daten
- Beitragsgruppen anlegen
- Standardwerte festlegen

## 📚 Dokumentation

- [API-Dokumentation](docs/api.md)
- [Event-Referenz](docs/events.md)
- [Entwicklerhandbuch](docs/development.md)

## 🔒 DSGVO / Datenschutz

Diese App implementiert die Nextcloud Privacy API (`IPersonalDataProvider`):

- **Datenexport**: Alle Mitgliederdaten werden im Nextcloud-Export eingeschlossen
- **Löschanfragen**: "Recht auf Vergessenwerden" wird unterstützt
- **Audit-Log**: Alle Änderungen werden protokolliert

## 🤝 Weitere Module

| Modul | Beschreibung |
|-------|--------------|
| [clubsuite-applications](https://github.com/ClubSuite-for-Nextcloud/clubsuite-applications) | Mitgliedsanträge |
| [clubsuite-finance](https://github.com/ClubSuite-for-Nextcloud/clubsuite-finance) | Kassenbuch |
| [clubsuite-sepa](https://github.com/ClubSuite-for-Nextcloud/clubsuite-sepa) | SEPA-Lastschriften |
| [clubsuite-meetings](https://github.com/ClubSuite-for-Nextcloud/clubsuite-meetings) | Sitzungen & Protokolle |
| [clubsuite-inventory](https://github.com/ClubSuite-for-Nextcloud/clubsuite-inventory) | Inventar & Ausleihe |
| [clubsuite-scores](https://github.com/ClubSuite-for-Nextcloud/clubsuite-scores) | Notenverwaltung |
| [clubsuite-documents](https://github.com/ClubSuite-for-Nextcloud/clubsuite-documents) | Dokumenten-Workflows |
| [clubsuite-newsletter](https://github.com/ClubSuite-for-Nextcloud/clubsuite-newsletter) | Newsletter |
| [clubsuite-stats](https://github.com/ClubSuite-for-Nextcloud/clubsuite-stats) | Statistiken |
| [clubsuite-training](https://github.com/ClubSuite-for-Nextcloud/clubsuite-training) | Trainingsplanung |
| [clubsuite-donations](https://github.com/ClubSuite-for-Nextcloud/clubsuite-donations) | Spendenverwaltung |

## 📄 Lizenz

AGPL v3 – Siehe [LICENSE](LICENSE)

## 🐛 Bugs & Feature Requests

Bitte erstellen Sie ein [Issue](https://github.com/ClubSuite-for-Nextcloud/clubsuite-core/issues) auf GitHub.

---

© 2026 Stefan Schulz
