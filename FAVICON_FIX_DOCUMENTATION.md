# Favicon 404-Fehler Behebung

## Problem
In der Live-Umgebung trat häufig der Fehler 404 für das Favicon `https://eltern.esz-radebeul.de/img/20241202093712_favicon.png` auf, obwohl das Favicon im Webformular gesetzt wurde und teilweise im Browser angezeigt wurde.

## Ursache
Das Problem hatte mehrere Ursachen:

1. **Verwaiste Dateireferenzen**: Die Datenbank verwies auf Favicon-Dateien, die nicht mehr im Storage existierten
2. **Fehlende Bereinigung**: Alte Favicon-Dateien wurden beim Hochladen neuer Dateien nicht gelöscht
3. **Kein Fallback**: Wenn eine Favicon-Datei fehlte, gab es keinen Fallback zum Standard-Logo
4. **Browser-Caching**: Browser cachten alte Favicon-URLs ohne Versionierung

## Implementierte Lösungen

### 1. Automatische Löschung alter Dateien (SettingsController.php)
Beim Hochladen eines neuen Favicons oder Logos wird die alte Datei automatisch gelöscht:

```php
if ($request->hasFile('favicon')) {
    // Alte Favicon-Datei löschen (außer Standard-Favicon)
    if ($settings->favicon && $settings->favicon !== 'app_logo.png' && Storage::disk('public')->exists('img/' . $settings->favicon)) {
        Storage::disk('public')->delete('img/' . $settings->favicon);
    }
    
    // Neue Datei hochladen...
}
```

### 2. Fallback zum Standard-Logo (app.blade.php, layout.blade.php)
Die Layouts prüfen jetzt, ob die konfigurierte Favicon-Datei existiert. Falls nicht, wird automatisch das Standard-Logo verwendet:

```php
@php
    if (!Storage::disk('public')->exists('img/' . $settings->favicon)) {
        // Fallback zum Standard-Logo
        $faviconUrl = asset('img/app_logo.png');
    }
@endphp
```

### 3. Cache-Busting
Favicon-URLs enthalten jetzt einen Versions-Parameter basierend auf dem letzten Änderungsdatum der Datei:

```html
<link rel="shortcut icon" href="{{$faviconUrl}}?v={{$faviconVersion}}" type="image/x-icon">
```

### 4. Wartungs-Commands

#### `php artisan favicon:cleanup`
Bereinigt alte, nicht mehr verwendete Favicon- und Logo-Dateien aus dem Storage:
- Löscht alle Dateien im Format `YYYYMMDDHHiiss_(favicon|logo).*`
- Behält das aktuell konfigurierte Favicon und Logo
- Behält Standard-Dateien wie `app_logo.png`

#### `php artisan favicon:validate`
Validiert, ob die konfigurierten Favicon- und Logo-Dateien existieren:
- Prüft, ob die in der Datenbank gespeicherten Dateien im Storage vorhanden sind
- Setzt fehlende Dateien automatisch auf das Standard-Logo zurück
- Loggt alle Änderungen

## Deployment-Schritte für die Live-Umgebung

1. **Code deployen**:
   ```bash
   git pull origin main
   ```

2. **Cache löschen**:
   ```bash
   php artisan view:clear
   php artisan cache:clear
   ```

3. **Favicon validieren**:
   ```bash
   php artisan favicon:validate
   ```
   Dies setzt das Favicon auf `app_logo.png` zurück, falls die aktuelle Datei fehlt.

4. **Alte Dateien bereinigen** (optional):
   ```bash
   php artisan favicon:cleanup
   ```

5. **Neues Favicon hochladen** (falls gewünscht):
   - Im Admin-Bereich zu Settings → Allgemein navigieren
   - Neues Favicon hochladen
   - Die alte Datei wird automatisch gelöscht

## Empfohlene Wartung

Es wird empfohlen, regelmäßig (z.B. monatlich) den Cleanup-Command auszuführen:

```bash
php artisan favicon:cleanup
```

Optional kann auch ein Cronjob eingerichtet werden, der `favicon:validate` täglich ausführt, um sicherzustellen, dass keine verwaisten Referenzen existieren.

## Dateien geändert

1. **app/Http/Controllers/SettingsController.php** - Automatische Löschung alter Dateien
2. **resources/views/layouts/app.blade.php** - Fallback-Logik und Cache-Busting
3. **resources/views/layouts/layout.blade.php** - Fallback-Logik und Cache-Busting
4. **app/Console/Commands/CleanupOldFavicons.php** - Neuer Command (NEU)
5. **app/Console/Commands/ValidateFaviconCommand.php** - Neuer Command (NEU)

## Wichtige Hinweise

- Das Standard-Favicon ist `public/img/app_logo.png` und sollte immer vorhanden sein
- Hochgeladene Favicons werden im Format `YYYYMMDDHHiiss_favicon.ext` gespeichert
- Die Dateien werden in `storage/app/public/img/` gespeichert und über den Symlink `public/storage` zugänglich gemacht
- Der Symlink muss existieren: `php artisan storage:link`

## Testen der Lösung

Nach dem Deployment:

1. Öffne die Website im Browser
2. Öffne die Developer Tools (F12) → Network Tab
3. Lade die Seite neu (Strg+F5 für Hard Refresh)
4. Prüfe, ob das Favicon ohne 404-Fehler geladen wird
5. Die URL sollte einen `?v=` Parameter enthalten

