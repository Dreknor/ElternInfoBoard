# Theme-System Konzept für elterninfo

**Branch:** `feature/theme-system`  
**Stand:** Mai 2026  
**Status:** Konzeptphase

---

## 1. Ausgangssituation & Zielsetzung

### Aktueller Stand
Das Projekt nutzt aktuell ein gemischtes CSS-Framework-System bestehend aus:
- **Bootstrap 4** (Paper Dashboard) – Grundlayout, Grid, Komponenten
- **Tailwind CSS v3** – Utility-First, in `resources/css/app.css` via Vite
- **CSS Custom Properties** – bereits vorhanden (`--app-bg`, `--app-text` in `:root`)
- **Feste Farbwerte** – Sidebar `bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900`, Navbar `bg-white`, Akzentfarbe Blau

### Ziel
Ein **erweiterbares Theme-System**, das:
1. Einen **systemweiten Standard-Theme** durch einen Admin konfigurierbar macht
2. Jedem **Nutzer** erlaubt, seinen eigenen Theme zu wählen (sofern vom Admin erlaubt)
3. **Nachträglich neue Themes** ohne Code-Änderungen im Core ermöglicht
4. Rückwärtskompatibel zum bestehenden Design ist (aktuelles Design = Theme `default`)

---

## 2. Architekturübersicht

```
┌─────────────────────────────────────────────────────────────┐
│                     Theme-System                             │
│                                                              │
│  ┌──────────────┐    ┌──────────────┐    ┌───────────────┐  │
│  │  ThemeRegistry│    │ ThemeService │    │  ThemeMiddle- │  │
│  │              │◄───│              │◄───│  ware         │  │
│  │ themes/*.php │    │ resolveTheme │    │ (setzt auf    │  │
│  └──────────────┘    └──────────────┘    │  jeder Seite) │  │
│                             │            └───────────────┘  │
│                    ┌────────▼─────────┐                     │
│                    │  CSS-Variablen   │                     │
│                    │  im <head> via   │                     │
│                    │  Blade-Component │                     │
│                    └──────────────────┘                     │
│                                                              │
│  ┌──────────────────┐       ┌──────────────────────────────┐│
│  │  GeneralSetting  │       │     UserAppSettings           ││
│  │  +default_theme  │       │     settings.theme = 'dark'   ││
│  └──────────────────┘       └──────────────────────────────┘│
└─────────────────────────────────────────────────────────────┘
```

---

## 3. Theme-Definition

Jedes Theme ist eine **PHP-Klasse**, die ein Interface implementiert und ein strukturiertes Array mit CSS-Werten zurückgibt.

### 3.1 Interface `ThemeInterface`

```php
// app/Themes/Contracts/ThemeInterface.php

namespace App\Themes\Contracts;

interface ThemeInterface
{
    /** Interner Identifier, z.B. 'default', 'dark', 'nature' */
    public function id(): string;

    /** Anzeigename für Admin/User UI */
    public function name(): string;

    /** Kurze Beschreibung */
    public function description(): string;

    /** Optional: Pfad zur Vorschau-Grafik (public/) */
    public function previewImage(): ?string;

    /** Alle CSS Custom Properties als key => value Array */
    public function variables(): array;

    /** Optionale Tailwind-Klassen die dynamisch auf <body> gesetzt werden */
    public function bodyClasses(): string;
}
```

### 3.2 Basis-Theme-Klasse

```php
// app/Themes/AbstractTheme.php

namespace App\Themes;

use App\Themes\Contracts\ThemeInterface;

abstract class AbstractTheme implements ThemeInterface
{
    public function previewImage(): ?string { return null; }
    public function bodyClasses(): string { return ''; }
}
```

### 3.3 Struktur der CSS-Variablen pro Theme

Jedes Theme definiert folgende CSS Custom Properties:

| Variable | Bedeutung | Default-Wert |
|---|---|---|
| `--color-primary` | Primärfarbe (Buttons, Links, aktive Nav) | `#2563eb` |
| `--color-primary-dark` | Hover-Variante Primär | `#1d4ed8` |
| `--color-primary-light` | Hintergrund-Akzent | `#eff6ff` |
| `--color-secondary` | Sekundärfarbe | `#6366f1` |
| `--color-sidebar-bg` | Sidebar Hintergrundfarbe (von) | `#111827` |
| `--color-sidebar-bg-mid` | Sidebar Hintergrundfarbe (mitte) | `#1f2937` |
| `--color-sidebar-text` | Sidebar Textfarbe | `#d1d5db` |
| `--color-sidebar-active-bg` | Aktiver Nav-Link Hintergrund | `#2563eb` |
| `--color-navbar-bg` | Topbar Hintergrundfarbe | `#ffffff` |
| `--color-navbar-text` | Topbar Textfarbe | `#1f2937` |
| `--color-navbar-border` | Topbar Rahmenfarbe | `#e5e7eb` |
| `--color-body-bg` | Seitenhintergrund | `#f3f4f6` |
| `--color-card-bg` | Karten-Hintergrund | `#ffffff` |
| `--color-card-border` | Karten-Rahmen | `#e5e7eb` |
| `--color-text-primary` | Haupt-Textfarbe | `#111827` |
| `--color-text-secondary` | Sekundäre Textfarbe | `#6b7280` |
| `--color-mobile-nav-bg` | Mobile Bottom-Nav Hintergrund | `#ffffff` |
| `--border-radius-base` | Basis-Rundung | `0.5rem` |
| `--font-family-base` | Schriftart | `'Newsreader', ui-serif, serif` |

---

## 4. Konkrete Theme-Implementierungen

### 4.1 Theme: `default` (aktuell → Blau/Dunkel)

```php
// app/Themes/DefaultTheme.php

namespace App\Themes;

class DefaultTheme extends AbstractTheme
{
    public function id(): string { return 'default'; }
    public function name(): string { return 'Standard (Blau)'; }
    public function description(): string { return 'Das klassische Design mit dunkler Sidebar und blauen Akzenten.'; }
    public function previewImage(): ?string { return '/img/themes/preview-default.png'; }

    public function variables(): array
    {
        return [
            '--color-primary'         => '#2563eb',
            '--color-primary-dark'    => '#1d4ed8',
            '--color-primary-light'   => '#eff6ff',
            '--color-secondary'       => '#6366f1',
            '--color-sidebar-bg'      => '#111827',
            '--color-sidebar-bg-mid'  => '#1f2937',
            '--color-sidebar-text'    => '#d1d5db',
            '--color-sidebar-active-bg' => '#2563eb',
            '--color-navbar-bg'       => '#ffffff',
            '--color-navbar-text'     => '#1f2937',
            '--color-navbar-border'   => '#e5e7eb',
            '--color-body-bg'         => '#f3f4f6',
            '--color-card-bg'         => '#ffffff',
            '--color-card-border'     => '#e5e7eb',
            '--color-text-primary'    => '#111827',
            '--color-text-secondary'  => '#6b7280',
            '--color-mobile-nav-bg'   => '#ffffff',
            '--border-radius-base'    => '0.5rem',
            '--font-family-base'      => "'Newsreader', ui-serif, Georgia, serif",
            // Spezifische App-Variablen
            '--app-bg'                => '#f3f4f6',
            '--app-text'              => '#111827',
        ];
    }
}
```

### 4.2 Theme: `dark` (Vollständig dunkles Design)

```php
// app/Themes/DarkTheme.php

namespace App\Themes;

class DarkTheme extends AbstractTheme
{
    public function id(): string { return 'dark'; }
    public function name(): string { return 'Dark Mode'; }
    public function description(): string { return 'Dunkles Design für ein augenschonendes Erlebnis bei Nacht.'; }
    public function previewImage(): ?string { return '/img/themes/preview-dark.png'; }
    public function bodyClasses(): string { return 'dark-mode'; }

    public function variables(): array
    {
        return [
            '--color-primary'         => '#3b82f6',
            '--color-primary-dark'    => '#2563eb',
            '--color-primary-light'   => '#1e3a5f',
            '--color-secondary'       => '#818cf8',
            '--color-sidebar-bg'      => '#0f172a',
            '--color-sidebar-bg-mid'  => '#1e293b',
            '--color-sidebar-text'    => '#cbd5e1',
            '--color-sidebar-active-bg' => '#3b82f6',
            '--color-navbar-bg'       => '#1e293b',
            '--color-navbar-text'     => '#f1f5f9',
            '--color-navbar-border'   => '#334155',
            '--color-body-bg'         => '#0f172a',
            '--color-card-bg'         => '#1e293b',
            '--color-card-border'     => '#334155',
            '--color-text-primary'    => '#f1f5f9',
            '--color-text-secondary'  => '#94a3b8',
            '--color-mobile-nav-bg'   => '#1e293b',
            '--border-radius-base'    => '0.5rem',
            '--font-family-base'      => "'Newsreader', ui-serif, Georgia, serif",
            '--app-bg'                => '#0f172a',
            '--app-text'              => '#f1f5f9',
        ];
    }
}
```

### 4.3 Theme: `nature` (Grünes/Natürliches Design)

```php
// app/Themes/NatureTheme.php

namespace App\Themes;

class NatureTheme extends AbstractTheme
{
    public function id(): string { return 'nature'; }
    public function name(): string { return 'Natur (Grün)'; }
    public function description(): string { return 'Ein frisches, grünes Design passend zu Kita und Schule.'; }
    public function previewImage(): ?string { return '/img/themes/preview-nature.png'; }

    public function variables(): array
    {
        return [
            '--color-primary'         => '#16a34a',
            '--color-primary-dark'    => '#15803d',
            '--color-primary-light'   => '#f0fdf4',
            '--color-secondary'       => '#0891b2',
            '--color-sidebar-bg'      => '#14532d',
            '--color-sidebar-bg-mid'  => '#166534',
            '--color-sidebar-text'    => '#bbf7d0',
            '--color-sidebar-active-bg' => '#16a34a',
            '--color-navbar-bg'       => '#ffffff',
            '--color-navbar-text'     => '#14532d',
            '--color-navbar-border'   => '#bbf7d0',
            '--color-body-bg'         => '#f0fdf4',
            '--color-card-bg'         => '#ffffff',
            '--color-card-border'     => '#bbf7d0',
            '--color-text-primary'    => '#14532d',
            '--color-text-secondary'  => '#15803d',
            '--color-mobile-nav-bg'   => '#ffffff',
            '--border-radius-base'    => '0.75rem',
            '--font-family-base'      => "'Newsreader', ui-serif, Georgia, serif",
            '--app-bg'                => '#f0fdf4',
            '--app-text'              => '#14532d',
        ];
    }
}
```

### 4.4 Theme: `warm` (Warmes Orange/Beige Design)

```php
// app/Themes/WarmTheme.php

namespace App\Themes;

class WarmTheme extends AbstractTheme
{
    public function id(): string { return 'warm'; }
    public function name(): string { return 'Warm (Orange)'; }
    public function description(): string { return 'Ein warmes, freundliches Design in Erdtönen.'; }

    public function variables(): array
    {
        return [
            '--color-primary'         => '#ea580c',
            '--color-primary-dark'    => '#c2410c',
            '--color-primary-light'   => '#fff7ed',
            '--color-secondary'       => '#d97706',
            '--color-sidebar-bg'      => '#431407',
            '--color-sidebar-bg-mid'  => '#7c2d12',
            '--color-sidebar-text'    => '#fed7aa',
            '--color-sidebar-active-bg' => '#ea580c',
            '--color-navbar-bg'       => '#fffbeb',
            '--color-navbar-text'     => '#431407',
            '--color-navbar-border'   => '#fed7aa',
            '--color-body-bg'         => '#fff7ed',
            '--color-card-bg'         => '#fffbeb',
            '--color-card-border'     => '#fed7aa',
            '--color-text-primary'    => '#431407',
            '--color-text-secondary'  => '#9a3412',
            '--color-mobile-nav-bg'   => '#fffbeb',
            '--border-radius-base'    => '0.75rem',
            '--font-family-base'      => "'Newsreader', ui-serif, Georgia, serif",
            '--app-bg'                => '#fff7ed',
            '--app-text'              => '#431407',
        ];
    }
}
```

---

## 5. ThemeRegistry

```php
// app/Themes/ThemeRegistry.php

namespace App\Themes;

use App\Themes\Contracts\ThemeInterface;
use Illuminate\Support\Collection;

class ThemeRegistry
{
    /** @var array<string, ThemeInterface> */
    private array $themes = [];

    public function __construct()
    {
        // Standard-Themes registrieren
        $this->register(new DefaultTheme());
        $this->register(new DarkTheme());
        $this->register(new NatureTheme());
        $this->register(new WarmTheme());
    }

    public function register(ThemeInterface $theme): void
    {
        $this->themes[$theme->id()] = $theme;
    }

    public function get(string $id): ThemeInterface
    {
        return $this->themes[$id] ?? $this->themes['default'];
    }

    public function all(): Collection
    {
        return collect($this->themes);
    }

    public function exists(string $id): bool
    {
        return isset($this->themes[$id]);
    }
}
```

---

## 6. ThemeService

```php
// app/Services/ThemeService.php

namespace App\Services;

use App\Model\UserAppSettings;
use App\Settings\GeneralSetting;
use App\Themes\Contracts\ThemeInterface;
use App\Themes\ThemeRegistry;
use Illuminate\Support\Facades\Auth;

class ThemeService
{
    public function __construct(
        private ThemeRegistry $registry,
        private GeneralSetting $generalSetting,
    ) {}

    /**
     * Löst den aktiven Theme auf:
     * 1. User-eigene Einstellung (wenn erlaubt)
     * 2. Globaler Admin-Standard
     * 3. Fallback: 'default'
     */
    public function resolveActive(): ThemeInterface
    {
        // Ist Nutzer eingeloggt UND darf er überschreiben?
        if (Auth::check() && ($this->generalSetting->allow_user_theme ?? true)) {
            $userSettings = UserAppSettings::where('user_id', Auth::id())->first();
            $userTheme = data_get($userSettings?->settings, 'theme');

            if ($userTheme && $this->registry->exists($userTheme)) {
                return $this->registry->get($userTheme);
            }
        }

        // Globaler Admin-Standard
        $defaultTheme = $this->generalSetting->default_theme ?? 'default';
        return $this->registry->get($defaultTheme);
    }

    /**
     * Rendert die CSS Custom Properties als <style>-Block
     */
    public function renderCssVariables(): string
    {
        $theme = $this->resolveActive();
        $vars = collect($theme->variables())
            ->map(fn ($value, $key) => "  {$key}: {$value};")
            ->implode("\n");

        return "<style id=\"theme-vars\">\n:root {\n{$vars}\n}\n</style>";
    }

    public function registry(): ThemeRegistry
    {
        return $this->registry;
    }
}
```

---

## 7. Datenbankänderungen

### 7.1 Migration: GeneralSetting erweitern

```php
// database/settings/2026_05_XX_000000_add_theme_to_general_settings.php

<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.default_theme', 'default');
        $this->migrator->add('general.allow_user_theme', true);
    }
};
```

### 7.2 GeneralSetting-Klasse aktualisieren

```php
// app/Settings/GeneralSetting.php (erweitert)

class GeneralSetting extends Settings
{
    public string $app_name;
    public string $logo;
    public string $favicon;
    public string $default_theme;   // NEU
    public bool   $allow_user_theme; // NEU

    public static function group(): string { return 'general'; }
}
```

### 7.3 UserAppSettings – keine DB-Migration nötig

Das bestehende `settings` JSON-Feld in `user_app_settings` wird genutzt:
```json
{
  "theme": "dark",
  "other_existing_settings": "..."
}
```

---

## 8. Middleware: ThemeMiddleware

```php
// app/Http/Middleware/ApplyTheme.php

namespace App\Http\Middleware;

use App\Services\ThemeService;
use Closure;
use Illuminate\Http\Request;

class ApplyTheme
{
    public function __construct(private ThemeService $themeService) {}

    public function handle(Request $request, Closure $next)
    {
        // ThemeService in View-Variable verfügbar machen
        view()->share('activeTheme', $this->themeService->resolveActive());
        view()->share('themeService', $this->themeService);

        return $next($request);
    }
}
```

**Registrierung in `bootstrap/app.php`:**
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\ApplyTheme::class,
    ]);
})
```

---

## 9. Blade-Integration

### 9.1 Theme-Variablen Komponente

```php
// app/View/Components/ThemeVars.php

namespace App\View\Components;

use App\Services\ThemeService;
use Illuminate\View\Component;

class ThemeVars extends Component
{
    public string $cssVars;
    public string $bodyClasses;

    public function __construct(ThemeService $themeService)
    {
        $theme = $themeService->resolveActive();
        $vars = collect($theme->variables())
            ->map(fn ($v, $k) => "  $k: $v;")
            ->implode("\n");
        $this->cssVars = ":root {\n$vars\n}";
        $this->bodyClasses = $theme->bodyClasses();
    }

    public function render()
    {
        return view('components.theme-vars');
    }
}
```

```blade
{{-- resources/views/components/theme-vars.blade.php --}}
<style id="theme-vars">
{!! $cssVars !!}
</style>
```

### 9.2 Einbindung in `layouts/app.blade.php`

Im `<head>`-Bereich **vor den anderen CSS-Links** einfügen:

```blade
{{-- Theme CSS Custom Properties --}}
<x-theme-vars />
```

Im `<body>`-Tag:
```blade
<body id="app-layout" class="{{ $activeTheme->bodyClasses() }}">
```

### 9.3 Anpassung der Sidebar-Klassen

Statt hartcodierter Tailwind-Klassen werden CSS-Variablen genutzt:

```blade
{{-- Vorher --}}
<div class="sidebar bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900 ...">

{{-- Nachher --}}
<div class="sidebar" style="background: linear-gradient(to bottom, var(--color-sidebar-bg), var(--color-sidebar-bg-mid), var(--color-sidebar-bg));">
```

```blade
{{-- Navbar: vorher --}}
<nav class="bg-white shadow-lg border-b border-gray-200 fixed-top">

{{-- Navbar: nachher --}}
<nav class="shadow-lg fixed-top" style="background: var(--color-navbar-bg); border-color: var(--color-navbar-border);">
```

---

## 10. Admin-Einstellungen (Settings-Tab)

### Neuer Tab `design-tab.blade.php`

```blade
{{-- resources/views/settings/tabs/design-tab.blade.php --}}

<div class="tab-pane" id="design" role="tabpanel">
    <form action="{{ url('settings/design') }}" method="post">
        @csrf
        @method('PUT')

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    Standard-Theme
                    <select name="default_theme" class="form-control">
                        @foreach($themes as $theme)
                            <option value="{{ $theme->id() }}"
                                @if($settings->default_theme === $theme->id()) selected @endif>
                                {{ $theme->name() }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">Dieses Theme wird für alle Nutzer standardmäßig verwendet.</div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100 d-flex align-items-center gap-2">
                    <input type="checkbox" name="allow_user_theme" value="1"
                           @if($settings->allow_user_theme) checked @endif>
                    Nutzer dürfen ihren eigenen Theme wählen
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">Wenn aktiv, können Nutzer in ihren Einstellungen ein abweichendes Design wählen.</div>
            </div>
        </div>

        {{-- Theme-Vorschauen --}}
        <div class="form-row mt-2 p-2">
            <div class="col-12">
                <h6>Verfügbare Themes</h6>
                <div class="d-flex flex-wrap gap-3">
                    @foreach($themes as $theme)
                        <div class="card theme-preview-card" style="width: 200px; cursor: pointer;"
                             onclick="document.querySelector('[name=default_theme]').value='{{ $theme->id() }}'">
                            @if($theme->previewImage())
                                <img src="{{ $theme->previewImage() }}" class="card-img-top" alt="{{ $theme->name() }}">
                            @else
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 120px;">
                                    <i class="fas fa-palette fa-3x text-muted"></i>
                                </div>
                            @endif
                            <div class="card-body p-2">
                                <h6 class="card-title mb-1">{{ $theme->name() }}</h6>
                                <p class="card-text small text-muted mb-0">{{ $theme->description() }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="form-row">
            <button type="submit" class="btn btn-success btn-block">
                Design-Einstellungen speichern
            </button>
        </div>
    </form>
</div>
```

---

## 11. Nutzer-Einstellungen

In `resources/views/user/settings.blade.php` wird ein neuer Abschnitt "Design" eingefügt (nur wenn `$settings->allow_user_theme` aktiv):

```blade
@if($generalSettings->allow_user_theme ?? true)
<div class="mt-6">
    <h6 class="text-base font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
        <i class="fas fa-palette text-blue-600 mr-2"></i>
        Design-Theme
    </h6>
    <form action="{{ url('/einstellungen/theme') }}" method="post">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            {{-- "Standard des Systems verwenden" Option --}}
            <label class="cursor-pointer">
                <input type="radio" name="theme" value="" class="sr-only peer"
                       @if(!$userTheme) checked @endif>
                <div class="border-2 rounded-lg p-3 text-center peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-400 transition-all">
                    <i class="fas fa-cog text-gray-400 text-2xl mb-2 block"></i>
                    <span class="text-sm font-medium text-gray-700">System-Standard</span>
                    <span class="text-xs text-gray-400 block mt-1">Wie vom Admin festgelegt</span>
                </div>
            </label>

            @foreach($themes as $theme)
            <label class="cursor-pointer">
                <input type="radio" name="theme" value="{{ $theme->id() }}" class="sr-only peer"
                       @if($userTheme === $theme->id()) checked @endif>
                <div class="border-2 rounded-lg p-3 text-center peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-400 transition-all">
                    @if($theme->previewImage())
                        <img src="{{ $theme->previewImage() }}" class="w-full h-16 object-cover rounded mb-2" alt="">
                    @else
                        <i class="fas fa-palette text-gray-400 text-2xl mb-2 block"></i>
                    @endif
                    <span class="text-sm font-medium text-gray-700">{{ $theme->name() }}</span>
                    <span class="text-xs text-gray-400 block mt-1">{{ $theme->description() }}</span>
                </div>
            </label>
            @endforeach
        </div>
        <button type="submit" class="mt-4 px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold">
            <i class="fas fa-save mr-1"></i> Theme speichern
        </button>
    </form>
</div>
@endif
```

---

## 12. Controller-Anpassungen

### 12.1 DesignSettingsController (Admin)

```php
// app/Http/Controllers/Settings/DesignSettingsController.php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Settings\GeneralSetting;
use App\Themes\ThemeRegistry;
use Illuminate\Http\Request;

class DesignSettingsController extends Controller
{
    public function update(Request $request, GeneralSetting $settings)
    {
        $this->authorize('admin');

        $request->validate([
            'default_theme'    => 'required|string',
            'allow_user_theme' => 'nullable|boolean',
        ]);

        $settings->default_theme    = $request->input('default_theme', 'default');
        $settings->allow_user_theme = $request->boolean('allow_user_theme');
        $settings->save();

        return back()->with('Meldung', 'Design-Einstellungen gespeichert.')->with('type', 'success');
    }
}
```

### 12.2 UserThemeController

```php
// app/Http/Controllers/User/UserThemeController.php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Model\UserAppSettings;
use App\Settings\GeneralSetting;
use App\Themes\ThemeRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserThemeController extends Controller
{
    public function update(
        Request $request,
        GeneralSetting $generalSetting,
        ThemeRegistry $registry
    ) {
        abort_unless($generalSetting->allow_user_theme ?? true, 403, 'Theme-Auswahl nicht erlaubt.');

        $theme = $request->input('theme', '');

        // Leerer Wert = System-Standard
        if ($theme !== '' && !$registry->exists($theme)) {
            return back()->withErrors(['theme' => 'Ungültiges Theme.']);
        }

        $userSettings = UserAppSettings::firstOrNew(['user_id' => Auth::id()]);
        $settings = $userSettings->settings ?? [];
        if ($theme === '') {
            unset($settings['theme']);
        } else {
            $settings['theme'] = $theme;
        }
        $userSettings->settings = $settings;
        $userSettings->save();

        return back()->with('Meldung', 'Design gespeichert.')->with('type', 'success');
    }
}
```

---

## 13. Service Provider

```php
// app/Providers/ThemeServiceProvider.php

namespace App\Providers;

use App\Services\ThemeService;
use App\Themes\ThemeRegistry;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ThemeRegistry::class, fn () => new ThemeRegistry());
        $this->app->singleton(ThemeService::class);
    }

    public function boot(): void
    {
        // Erweiterungspunkt: Themes aus Config laden (für nachträgliche Themes)
        $customThemes = config('themes.custom', []);
        $registry = $this->app->make(ThemeRegistry::class);
        foreach ($customThemes as $themeClass) {
            $registry->register(new $themeClass());
        }
    }
}
```

**Registrierung in `bootstrap/providers.php`:**
```php
App\Providers\ThemeServiceProvider::class,
```

---

## 14. Config-Datei für benutzerdefinierte Themes

```php
// config/themes.php

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Custom Themes
    |--------------------------------------------------------------------------
    | Hier können weitere Theme-Klassen registriert werden, ohne Core-Code
    | zu ändern. Jede Klasse muss ThemeInterface implementieren.
    |
    | Beispiel:
    | \App\Themes\CustomNatureTheme::class,
    */
    'custom' => [
        // \App\Themes\MyCustomTheme::class,
    ],
];
```

---

## 15. Routen

```php
// In routes/web.php ergänzen:

// Admin Design-Einstellungen
Route::put('settings/design', [\App\Http\Controllers\Settings\DesignSettingsController::class, 'update'])
    ->middleware(['auth', 'role:admin'])
    ->name('settings.design.update');

// Nutzer Theme-Einstellung
Route::put('einstellungen/theme', [\App\Http\Controllers\User\UserThemeController::class, 'update'])
    ->middleware('auth')
    ->name('user.theme.update');
```

---

## 16. Erweiterbarkeit – Neues Theme hinzufügen

Um ein neues Theme hinzuzufügen, sind **nur 3 Schritte** nötig:

### Schritt 1: Theme-Klasse erstellen
```php
// app/Themes/MyNewTheme.php

namespace App\Themes;

class MyNewTheme extends AbstractTheme
{
    public function id(): string { return 'my-theme'; }
    public function name(): string { return 'Mein Theme'; }
    public function description(): string { return 'Beschreibung...'; }

    public function variables(): array
    {
        return [
            '--color-primary' => '#...',
            // ... alle Variablen ...
        ];
    }
}
```

### Schritt 2: In `config/themes.php` registrieren
```php
'custom' => [
    \App\Themes\MyNewTheme::class,
],
```

### Schritt 3: Fertig!
Das Theme erscheint automatisch in den Admin-Einstellungen und in der Nutzer-Theme-Auswahl.

---

## 17. Migrations-Plan / Implementierungsschritte

### Phase 1 – Fundament (Prio: Hoch)
- [ ] `ThemeInterface`, `AbstractTheme` erstellen
- [ ] `DefaultTheme` erstellen (1:1 aktuelles Design)
- [ ] `ThemeRegistry` erstellen
- [ ] `ThemeService` erstellen
- [ ] `ThemeServiceProvider` erstellen & registrieren
- [ ] `config/themes.php` erstellen
- [ ] Settings-Migration für `default_theme` + `allow_user_theme`
- [ ] `GeneralSetting` anpassen

### Phase 2 – Integration (Prio: Hoch)
- [ ] `ApplyTheme`-Middleware erstellen & registrieren
- [ ] `ThemeVars` Blade-Komponente erstellen
- [ ] `layouts/app.blade.php` anpassen (Theme-Variablen einbinden)
- [ ] `resources/css/app.css` – bestehende Farbwerte auf CSS-Variablen umstellen
- [ ] Sidebar- und Navbar-Klassen auf CSS-Variablen umstellen

### Phase 3 – Themes & UI (Prio: Mittel)
- [ ] `DarkTheme` implementieren
- [ ] `NatureTheme` implementieren
- [ ] `WarmTheme` implementieren
- [ ] Theme-Vorschau-Bilder erstellen (`public/img/themes/`)
- [ ] Admin-Tab „Design" in Settings-UI einbauen
- [ ] `DesignSettingsController` erstellen
- [ ] Nutzer-Theme-Auswahl in `user/settings.blade.php` einbauen
- [ ] `UserThemeController` erstellen
- [ ] Routen registrieren

### Phase 4 – Dark-Mode Feinschliff (Prio: Niedrig)
- [ ] Alle Blade-Views auf CSS-Variablen prüfen und ggf. anpassen
- [ ] Bootstrap-Komponenten im Dark Mode anpassen (`dark-mode` CSS-Klasse)
- [ ] Formulare, Modals, Tabellen im Dark Mode testen
- [ ] Mobile Bottom-Nav im Dark Mode anpassen

### Phase 5 – Tests & Dokumentation (Prio: Mittel)
- [ ] Unit-Tests für `ThemeRegistry` und `ThemeService`
- [ ] Feature-Test für Admin-Einstellungen
- [ ] Feature-Test für User-Theme-Einstellung
- [ ] README / Entwickler-Dokumentation aktualisieren

---

## 18. Technische Überlegungen & Entscheidungen

### Warum CSS Custom Properties?
- **Null Build-Overhead**: Keine neuen Tailwind-Konfigurationen nötig
- **Dynamisch zur Laufzeit**: Kein Re-Build bei Theme-Wechsel
- **Browser-Support**: 97%+ aller modernen Browser
- **Einfach erweiterbar**: Neue Variablen können jederzeit hinzugefügt werden

### Warum keine Tailwind Dark Mode Klassen?
- Das Projekt mischt Bootstrap und Tailwind – reine Tailwind-Dark-Mode-Klassen würden Bootstrap-Elemente nicht abdecken
- CSS Custom Properties funktionieren mit beiden Frameworks gleichzeitig

### Warum PHP-Klassen statt JSON/YAML für Themes?
- **Typsicherheit** und IDE-Unterstützung
- **Vererbung** möglich (z.B. `DarkNatureTheme extends NatureTheme`)
- **Validierung** im Konstruktor möglich
- **Methoden** für dynamische Werte (z.B. saisonale Themes)

### Performance
- CSS-Variablen werden einmalig im `<head>` als `<style>`-Block gerendert (~300 Bytes)
- Kein zusätzlicher HTTP-Request
- Theme-Auflösung wird durch Laravel-Caching (SessionCache) optimierbar

### Cookie-Alternative (optional, Phase 6)
Für Theme-Wechsel ohne Login könnte zusätzlich ein **Cookie** gespeichert werden, sodass auch Gäste ein Theme wählen können. Dies ist optional und kann in einer späteren Phase implementiert werden.

---

## 19. Dateistruktur (nach Implementierung)

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Settings/
│   │   │   └── DesignSettingsController.php  (NEU)
│   │   └── User/
│   │       └── UserThemeController.php        (NEU)
│   └── Middleware/
│       └── ApplyTheme.php                     (NEU)
├── Providers/
│   └── ThemeServiceProvider.php               (NEU)
├── Services/
│   └── ThemeService.php                       (NEU)
├── Themes/
│   ├── Contracts/
│   │   └── ThemeInterface.php                 (NEU)
│   ├── AbstractTheme.php                      (NEU)
│   ├── DefaultTheme.php                       (NEU)
│   ├── DarkTheme.php                          (NEU)
│   ├── NatureTheme.php                        (NEU)
│   └── WarmTheme.php                          (NEU)
├── View/
│   └── Components/
│       └── ThemeVars.php                      (NEU)
└── Settings/
    └── GeneralSetting.php                     (GEÄNDERT: +default_theme, +allow_user_theme)

config/
└── themes.php                                 (NEU)

database/
└── settings/
    └── 2026_05_XX_add_theme_to_general_settings.php  (NEU)

resources/
├── css/
│   └── app.css                                (GEÄNDERT: Farbwerte → CSS-Variablen)
└── views/
    ├── components/
    │   └── theme-vars.blade.php               (NEU)
    ├── layouts/
    │   └── app.blade.php                      (GEÄNDERT: ThemeVars + body-class)
    ├── settings/
    │   └── tabs/
    │       └── design-tab.blade.php           (NEU)
    └── user/
        └── settings.blade.php                 (GEÄNDERT: +Theme-Auswahl)

public/
└── img/
    └── themes/
        ├── preview-default.png               (NEU)
        ├── preview-dark.png                  (NEU)
        ├── preview-nature.png                (NEU)
        └── preview-warm.png                  (NEU)
```

---

## 20. Offene Fragen / Entscheidungsbedarf

| # | Frage | Optionen | Empfehlung |
|---|---|---|---|
| 1 | Dürfen alle Nutzer-Rollen ein Theme wählen? | Nur Admin / Alle / Konfigurierbar | Konfigurierbar via `allow_user_theme` |
| 2 | Soll Dark-Mode auch dem System-OS-Präferenz folgen? | Ja (prefers-color-scheme) / Nein | Optional, Phase 4 |
| 3 | Sollen Theme-Vorschauen Screenshots sein oder CSS-generierte Previews? | Screenshots / Live-Preview | Screenshots vorerst |
| 4 | Sollen Nutzer den Theme-Wechsel sofort sehen (AJAX)? | Ja / Nein (Reload) | Ja (LiveWire oder AJAX) |
| 5 | Soll es einen Theme-Import per JSON geben (für Schulen)? | Ja / Nein | Spätere Phase |

---

*Dieses Konzept liegt auf Branch `feature/theme-system` und ist bereit zur Diskussion und Umsetzung.*

