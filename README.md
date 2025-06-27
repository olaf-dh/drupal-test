# Translation API

Ein benutzerdefiniertes Drupal-Modul zur Verwaltung mehrsprachiger Inhalte über eine REST-API mit
einfachem Caching und Demo-Daten.

---

## 🔧 Voraussetzungen

- PHP >= 8.3
- Composer
- [DDEV](https://ddev.readthedocs.io/en/stable/)
- Drupal 11
- Drush (für CLI-Kommandos)

---

## Projekt mit DDEV einrichten

### 1. Projektverzeichnis vorbereiten

```bash
mkdir drupal-test
cd drupal-test
```

### 2. Projekt von GitHub clonen

```bash
git clone https://github.com/olaf-dh/drupal-test.git .
```

### 3. DDEV starten und Abhängigkeiten installieren

```bash
ddev start
ddev exec composer install
```

### 4. Drupal mit bereitgestellter Konfiguration installieren

Das Modul bringt vorkonfigurierte Felder, Inhaltstypen und Taxonomien mit. Die vollständige
Konfiguration ist im Verzeichnis `modules/custom/translation_api/config/sync` hinterlegt.
Diese wird beim `site:install` übernommen:

```bash
ddev exec drush site:install --existing-config --account-name=admin --account-pass=admin --site-name="Translation API"
```

Dadurch wird:

- Drupal installiert
- die Konfiguration aus `modules/custom/translation_api/config/sync` übernommen (Content-Type, Felder, Modulstatus)
- das Modul automatisch aktiviert
- Demo-Daten durch `translation_api.install` erstellt

---

## Funktionen

- Inhaltstyp: **Translation Item** mit Feldern:

  - field_key (Text, eindeutig)
  - field_category (Taxonomy-Term Referenz)
  - field_de / field_en / field_fr / field_it

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

- Caching: Responses werden im Drupal-Cache gespeichert und bei Node-Änderungen automatisch
invalidiert

---

## Modulstruktur

```
translation_api/
├── config/sync/                # Vollständige Konfiguration - inkl. Inhaltstyp, Felder etc.
├── src/Controller/             # REST-API Controller
├── src/Service/                # Serviceklassen mit Logik
├── translation_api.info.yml
├── translation_api.install     # Erstellt Demo-Inhalte
├── translation_api.routing.yml
├── translation_api.services.yml
```

---

## Admin-Menüeintrag

Nach der Installation des Moduls findest du im Administrationsmenü unter:

```
Admin ▸ Inhalt ▸ Übersetzungen
```

…eine Tabelle mit allen vorhandenen Übersetzungseinträgen (`translation_item`). Angezeigt werden:

- **Übersetzungsschlüssel** (`field_key`)
- Zugewiesene **Kategorie**
- Texte der vier Sprachfelder (DE, EN, FR, IT)

Die Seite wird über den internen Pfad `/admin/content/translations` bereitgestellt und
benötigt keine zusätzliche Konfiguration.

---

## Deinstallation

```bash
ddev exec drush pmu translation_api -y
```

Dabei werden:

- Alle Demo-Nodes gelöscht
- Konfiguration bleibt erhalten (optional manuell entfernbar)

---

## Hinweis

Dieses Projekt liefert vollständige Konfigurationsdateien.
Bitte installiere Drupal mit `--existing-config` – ein regulärer `site:install` mit anschließendem
`config:import` führt zu Fehlern (UUID, fehlende system.site).

---

## Implementierungsentscheidungen

- Das Modul verwendet **einen speziell für dieses Modul erstellten Inhaltstyp** (`translation_item`)
mit Sprachfeldern statt Drupal-Core-Übersetzungen, um die Anforderungen der Testaufgabe einfach umzusetzen.
- Die API wurde mit **Controller** und **Services** realisiert, um eine saubere Trennung von Logik und
Ausgabeschicht zu gewährleisten.
- Die Antwortformate sind so gestaltet, dass sie für eine Headless-Integration oder Frontend-Anbindung leicht
verarbeitbar sind.
- Der **Drupal-Cache** wird verwendet, um unnötige Datenbankabfragen zu vermeiden. Cache-Tags
sorgen dafür, dass Inhalte bei Änderungen sofort neu geladen werden.
- Die Konfiguration wird über `modules/custom/translation_api/config/sync` bereitgestellt, damit
das Modul vollständig mit Inhaltstyp, Feldern und Demo-Inhalten rekonstruierbar ist.

---

## API‑Authentifizierung per Schlüssel

Die API erfordert einen gültigen **API‑Key** im Request‑Header. Dadurch wird unautorisierter
Zugriff verhindert.

### API-Key definieren

Füge in deiner Datei `web/sites/default/settings.local.php` folgende Zeile hinzu:

```php
$settings['translation_api_key'] = 'geheim123';
```

> Nutze niemals `settings.php` direkt für geheime Schlüssel. Die Datei `settings.local.php` ist in
> `.gitignore` enthalten und sicher.

### Beispiel‑Header in Postman

```http
X-API-Key: geheim123
```

> Jeder Request ohne gültigen Key erhält HTTP-Status `403 Forbidden` zurück.

- Öffne den Tab „Headers“
- Trage ein: `Key: X-API-Key`, `Value: geheim123`

---

## API-Endpunkte testen in Postman

> **Tipp:** Lege dir eine Postman‑Environment‑Variable `{{base_url}}` an, z. B. `http://drupal-test.ddev.site`.
>
> So kannst du die Beispiele direkt kopieren, ohne die Domain jedes Mal anzupassen.

| Zweck | Methode & URL | Schritte in Postman                                                                                                                                                      |
|-------|---------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Alle Übersetzungen** | `GET {{base_url}}/api/translations` | 1. Neue **Request** <br>2. Methode =`GET` <br>3. URL einfügen <br>4. *Params* leer lassen <br>5. Tab **Headers** → `Accept: application/json` (optional) <br>6. **Send** |
| **Kategorie‑Filter** | `GET {{base_url}}/api/translations?category=general` | Wie oben, zusätzlich im *Params*‑Tab:<br>`key=category`, `value=general`                                                                                                 |
| **Einzelne Übersetzung** | `GET {{base_url}}/api/translations/welcome_message` | 1. Neue **Request** <br>2. Methode = `GET` <br>3. URL einfügen <br>4. **Send**                                                                                           |
