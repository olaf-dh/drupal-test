# Translation API

Ein benutzerdefiniertes Drupal-Modul zur Verwaltung mehrsprachiger Inhalte über eine REST-API mit einfachem Caching und Demo-Daten.

---

## 🔧 Voraussetzungen

- PHP >= 8.3
- Composer
- [DDEV](https://ddev.readthedocs.io/en/stable/)
- Drupal 11
- Drush (für CLI-Kommandos)

---

## 🚧 Projekt mit DDEV einrichten

### 1. Projektverzeichnis vorbereiten

```bash
mkdir translation-api-project
cd translation-api-project
```

### 2. Projekt von GitHub clonen

```bash
git clone https://github.com/olaf-dh/drupal-test.git .
```

### 3. Setup abschließen

```bash
ddev start
ddev exec composer install
ddev exec drush site:install --account-name=admin --account-pass=admin --site-name="Translation API"
```

---

## 🎓 Modulinstallation

### 1. Konfiguration importieren

Das Modul bringt vorkonfigurierte Felder, Inhaltstypen und Taxonomien mit. Diese kannst du importieren:

```bash
ddev exec drush config:import --source=web/modules/custom/translation_api/config/sync
```

### 2. Modul aktivieren

```bash
ddev exec drush en translation_api -y
```

Das Modul legt automatisch an:

- Inhaltstyp `Translation Item`
- Taxonomie `Translation Categories`
- 3 Beispiel-Nodes mit jeweils 4 Übersetzungen

---

## 🛠️ Funktionen

- Inhaltstyp: **Translation Item** mit Feldern:

  - field\_key (Text, eindeutig)
  - field\_category (Taxonomy-Term Referenz)
  - field\_de / field\_en / field\_fr / field\_it

- REST-API-Endpunkte:

  - Alle Übersetzungen:
    ```
    GET /api/translations
    Optionaler Filter: ?category=general
    ```
  - Einzelne Übersetzung:
    ```
    GET /api/translations/welcome_message
    ```

- API-Response-Format:

```json
{
  "items": [
    {
      "key": "welcome_message",
      "category": "general",
      "translations": {
        "de": "Willkommen",
        "en": "Welcome",
        "fr": "Bienvenue",
        "it": "Benvenuto"
      }
    }
  ]
}
```

- Caching: Responses werden im Drupal-Cache gespeichert und bei Node-Änderungen automatisch invalidiert

---

## 📂 Modulstruktur

```
translation_api/
├── config/sync/                # Konfigurationen für Felder, Inhaltstyp etc.
├── src/Controller/             # REST-API Controller
├── src/Service/                # Serviceklasse mit Logik
├── translation_api.info.yml
├── translation_api.install     # Erstellt Demo-Inhalte
├── translation_api.routing.yml
├── translation_api.services.yml
```

---

## 🔙 Deinstallation

```bash
ddev exec drush pmu translation_api -y
```

Dabei werden:

- Alle Demo-Nodes gelöscht
- Konfiguration bleibt erhalten (optional manuell entfernbar)

---
