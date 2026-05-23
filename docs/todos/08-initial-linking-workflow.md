# TODO-08: Initial-Linking-Workflow

> Konzept: §5.2 (Duplikat-Vermeidung), §8 (`ucs_link_candidates`), §15.1 (Offener Punkt)

## Ziel

Verhindern, dass der Sync für vor-UCS manuell angelegte Kinder
(`children.ucs_username = NULL`, `ucs_source='local'`) bei erstmaligem
Erscheinen in Kelvin Duplikate erzeugt. Stattdessen werden potenzielle
Treffer in `ucs_link_candidates` gemeldet, die ein Admin per UI oder
CLI verschmilzt.

## Scope / Anforderungen

- Detection-Logik im `UcsSyncService` (kommt aus Paket 04, hier nur
  UI-/Workflow-Teil).
- Eloquent-Model `App\Model\UcsLinkCandidate` mit Relationen.
- Admin-UI-Sektion **innerhalb** des UCS-Tabs (Paket 07) **oder** als
  eigene Liste unter dem Tab – Entscheidung: **innerhalb**, weil
  inhaltlich zusammengehörig.
- `ucs:link-child`-CLI ist bereits in Paket 05 spezifiziert.
- Bestätigung im UI ruft denselben Service auf wie das CLI.

## Abhängigkeiten

- 01 (`ucs_link_candidates`-Tabelle) **zwingend**.
- 04 (Detection im `UcsSyncService`) **zwingend**.
- 05 (`ucs:link-child` als Referenzimplementierung der Confirm-Logik) **zwingend**.
- 07 (UCS-Tab mit Container-Struktur, dort wird ein neuer Block eingehängt).

## Aufgaben

### Model & Service

- [ ] **`app/Model/UcsLinkCandidate.php`**
  - Felder `child_id`, `ucs_username`, `ucs_uuid`, `reason`,
    `payload` (cast `array`), `detected_at`, `confirmed_at`,
    `confirmed_by`.
  - Relationen `child(): BelongsTo`, `confirmedBy(): BelongsTo`.
- [ ] **`App\Services\Ucs\LinkCandidateService`** (kapselt die
  Confirm-Logik, gemeinsam genutzt von CLI und UI):
  - `confirm(UcsLinkCandidate $c, ?User $by): Child`
    - In `DB::transaction()`: `child.ucs_username` und
      `child.ucs_uuid` setzen, `child.ucs_source` bleibt `'local'`
      (manuelle Pflege respektiert!), `confirmed_at=now()`,
      `confirmed_by=$by?->id`.
    - Idempotent: bereits confirmed → no-op + Hinweis.
  - `reject(UcsLinkCandidate $c, ?User $by, string $note = ''): void`
    - Markiert mit `confirmed_at` und `payload->note = …`
      `payload->status = 'rejected'`, damit der Sync den Kandidaten
      nicht beim nächsten Lauf erneut detektiert.
- [ ] **Sync-Verhalten:**
  - Beim nächsten Sync-Lauf wird ein bereits confirmter Eintrag
    übersprungen (Child hat dann `ucs_username` → normaler Match-Pfad).
  - Ein rejecteter Kandidat (`payload.status === 'rejected'`) wird vom
    Sync ebenfalls übersprungen, aber im Log mit Hinweis vermerkt.

### Admin-UI

- [ ] Neuer Blade-Partial
  `resources/views/settings/tabs/_ucs-link-candidates.blade.php`,
  eingebunden im UCS-Tab unterhalb des Formulars.
- [ ] Listet alle offenen Kandidaten (`confirmed_at IS NULL` und
  `payload.status != 'rejected'`) als Tabelle mit Spalten:
  - Lokales Kind (`first_name last_name`, Klasse, ID).
  - UCS-Vorschlag (`ucs_username`, `ucs_uuid`, Vorschau aus `payload`).
  - Match-Grund (`reason`).
  - Aktionen: „Verknüpfen“, „Verwerfen“, beide als POST-Form.
- [ ] Routen:
  ```php
  Route::middleware(['auth','permission:edit settings'])->group(function () {
      Route::post('settings/ucs/link-candidates/{candidate}/confirm',
          [UcsLinkCandidateController::class, 'confirm'])
          ->name('settings.ucs.link.confirm');
      Route::post('settings/ucs/link-candidates/{candidate}/reject',
          [UcsLinkCandidateController::class, 'reject'])
          ->name('settings.ucs.link.reject');
  });
  ```
- [ ] `App\Http\Controllers\UcsLinkCandidateController` (zwei Methoden,
  rufen den Service auf, Flash-Message + Redirect zurück zum Tab).

### CLI

- [ ] `ucs:link-child` aus Paket 05 nutzt **dieselbe** Service-Methode
  `LinkCandidateService::confirm()` (kein duplizierter Code).

### Test-Daten

- [ ] Optionaler Seeder `UcsLinkCandidateSeeder` für lokale Demos
  (nur in `local`-Env).

## Gelingenskriterien

1. Im Sync-Run mit einem lokalen Kind „Max Müller, Klasse 5a“ (`ucs_username=NULL`)
   und einem UCS-Kind „max.mueller“ in derselben Klasse:
   → **kein** zweiter `children`-Eintrag,
   → genau **ein** `ucs_link_candidates`-Eintrag mit `reason='name_match'`.
2. Im UI wird der Kandidat angezeigt; Klick auf „Verknüpfen“ setzt
   `children.ucs_username='max.mueller'` und entfernt den Kandidaten
   aus der Liste.
3. Nach Verknüpfung erkennt der nächste Sync den Match und legt Auto-Pivots an.
4. Klick auf „Verwerfen“ entfernt den Kandidaten ebenfalls aus der UI
   und unterbindet das Re-Detect im nächsten Sync (`payload.status='rejected'`).
5. CLI `ucs:link-child 42 max.mueller` und UI-Klick auf „Verknüpfen“ erzeugen
   identische DB-Zustände (Service-Test).
6. Permissions: User ohne `edit settings` sieht die Liste **nicht**
   und bekommt bei direktem POST 403.
7. Doppelt-Confirm (Race-Condition) ist no-op, keine Exception.

## Out of Scope

- Fuzzy-Matching jenseits von „exakter Name + Klasse“ (Geburtsdatum
  o.ä. – das Konzept persistiert solche Daten bewusst nicht, §7.5).
- Bulk-Aktionen („alle bestätigen“). Falls gewünscht → eigenes Ticket.

## Aufwand

M – ca. 1–2 Personentage.

