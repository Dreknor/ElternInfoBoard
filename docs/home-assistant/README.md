# Home Assistant anbinden

Home Assistant liest über die App-API v1 den Tagesstatus der Kinder, offene Aufgaben, Termine, Beiträge,
ungelesene Benachrichtigungen und den Vertretungsplan. Push-Benachrichtigungen kommen weiterhin über die App.

## Integrations-Token

ElternInfoBoard → **Einstellungen → API-Token → Neues Integrations-Token erstellen**

- **Laufzeit:** 90 Tage, 1 Jahr oder unbegrenzt. Die globale App-Laufzeit (`SANCTUM_TOKEN_EXPIRATION`, 30 Tage) gilt für Integrations-Tokens nicht.
- **Rechte:** immer nur lesen. Optional zusätzlich *Krankmeldungen abgeben* und *Benachrichtigungen als gelesen markieren*.
- Alles andere (Profil ändern, Tokens verwalten, Token-Refresh, Web-Anmeldelinks, Beiträge kommentieren …) wird mit
  `403 {"code": "integration_scope"}` abgelehnt. Umsetzung: `App\Services\IntegrationTokens`, Middleware
  `EnforceIntegrationTokenScope` (gesamte API-Gruppe).

## Einrichtung in Home Assistant

1. `secrets.yaml`: `elterninfo_bearer: "Bearer <Token>"`
2. `configuration.yaml`, unter dem vorhandenen `homeassistant:`-Block:
   ```yaml
   homeassistant:
     packages: !include_dir_named packages
   ```
3. [`elterninfo.yaml`](elterninfo.yaml) nach `/config/packages/elterninfo.yaml` kopieren.
4. Entwicklerwerkzeuge → YAML → *Konfiguration prüfen*, dann neu starten.

Entstehende Entitäten:

| Entität | Zustand | Attribute |
|---|---|---|
| `sensor.elterninfo_ubersicht` | Anzahl offener Aufgaben | `children`, `todo`, `termine`, `posts`, `losung`, `active_diseases`, `care_active` |
| `sensor.elterninfo_benachrichtigungen` | ungelesene Benachrichtigungen | `data` |
| `sensor.elterninfo_vertretungsplan` | Anzahl Vertretungen | `vertretungen`, `news`, `week`, `absences` |
| `sensor.nele_elterninfo_status` | krank / in der Einrichtung / abgeholt / angemeldet / abgemeldet / offen / kein Eintrag | `schickzeit`, `krank_bis` |

Das Dashboard „Familie" enthält die Ansicht **ElternInfo** (`/dashboard-test/elterninfo-status`), die diese Sensoren anzeigt.
