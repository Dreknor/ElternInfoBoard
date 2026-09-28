# TODO-12: Kelvin-API-Endpunkt-Dokumentation & Proxy-Freigabeliste

> Konzept: §3 (Kelvin-Endpunkte), §7.5 (TLS-Pflicht), §7.6 (Robustheit)

## Hintergrund & Ziel

Die Kelvin REST API ist **hinter einem ausgehenden Proxy abgesichert**.
Jeder HTTPS-Request von der Laravel-Applikation nach außen wird dort
nach einer Whitelist geprüft – **nicht gelistete Aufrufe werden hart
blockiert** und erzeugen für die Applikation einen nicht
differenzierten Netzwerkfehler.

Ziel dieses Pakets ist eine **vollständige, maschinenlesbare
Dokumentation aller API-Aufrufe**, die der `KelvinClient` (Paket 03)
tätigt – inklusive Protokoll, Methode, Pfad-Pattern, Query-Parameter,
Request-Header und erwarteter Response-Codes. Diese Dokumentation wird
dem Netzwerk-/Proxy-Team übergeben, das daraus die Freigabe-Regeln
konfiguriert.

> **Wichtiger Grundsatz:** Kein API-Aufruf darf in Paket 03 implementiert
> werden, der noch **nicht** in dieser Dokumentation erfasst ist.
> Neue Endpunkte → zuerst hier dokumentieren, dann im `KelvinClient`
> implementieren, dann Proxy-Freigabe beantragen.

## Scope / Anforderungen

- Dokumentationsdatei `docs/kelvin-api-endpunkte.md` mit einer
  Tabelle und Detailblöcken für **jeden** einzelnen HTTP-Aufruf.
- Zu jedem Aufruf müssen dokumentiert sein:
  - **Protokoll** (immer HTTPS)
  - **Methode** (GET / POST / …)
  - **Basis-URL-Pattern** (z. B. `{UCS_KELVIN_BASE_URL}/auth/`)
  - **Pfad-Pattern** inkl. aller Pfad-Parameter
  - **Query-Parameter** (Name, Typ, Pflicht/Optional, Beispielwert)
  - **Request-Header** (Pflicht-Header, insb. `Authorization`, `Accept`, `Content-Type`)
  - **Request-Body-Schema** (falls vorhanden, z. B. Form-Body bei `/auth/`)
  - **Erwartete HTTP-Response-Codes** (Erfolg + Fehler)
  - **Response-Body-Schema** (Felder, Typen, Beispiel-JSON)
  - **Aufrufender Code** (Klasse + Methode in Laravel)
  - **Wann wird dieser Call ausgelöst** (Scheduler-Sync / JIT-Login / CLI / Test-Button)
  - **Besonderheiten** (Pagination, Retry, Rate-Limit-Verhalten)
- Eine kompakte **Proxy-Whitelist-Checkliste** am Ende der Datei, die
  als Übergabe-Dokument an das Netzwerk-Team dient.
- Jede `KelvinClient`-Methode muss einen Docblock-Verweis auf das
  entsprechende Dokumentations-Kapitel enthalten.

## Abhängigkeiten

- 02 (`UcsSetting`, Basis-URL als ENV) – Basis-URL-Pattern bekannt.
- 03 (`KelvinClient`) – parallel oder **vor** Paket 03 starten.

> **Empfehlung:** Dieses Paket am besten **vor oder parallel zu Paket 03**
> beginnen, sodass beim Implementieren des Clients die Proxy-Dokumentation
> synchron mitgepflegt werden kann (kein Nachpflege-Aufwand).

## Aufgaben

### Dokumentationsdatei anlegen

- [ ] `docs/kelvin-api-endpunkte.md` erstellen (Struktur s. u.).

### Zu dokumentierende Endpunkte

Alle folgenden Calls müssen vollständig beschrieben sein:

#### 1. Token-Authentifizierung (`POST /auth/`)

- [ ] Methode: `POST`
- [ ] Pfad: `{UCS_KELVIN_BASE_URL}/auth/`
- [ ] Content-Type: `application/x-www-form-urlencoded`
- [ ] Request-Body: `username=<service-account>&password=<secret>`
- [ ] Response 200: `{"access_token":"…","token_type":"bearer","expires_in":3600}`
- [ ] Response 401: `{"detail":"…"}` (falsches Passwort)
- [ ] Aufrufender Code: `KelvinClient::token()`
- [ ] Trigger: Jeder erste API-Call nach Token-Ablauf (Cache-Miss)

#### 2. Schulen auflisten (`GET /schools/`)

- [ ] Methode: `GET`
- [ ] Pfad: `{UCS_KELVIN_BASE_URL}/schools/`
- [ ] Query: `limit` (optional, int, Default aus `UcsSetting::kelvin_page_size`)
- [ ] Header: `Authorization: Bearer <token>`
- [ ] Response 200: Array von School-Objekten
- [ ] Response 401/403: Token abgelaufen / keine Berechtigung
- [ ] Aufrufender Code: `KelvinClient::ping()`, `KelvinClient::listSchools()`
- [ ] Trigger: Test-Button im UI-Tab, `ucs:ping`-Command

#### 3. Eltern auflisten (`GET /users/` – legal_guardian)

- [ ] Methode: `GET`
- [ ] Pfad: `{UCS_KELVIN_BASE_URL}/users/`
- [ ] Query:
  - `role=legal_guardian` (Pflicht)
  - `school={school}` (Pflicht, Wert aus `UcsSetting::school`)
  - `limit` (Pflicht, int, `UcsSetting::kelvin_page_size`, empfohlen: 200)
  - `offset` (Pflicht für Pagination, int, startet bei 0)
- [ ] Header: `Authorization: Bearer <token>`, `Accept: application/json`
- [ ] Response 200: Array von User-Objekten (Felder: `username`, `record_uid`,
  `firstname`, `lastname`, `email`, `school`, `roles`, `legal_wards[]`)
- [ ] Response 200 leer (`[]`): Ende der Paginierung
- [ ] Response 401/403/404
- [ ] Aufrufender Code: `KelvinClient::listParents()` (Generator)
- [ ] Trigger: `SyncUcsSchoolJob` (Scheduler + manueller Sync-Button)

#### 4. Schüler auflisten (`GET /users/` – student)

- [ ] Methode: `GET`
- [ ] Pfad: `{UCS_KELVIN_BASE_URL}/users/`
- [ ] Query:
  - `role=student` (Pflicht)
  - `school={school}` (Pflicht)
  - `limit`, `offset` (wie Eltern)
- [ ] Header: identisch zu §4.3
- [ ] Response 200: Array von Student-Objekten (Felder: `username`,
  `record_uid`, `firstname`, `lastname`, `school`,
  `school_classes: {"<school>": ["<classname>", …]}`, `url`)
- [ ] Response-Sonderfall `school_classes` leer: Kind ohne Klasse –
  Sync schreibt kein `class_id`, loggt Warnung
- [ ] Aufrufender Code: `KelvinClient::listStudents()` (Generator)
- [ ] Trigger: identisch zu §4.3

#### 5. Einzelner Benutzer (`GET /users/{username}`)

- [ ] Methode: `GET`
- [ ] Pfad: `{UCS_KELVIN_BASE_URL}/users/{username}`
  (Pfad-Parameter: URL-kodierter Username)
- [ ] Header: `Authorization: Bearer <token>`, `Accept: application/json`
- [ ] Response 200: einzelnes User-Objekt (Elternteil oder Schüler,
  je nach Rolle des Accounts)
- [ ] Response 404: `{"detail":"Not Found"}` – User in UCS nicht
  vorhanden (Negativ-Cache starten!)
- [ ] Response 401/403
- [ ] Aufrufender Code: `KelvinClient::findUser()`,
  `UcsSyncService::syncSingleParent()`
- [ ] Trigger: JIT-Login beim OIDC-Callback, `ucs:link-child`-CLI

#### 6. Klassen auflisten (`GET /classes/`) – optional

- [ ] Methode: `GET`
- [ ] Pfad: `{UCS_KELVIN_BASE_URL}/classes/`
- [ ] Query: `school={school}` (Pflicht), `limit`, `offset`
- [ ] Response 200: Array von Class-Objekten (Felder: `name`, `school`,
  `users[]`, `url`, `dn`)
- [ ] Aufrufender Code: `KelvinClient::listClasses()` (falls implementiert)
- [ ] Trigger: Nur bei explizitem Aufruf (derzeit nicht im
  Haupt-Sync-Pfad – in Dokumentation als „optional / Phase 2" kennzeichnen)

### Proxy-Whitelist-Checkliste (Übergabe-Dokument)

- [ ] Am Ende von `docs/kelvin-api-endpunkte.md` eine kompakte
  Tabelle erstellen, die das Netzwerk-Team direkt in die Proxy-Regel
  übersetzen kann:

```
| Ziel-Host        | Protokoll | Methode | Pfad-Pattern                        | Query-Parameter (Pflicht)                      |
|------------------|-----------|---------|-------------------------------------|------------------------------------------------|
| <UCS-Host>       | HTTPS     | POST    | /ucsschool/kelvin/v1/auth/          | –                                              |
| <UCS-Host>       | HTTPS     | GET     | /ucsschool/kelvin/v1/schools/       | –                                              |
| <UCS-Host>       | HTTPS     | GET     | /ucsschool/kelvin/v1/users/         | role=legal_guardian|student, school=<school>   |
| <UCS-Host>       | HTTPS     | GET     | /ucsschool/kelvin/v1/users/*        | –                                              |
| <UCS-Host>       | HTTPS     | GET     | /ucsschool/kelvin/v1/classes/       | school=<school>                                |
```

> **Hinweis:** Der `<UCS-Host>` entspricht dem konfigurierten Wert
> aus `UCS_KELVIN_BASE_URL` und wird für die Proxy-Konfiguration
> als FQDN (ohne Pfad) eingetragen.

### Docblock-Verweise im Code

- [ ] Jede `public`-Methode in `KelvinClient` erhält einen Docblock
  mit `@see docs/kelvin-api-endpunkte.md#<Abschnitt>`, z. B.:
  ```php
  /**
   * Liefert alle legalen Erziehungsberechtigten der Schule (paginiert).
   *
   * @see docs/kelvin-api-endpunkte.md#3-eltern-auflisten-get-users--legal_guardian
   */
  public function listParents(string $school): Generator
  ```
- [ ] Wenn ein neuer Endpunkt ergänzt wird, muss dieser Schritt
  **zwingend** vor dem Merge erledigt sein (PR-Template-Checkliste).

### PR-Template-Erweiterung

- [ ] `.github/PULL_REQUEST_TEMPLATE.md` (bzw. GitLab-Äquivalent)
  ergänzen mit:
  ```markdown
  ## Kelvin-API-Aufrufe
  - [ ] Alle neuen/geänderten Kelvin-API-Aufrufe sind in
        `docs/kelvin-api-endpunkte.md` dokumentiert.
  - [ ] Die Proxy-Whitelist-Checkliste am Ende der Datei ist aktuell.
  - [ ] Das Netzwerk-Team wurde über neue Einträge informiert.
  ```

## Struktur von `docs/kelvin-api-endpunkte.md`

```markdown
# Kelvin REST API – Aufrufe und Proxy-Freigaben

## Überblick
…

## Basis-URL
`{UCS_KELVIN_BASE_URL}` – Wert aus `.env`/`UcsSetting::kelvin_base_url`.
HTTPS ist Pflicht (TLS-Verifikation `verify=true`).

## Authentifizierung
Jeder Aufruf trägt einen Bearer-Token im `Authorization`-Header.
Token-Lebensdauer: `UcsSetting::kelvin_token_ttl` (Standard: 3300 s).

## Endpunkte

### 1. Token-Authentifizierung
…

### 2. Schulen auflisten
…

[usw.]

## Proxy-Whitelist-Checkliste
[kompakte Tabelle für Netzwerk-Team]

## Änderungshistorie
| Datum | Autor | Änderung |
|---|---|---|
| 2026-05-22 | Engineering | Erstfassung |
```

## Gelingenskriterien

1. `docs/kelvin-api-endpunkte.md` existiert und beschreibt **jeden**
   in `KelvinClient` implementierten Endpunkt vollständig (kein Aufruf
   ohne Eintrag).
2. Die Proxy-Whitelist-Checkliste ist eine eigenständige,
   kopierfertige Tabelle, die das Netzwerk-Team ohne Rückfragen
   umsetzen kann (Ziel-Host, Methode, Pfad-Pattern).
3. Eine Pull-Request gegen `KelvinClient` mit einem neuen API-Aufruf
   **ohne** zugehörigen Dokumentationseintrag wird durch das
   PR-Template-Feld vom Reviewer geblockt.
4. Jede `public`-Methode in `KelvinClient` hat einen `@see`-Docblock.
5. Das Netzwerk-Team hat die Checkliste erhalten und bestätigt, dass
   die Freigaben gesetzt wurden, **bevor** der erste echte Sync
   (Paket 10, Roll-out Schritt T-3 d) gestartet wird.
6. Wenn der Client einen bisher nicht freigegebenen Endpunkt aufruft
   (Proxy-Block), ist der Fehler im Log als
   `KelvinUnavailableException` mit Hinweis „Proxy-Block ?" sichtbar
   (defensiver Kommentar in `KelvinClient::client()` – kein eigener
   Request, aber ein Hinweis in der Catch-Clause auf das Runbook).

## Out of Scope

- OIDC-Endpunkte (Keycloak/Konnect) – die werden vom bestehenden
  Socialite-Flow verwaltet und sind kein Teil des `KelvinClient`.
- SCIM- oder Webhook-Endpunkte (Phase 2/3, §13).
- Dokumentation der internen Laravel-Routen.

## Aufwand

S – ca. 0,5–1 Personentag (parallel zu Paket 03 zu erledigen).

