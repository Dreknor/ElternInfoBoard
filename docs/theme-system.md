# Theme-System: Entwickler-Dokumentation

**Status:** Phase 1–5 implementiert (Mai 2026)  
**Branch:** `feature/theme-system`  
**Konzept:** [theme-system-konzept.md](theme-system-konzept.md)

---

## 1. Überblick

Das Theme-System erlaubt es, das visuelle Design der App über CSS Custom Properties dynamisch
zu wechseln. Es bietet:

- **System-Standard-Theme** (vom Admin in den Einstellungen wählbar)
- **Pro-Nutzer-Theme** (in den persönlichen Einstellungen, sofern vom Admin erlaubt)
- **Erweiterbarkeit** über `config/themes.php` ohne Core-Änderungen
- **Vier mitgelieferte Themes**: `default`, `dark`, `nature`, `warm`

---

## 2. Architektur

```
HTTP-Request
   │
   ▼
ApplyTheme-Middleware (web-Group)
   │  ├─ ruft ThemeService::resolveActive()
   │  └─ teilt $activeTheme + $themeService an alle Views
   ▼
Blade-Layout app.blade.php
   │  ├─ <x-theme-vars />   → :root { --color-* … }  (NACH @vite, überschreibt Defaults)
   │  ├─ <html class="dark"> wenn aktiver Theme = dark
   │  └─ <body class="… {{ $activeTheme->bodyClasses() }}">
   ▼
Sidebar / Navbar / Karten verwenden var(--color-*) Inline-Styles
```

### Beteiligte Klassen

| Datei | Zweck |
|---|---|
| `app/Themes/Contracts/ThemeInterface.php` | Interface für alle Themes |
| `app/Themes/AbstractTheme.php` | Basis-Implementierung (Default-Methoden) |
| `app/Themes/DefaultTheme.php` / `DarkTheme` / `NatureTheme` / `WarmTheme` | Konkrete Themes |
| `app/Themes/ThemeRegistry.php` | Registry – sammelt alle Themes (Standard + Custom aus Config) |
| `app/Services/ThemeService.php` | Auflösung des aktiven Themes (User > Admin > Fallback) |
| `app/Providers/ThemeServiceProvider.php` | Bindet Registry + Service als Singletons; lädt Custom-Themes |
| `app/Http/Middleware/ApplyTheme.php` | Teilt aktives Theme an alle Views |
| `app/View/Components/ThemeVars.php` + `resources/views/components/theme-vars.blade.php` | Rendert `:root { … }` Block |

### Controller & Routen

| Methode | Route | Controller | Middleware |
|---|---|---|---|
| `PUT` | `/settings/design` | `Settings\DesignSettingsController::update` | `auth`, `permission:edit settings` |
| `PUT` | `/einstellungen/theme` | `User\UserThemeController::update` | `auth` |

---

## 3. CSS-Variablen

Jedes Theme liefert ein Array von CSS Custom Properties, die im `<head>` als
`<style id="theme-vars">:root { … }</style>` injiziert werden. Diese Properties überschreiben
die Defaults aus `resources/css/app.css`.

Wichtigste Variablen:

| Variable | Verwendung |
|---|---|
| `--color-primary` | Buttons, Links, aktive Nav-Items |
| `--color-primary-dark` | Hover Primärfarbe |
| `--color-primary-light` | Hintergrund-Akzente |
| `--color-secondary` | Sekundärfarbe |
| `--color-sidebar-bg`, `-bg-mid` | Sidebar-Gradient (oben/mitte/unten) |
| `--color-sidebar-border`, `-text`, `-text-muted` | Sidebar-Border + Text-Farben |
| `--color-sidebar-logo-bg`, `-logo-border` | Sidebar-Logo-Box |
| `--color-sidebar-footer-bg`, `-footer-border` | Sidebar-Footer |
| `--color-sidebar-active-bg` | Aktiver Nav-Link Hintergrund |
| `--color-navbar-bg`, `-text`, `-border` | Top-Navbar |
| `--color-body-bg` | Hauptbereich-Hintergrund |
| `--color-card-bg`, `-border` | Karten |
| `--color-text-primary`, `-secondary` | Texte |
| `--color-mobile-nav-bg` | Mobile Bottom-Nav |
| `--border-radius-base` | Globale Rundung |
| `--font-family-base` | Schriftart |
| `--app-bg`, `--app-text` | App-Body Hintergrund + Text |

In Blade-Views Verwendung typischerweise als Inline-Style:

```blade
<div style="background: var(--color-card-bg); color: var(--color-text-primary);">…</div>
```

oder über Tailwind arbitrary-values:

```blade
<body class="bg-[var(--app-bg)] text-[var(--app-text)]">
```

---

## 4. Auflösung des aktiven Themes

`ThemeService::resolveActive()` Priorität (von hoch nach niedrig):

1. **User-Präferenz** aus `user_app_settings.settings.theme` – nur wenn
   `GeneralSetting::allow_user_theme === true`
2. **Globaler Admin-Standard** aus `GeneralSetting::default_theme`
3. **Fallback:** `default`

Das Ergebnis wird pro Request innerhalb des `ThemeService` gecached
(`$resolvedCache`).

---

## 5. Neues Theme hinzufügen

### Schritt 1: Theme-Klasse erstellen

```php
// app/Themes/SchoolBlueTheme.php
namespace App\Themes;

class SchoolBlueTheme extends AbstractTheme
{
    public function id(): string          { return 'school-blue'; }
    public function name(): string        { return 'Schul-Blau'; }
    public function description(): string { return 'Frisches Hellblau'; }
    public function previewImage(): ?string { return '/img/themes/preview-school-blue.png'; }

    public function variables(): array
    {
        return [
            '--color-primary'        => '#0284c7',
            '--color-primary-dark'   => '#0369a1',
            '--color-primary-light'  => '#e0f2fe',
            // ... alle weiteren Variablen siehe DefaultTheme.php
            '--app-bg'               => '#f0f9ff',
            '--app-text'             => '#0c4a6e',
        ];
    }

    // Optional: Body-CSS-Klassen (z.B. 'dark-mode' für globale Overrides)
    public function bodyClasses(): string { return ''; }
}
```

> **Hinweis:** Es empfiehlt sich, von `DefaultTheme::variables()` als Vorlage
> zu kopieren und nur die abweichenden Werte zu ändern.

### Schritt 2: In `config/themes.php` registrieren

```php
return [
    'custom' => [
        \App\Themes\SchoolBlueTheme::class,
    ],
];
```

### Schritt 3: Fertig

Der Theme erscheint automatisch:

- im Admin-Tab **Design** (`Einstellungen → Design`)
- in der Nutzer-Themeauswahl (`Persönliche Einstellungen → Design-Theme`),
  sofern `allow_user_theme = true`

---

## 6. Datenbank

### Settings (Spatie/Laravel-Settings)

Migration `database/settings/2026_05_19_120000_add_theme_to_general_settings.php`:

```php
$this->migrator->add('general.default_theme', 'default');
$this->migrator->add('general.allow_user_theme', true);
```

`App\Settings\GeneralSetting`:

```php
public string $default_theme = 'default';
public bool   $allow_user_theme = true;
```

### User-Theme

Bestehendes JSON-Feld `user_app_settings.settings`:

```json
{ "theme": "dark" }
```

Bei `theme = ''` (leerer String) wird der Eintrag entfernt → System-Standard wirkt.

---

## 7. Caching / Performance

- CSS-Variablen werden pro Request ein einziges Mal als ~300-700 Byte `<style>`-Block
  in den `<head>` gerendert
- `ThemeService` cached die Auflösung pro Request-Instanz (`resolvedCache`)
- `ThemeRegistry` ist Singleton (`ThemeServiceProvider::register`)
- Spatie-Settings sind `scoped` – pro Request einmalig aus DB geladen

---

## 8. Tests

Test-Suite im Repository:

| Datei | Inhalt |
|---|---|
| `tests/Unit/ThemeRegistryTest.php` | Registry: Existenz, Lookup, Fallback, Custom-Theme-Registrierung, Pflicht-Variablen |
| `tests/Unit/ThemeServiceTest.php` | Auflösungs-Priorität (Guest/User/Admin), Allow-Flag, Render-CSS |
| `tests/Feature/DesignSettingsTest.php` | Admin-Routen (Update, Disable, Validation, Permission, Guest-Redirect) |
| `tests/Feature/UserThemeTest.php` | User-Routen (Wahl, Reset, Validation, Allow-Flag, Guest-Redirect) |

Ausführen:

```bash
php artisan test --filter="ThemeRegistryTest|ThemeServiceTest|DesignSettingsTest|UserThemeTest"
```

> **Wichtig für Test-Schreiber:** Da `spatie/laravel-settings` Settings als `scoped`
> Instanzen registriert, müssen Tests zwischen Setup-Änderungen und HTTP-Requests
> `$this->app->forgetScopedInstances()` aufrufen (in den Test-`setUp()`-Methoden bereits
> erledigt). Außerdem User mit `User::factory()->create(['changePassword' => false])`
> erstellen, da die `PasswordExpired`-Middleware sonst auf `/password/expired` umleitet.

---

## 9. Layout-Integration (was wurde geändert)

`resources/views/layouts/app.blade.php`:

1. `<x-theme-vars />` **nach** `@vite`-Block (damit Theme-Variablen die Defaults aus `app.css` überschreiben)
2. `<html class="dark">` wenn `$activeTheme->id() === 'dark'` oder `bodyClasses()` enthält `dark-mode`
3. `<body>` erhält dynamisch `{{ $activeTheme->bodyClasses() }}`
4. Sidebar nutzt jetzt `linear-gradient(…, var(--color-sidebar-bg), var(--color-sidebar-bg-mid), …)`
   für einen Gradient-Effekt – die Themes definieren beide Stops

`bootstrap/app.php`:
- `\App\Http\Middleware\ApplyTheme::class` ans Ende der web-Middleware-Liste angehängt

`bootstrap/providers.php`:
- `App\Providers\ThemeServiceProvider::class` registriert

`routes/web.php`:
- `PUT /settings/design` innerhalb der `permission:edit settings`-Gruppe
- `PUT /einstellungen/theme` innerhalb der `auth`-Gruppe

---

## 10. Optionale Vorschau-Bilder

Wenn `previewImage()` einen Pfad zurückgibt, wird das Bild im Admin-Tab und der
Nutzer-Auswahl angezeigt. Andernfalls werden **dynamische Farbswatches** aus den
Theme-Variablen gerendert (Primary, Sidebar-BG, Body-BG, Card-BG, Text).

Empfohlene Bildgröße: 200×120px, abgelegt unter `public/img/themes/preview-<id>.png`.

---

## 11. Offene Punkte / nächste Schritte (Phase 6+)

- [ ] Theme-Editor im Admin-UI (interaktiv CSS-Variablen anpassen)

---

*Datei automatisch generiert aus dem Konzept und der Implementierung in Branch `feature/theme-system`.*

