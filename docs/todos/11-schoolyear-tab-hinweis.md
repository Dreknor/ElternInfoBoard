# TODO-11: Schuljahreswechsel-Tab – Inline-Hinweis bei aktivem UCS-Sync

> Konzept: §15.2 (Verhältnis Schuljahreswechsel ↔ UCS-Sync)

## Ziel

Im bestehenden Settings-Tab „Schuljahreswechsel“ (`schoolyear`) klar
signalisieren, dass bei aktiver UCS-Anbindung der manuelle
Klassen-Promotion-Workflow nur noch lokal angelegte Kinder
(`ucs_source='local'`) betrifft. Alle UCS-Kinder erhalten ihre neue
Klasse automatisch über den Sync.

## Scope / Anforderungen

- Eine Inline-Warning/Info-Box am oberen Rand des bestehenden Tabs.
- Sichtbar nur, wenn `UcsSetting::enabled === true`.
- Keine funktionale Änderung am Promotion-Workflow.
- Konsistenter Bootstrap-Stil (`alert alert-info`/`alert-warning`).

## Abhängigkeiten

- 02 (`UcsSetting`).
- 07 (UCS-Tab existiert) – nicht zwingend funktional, aber sinnvoll für die
  textuelle Cross-Referenz im Hinweis.

## Aufgaben

- [ ] `resources/views/settings/tabs/schoolyear-tab.blade.php` öffnen
  (Datei-Pfad ggf. anpassen, falls die Datei anders heißt).
- [ ] Am Anfang des Tab-Containers einfügen:
  ```blade
  @php $ucs = $ucsSettings ?? app(\App\Settings\UcsSetting::class); @endphp
  @if($ucs->enabled)
      <div class="alert alert-info small mb-3">
          <i class="fas fa-info-circle"></i>
          <strong>UCS-Sync aktiv:</strong>
          Klassenwechsel von Kindern aus UCS@school
          (<code>ucs_source = 'kelvin'</code>) werden beim nächsten
          Sync automatisch übernommen. Die manuelle Promotion unten
          betrifft <strong>nur lokal angelegte Kinder</strong>
          (<code>ucs_source = 'local'</code>) – die bleiben weiterhin
          in Ihrer Hand.
      </div>
  @endif
  ```
- [ ] Sicherstellen, dass `$ucsSettings` in
  `SettingsController::index()` an die View übergeben wird – das wird
  ohnehin durch Paket 07 erledigt; falls Paket 07 noch nicht gemerged
  ist, nutzt der obige Fallback `app(UcsSetting::class)`.
- [ ] Optional: Wenn der Promotion-Workflow eine Vorschau zeigt, die
  Vorschau auf `Child::local()` (Scope aus Paket 01) filtern, damit
  UCS-Kinder gar nicht erst in der Liste auftauchen.

## Gelingenskriterien

1. Mit `UcsSetting::enabled = false` ist der Tab visuell unverändert.
2. Mit `UcsSetting::enabled = true` erscheint die Info-Box oben.
3. Manuell ein UCS-Kind (`ucs_source='kelvin'`) im Promotion-Workflow
   anzufassen ist (sofern die Option 2-Filterung umgesetzt wird)
   nicht möglich.
4. Bestehender Schuljahreswechsel-Workflow für lokale Kinder
   funktioniert weiterhin unverändert (Regression).

## Out of Scope

- Komplette Überarbeitung des Schuljahreswechsel-Tabs.
- Automatische Promotion lokaler Kinder analog zum UCS-Sync.

## Aufwand

S – ca. 0,5 Personentag (inkl. Manual-Test).

