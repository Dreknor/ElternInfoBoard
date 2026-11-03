# Schülerkonten (optional)

Stand: 03.11.2026 · Entwurf, Grundsatzfragen entschieden (§12) · standardmäßig aus

Schulen können Schülerinnen und Schülern ein eigenes, stark eingeschränktes Konto geben.
Wo ein Schul-Login (SSO) besteht, melden sich Schüler darüber an. Ohne SSO gibt es einen
Benutzernamen statt einer E-Mail-Adresse. Die Übernahme aus UCS@school ist optional.
Ist die Funktion aus, ändert sich nichts.

---

## 1. Ziele und Grundsätze

* **Optional:** eigenes Modul „Schülerkonten“, standardmäßig inaktiv.
* **Grundsätzlich verboten:** Ein Schülerkonto darf nur, was ausdrücklich freigegeben ist.
  Neue Funktionen sind für Schüler automatisch gesperrt.
* **Schüler sind keine Eltern:** Schülerkonten stehen nie in `group_user`, nie in einer
  Familie und haben nie Elternrechte. Dadurch tauchen sie in keinem Verteiler, keiner
  Statistik und keinem Pool auf (Reinigung, Pflichtstunden, Rückmeldungen …).
* **Keine E-Mail nötig:** Anmeldung über SSO oder über einen Benutzernamen.
* **Datensparsam:** Es werden nur Name, Klasse und Login gespeichert, keine Kontaktdaten.
  Gesundheitsdaten und Krankmeldungen bleiben für Schüler unsichtbar.

## 2. Ausgangslage im Code

Diese Stellen bestimmen das Konzept:

| Befund | Folge |
|---|---|
| Sichtbarkeit und Empfänger laufen über `group_user` (`Post::users()`, `Termin::users()`, `Liste::users()`, `PostPolicy`, `PostQuery::visibleTo`) | Wäre ein Schüler Mitglied der Klassengruppe, sähe er alle Elternbeiträge und stünde in allen Verteilern |
| Reinigungsplan-Pool `User::whereHas('groups')` ohne Rollenfilter (`ReinigungController:136/407`) | Schüler würden zum Putzen eingeteilt |
| Rückmelde- und Lesestatistik „wer hat noch nicht“ = `$post->users` (`RueckmeldungStatusService`, `ReadReceiptsController`, `ProcessRemindersJob`) | Falsche Zahlen und Erinnerungen an Schüler |
| Mail, Newsletter und Push gehen an Gruppenmitglieder (`NachrichtenController:989/1057`, `TerminController:260`, `ListenController:158`, `VertretungObserver:34`) | Unerwünschte Mails und Pushes an Schüler |
| Messenger: Gruppenchats und Kontaktsuche über gemeinsame Gruppe (`GroupsController:298ff`, `MessengerController:143ff`) | Schüler könnten Eltern anschreiben |
| `children` hat keine Verbindung zu einem Konto | Neue Verknüpfung Kind ↔ Schülerkonto nötig |
| `users.email` ist Pflichtfeld (eindeutig je `email, ucs_source`) | Für Schüler ohne E-Mail muss das Feld leer sein dürfen |
| Allgemeiner Keycloak-Login (`LoginController::handleKeycloakCallback`) sucht das Konto über die E-Mail und **legt unbekannte Konten automatisch als normale Konten an** | **Schon heute ein Risiko:** Ein Schüler mit Schul-SSO bekommt ein vollwertiges Konto. Wird unabhängig von diesem Konzept zuerst behoben (S-02) |

Daraus folgt: Schülerkonten bekommen **keine** Gruppenmitgliedschaft. Ihre Klassen werden
über das verknüpfte Kind bestimmt (`Child::class`, `Child::group`, `child_group`).

## 3. Datenmodell (nur additiv)

* `users.account_type`: `standard` (Standard) | `student`. Das ist ein festes Merkmal,
  keine Rolle, damit niemand es versehentlich über die Rollenverwaltung vergibt.
* `users.username` (nullable, eindeutig): Login ohne E-Mail. Bei UCS ist es der
  UCS-Benutzername.
* `users.email` nullable. Für `account_type = student` ist die E-Mail immer leer, auch wenn
  das SSO eine liefert. So gehen keine Mails an Schüler, und die Adresse kann nicht mit einem
  Elternkonto kollidieren.
* `children.user_id` (nullable, eindeutig, FK `users`): ein Konto je Kind.
* `posts.zielgruppe` (und optional `termine.zielgruppe`): `eltern` (Standard) |
  `eltern_schueler` | `schueler`. Bestehende Beiträge bleiben `eltern`.
* `student_account_events`: Protokoll über Anlage, Sperre, Code-Ausgabe und Löschung
  (wer, wann, was; keine Inhalte).
* Neue Einstellung `SchuelerSetting` (siehe §9), neues Modul „Schülerkonten“.

`User::isStudent()` ist die einzige Abfrage, die der restliche Code benutzt.

## 4. Was ein Schülerkonto darf

| Bereich | Schüler | Hinweis |
|---|---|---|
| Nachrichten | nur `zielgruppe` ≠ `eltern` **und** an eine Klasse/Gruppe des Kindes | keine Lesebestätigung, Rückmeldung, Umfrage, Kommentare, Reaktionen |
| Termine | nur Termine mit `zielgruppe` ≠ `eltern` der eigenen Gruppen | kein iCal-Abo mit Elterndaten |
| Vertretungsplan | eigene Klasse | ohne Abwesenheiten von Lehrkräften, falls die Schule das wünscht |
| Stundenplan | eigene Klasse | |
| AGs | ansehen | Anmeldung bleibt bei den Eltern |
| Eigenes Profil | Name ansehen, Passwort ändern (nur Login ohne SSO) | keine Kontaktdaten |
| Push in der App | nur bei Schülerbeiträgen | später, siehe §8 |

**Gesperrt** sind alle anderen Bereiche, zum Beispiel: Krankmeldung, Kinder, Rückmeldungen,
Listen und Buchungen, Pflichtstunden, Reinigung, Elternrat, Messenger, Kontaktformular,
Datei-Uploads, Personensuche, Datenschutz-Auskunft (geht über die Eltern) und jede
Verwaltungsfunktion.

**Messenger mit Lehrkräften** entscheidet die Schule (Einstellung `messenger_lehrkraefte`,
Standard aus). Ist er an, dürfen Schüler nur Direktchats mit Lehrkräften ihrer Gruppen führen.
Die Kontaktsuche zeigt dann nur diese Lehrkräfte. Es gibt keine Gruppenchats und keine Chats
mit Eltern oder anderen Schülern. Lehrkräfte können Schüler-Chats stumm schalten oder sperren.
Kommentare bleiben gesperrt.

## 5. Durchsetzung (mehrere Ebenen)

1. **Freigabeliste je Route:** Die Middleware `RestrictStudentAccounts` liegt in den
   Gruppen `web` und `api`. Für Schülerkonten lässt sie nur Routen mit dem Namenspräfix
   `student.` sowie Login und Logout durch. Alles andere ergibt 404, wie bei inaktiven
   Modulen. Neue Routen sind so automatisch gesperrt.
2. **Rechte:** Ein `Gate::before` gibt Schülern bei jeder Berechtigung `false`, außer bei den
   wenigen `student …`-Rechten. Rollen werden an Schülerkonten nicht vergeben
   (`UserService::syncRoles` lehnt das ab).
3. **Daten:**
   * Schülerkonten werden nie in `group_user` geschrieben. `GroupsController`,
     `GroupMembershipService`, Importe und der Schuljahreswechsel (`SchoolYearService`)
     prüfen `isStudent()`. Zusätzlich wird es in der Datenbank verhindert (Trigger bzw.
     Prüfung im Observer von `group_user`).
   * `FamilyBuilder` und `FamilyResolver` überspringen Schülerkonten.
   * `messenger_discoverable = false`, `benachrichtigung` aus, `sendCopy` aus.
   * Eigene Abfrage `StudentPostQuery` statt `PostPolicy` und `PostQuery`. Gruppen kommen
     aus `Child::derivedGroupIds()`.
4. **Schutz bei der Verwaltung:** Schülerkonten erscheinen in der Benutzerliste nur auf
   einem eigenen Reiter „Schülerkonten“. In „alle Nutzer“-Auswahlen
   (`User::query()` in Verwaltungsmasken) fehlen sie über den Scope `withoutStudents()`.
5. **Test als Netz:** Ein Feature-Test geht alle registrierten Routen durch und erwartet
   für ein Schülerkonto 404/403, außer bei der Freigabeliste. Jede neue Route ist damit
   automatisch abgedeckt.

## 6. Anmeldung

### 6.1 Mit SSO (UCS-OIDC oder Keycloak)

* Schüler melden sich nur über das SSO an. Es gibt kein lokales Passwort, und
  „Passwort vergessen“ entfällt.
* Zuordnung wie bei Eltern (`UcsLoginController`, §6.2 UCS-Konzept): zuerst `ucs_oidc_sub`,
  dann `username`. Die Zuordnung zum Kind läuft über `children.ucs_username`.
  **Nie über die E-Mail-Adresse.**
* Ob jemand Schüler ist, ergibt sich **aus der Rolle** (umgesetzt in
  `App\Services\Auth\SsoStudentDetector`):
  * **UCS@school:** Claim `ucsschoolRole` mit dem Wert `student:school:<Schule>`. Die Rolle
    `student` gilt immer als Schüler, ohne Einstellung. Der Claim kommt nur ins Token, wenn
    er im Keycloak eingerichtet ist (§6.3).
  * **Allgemeiner Keycloak:** Realm- oder Client-Rolle im Token (`realm_access.roles`,
    `resource_access.<client>.roles`, `roles`). Die Rollennamen stehen in der Einstellung
    „Rollen für Schülerinnen und Schüler“ (`KeycloakSetting::$student_roles`).
  * E-Mail-Domain oder Gruppennamen werden nicht ausgewertet.
* Ist kein Konto vorhanden:
  * Bei aktivierter **UCS-Provisionierung** (§7) wurde es bereits angelegt.
  * Sonst und bei `jit_anlage = an`: Das Konto wird beim ersten Login angelegt, aber nur,
    wenn ein passendes Kind existiert und die Klasse freigegeben ist (§9).
  * Sonst erscheint der Hinweis „Für Sie ist kein Zugang freigeschaltet.“
* **S-02 (Pflicht, auch ohne Schülerkonten):** Der allgemeine Keycloak-Login legt
  Konten mit der Schülerrolle nie als normale Konten an. Optional lässt sich die
  automatische Anlage ganz abschalten.

### 6.2 Ohne SSO: Benutzername

* Das Anmeldefeld heißt „E-Mail oder Benutzername“. Schülerkonten melden sich nur mit
  Benutzername an, z. B. `vorname.n3` (eindeutig, ohne Klassenangabe).
* **Erstzugang über einen Code:** Die Verwaltung (oder, falls erlaubt, ein Elternteil mit
  `can_manage`) erzeugt einen Einmal-Code bzw. QR-Code (gültig 7 Tage). Damit setzt der
  Schüler sein Passwort. Das nutzt den vorhandenen Login-Code-Mechanismus aus
  `API/V1/AuthController` (`app_login_code`).
* **Passwort vergessen:** Ohne E-Mail gibt es kein Zurücksetzen per Mail. Verwaltung oder
  Eltern erzeugen einen neuen Code.
* Strengere Sperre bei Fehlversuchen (`throttle:login-student`). Sitzungen und
  App-Tokens laufen kürzer (Standard 30 Tage).

### 6.3 UCS@school-Keycloak einrichten

UCS@school speichert die Rolle im LDAP-Attribut `ucsschoolRole` (mehrwertig, Format
`<rolle>:school:<schule>`, z. B. `student:school:ESZ`, `teacher:school:ESZ`,
`legal_guardian:school:ESZ`). Der UCS-Keycloak übernimmt Nutzer aus dem LDAP, macht daraus
aber **keine** Keycloak-Rollen. In `realm_access.roles` steht deshalb nur z. B.
`default-roles-ucs`. Ohne Mapper enthält das Token keine Schülerrolle, und die Erkennung
greift nicht.

Einrichtung in der Keycloak-Admin-Konsole (Realm `ucs`):

1. *User federation → LDAP-Provider → Mappers → Add mapper*, Typ
   `user-attribute-ldap-mapper`: Name und User-Model-Attribut `ucsschoolRole`,
   LDAP-Attribut `ucsschoolRole`, *Read only* an, *Always read value from LDAP* an.
2. *Clients → (Client des ElternInfoBoards) → Client scopes → …-dedicated → Add mapper →
   By configuration → User Attribute*: User-Attribut `ucsschoolRole`, Token-Claim-Name
   `ucsschoolRole`, *Multivalued* an, *Add to ID token*, *Add to access token* und
   *Add to userinfo* an.
3. Prüfen: *Client scopes → Evaluate*, einen Test-Schüler wählen. Unter „Generated ID
   token“ muss `"ucsschoolRole": ["student:school:…"]` stehen.

Hinweise:

* Die Schritte stammen aus dem Keycloak-Standard und dem UCS-Rollenformat im Projekt
  (`KelvinClient`). Mit der Univention-Doku zur Keycloak-App sind sie noch nicht
  abgeglichen. Bei Univention gibt es dafür ggf. auch das Kommando `univention-keycloak`.
* Statt `ucsschoolRole` darf der Claim auch `ucsschool_role` heißen. Beide werden gelesen.
* Ohne Mapper bleibt im UCS-Login trotzdem ein Schutz: Ein unbekannter Nutzer wird nur
  angelegt, wenn Kelvin ihn als `legal_guardian` führt (`UcsSyncService::syncSingleParent`).
  Schüler bekommen dort also kein Elternkonto. Schutzlos ist ohne Mapper nur der
  **allgemeine** Keycloak-Login, der unbekannte Nutzer über die E-Mail anlegt. Dort hilft
  zusätzlich „Unbekannte Nutzer automatisch anlegen“ = aus.

## 7. UCS@school-Provisionierung (optional)

Einstellung `ucs_provisionierung` (Standard: aus), nur wirksam bei `UcsSetting::enabled`.

* **An:** `sync:ucs-parents` legt für Kelvin-Schüler der freigegebenen Klassen Schülerkonten
  an (`account_type = student`, `username = ucs_username`, `ucs_source = kelvin`, keine
  E-Mail, kein Passwort) und verknüpft sie mit dem Kind (`children.user_id`).
* **Aus:** Es werden keine Konten vorab angelegt. Bei SSO entstehen sie erst beim ersten
  Login (JIT, §6.1) oder durch manuelle Anlage bzw. CSV.
* Aufräumen nach den bestehenden Regeln: Verschwindet ein Schüler aus UCS oder hat das Kind
  `status = left`, wird das Konto sofort gesperrt (`is_active = false`) und nach
  `loeschen_nach_tagen` (Standard 30) gelöscht. Geändert oder gelöscht werden nur Konten mit
  `ucs_source = kelvin`.
* Es kommen keine Daten außer Vorname, Nachname, Benutzername und Klassen in die
  Datenbank.

## 8. App (elterninfo_app)

Entschieden: **Schüler bekommen eine eigene Ansicht in der App.** Es wird bewusst keine
zweite App. Die bestehende App zeigt je nach Kontoart eine andere Oberfläche. Die
Projektregel der App („nur für Eltern“) wird entsprechend angepasst.

* **Backend:** Endpunkte unter `v1/student/*` (Start, Nachrichten, Termine, Vertretungs-
  und Stundenplan, AGs, Profil, ggf. Messenger). Schüler-Tokens tragen die Sanctum-Fähigkeit
  `student` und erreichen nur diese Endpunkte. Alle übrigen `v1`-Endpunkte liefern 403
  bzw. 404 (gleiche Freigabeliste wie §5).
* `v1/bootstrap` liefert `account_type`. Daran entscheidet die App, welche Tabs sie zeigt.
* **Login in der App:** über SSO (wie Eltern), Benutzername und Passwort oder den
  Einmal-Code/QR (§6.2).
* **Push:** nur für Schülerbeiträge und Termine, ohne Inhalte im Push-Text.
* **Offline-Cache:** wie bei Eltern verschlüsselt. Beim Wechsel der Kontoart wird er
  gelöscht.
* **Reihenfolge:** Die Weboberfläche kommt zuerst. Die App folgt, sobald S-03 bis S-07
  stehen. Bis dahin lehnen `POST v1/token` und der SSO-Austausch Schülerkonten mit 403 ab.

## 9. Einstellungen (`SchuelerSetting`, Reiter „Schülerkonten“)

| Einstellung | Standard | Bedeutung |
|---|---|---|
| `enabled` | aus | Modul an/aus |
| `login_sso` | an | Login über SSO erlauben |
| `login_benutzername` | aus | Login mit Benutzername + Passwort (ohne SSO) |
| `jit_anlage` | aus | Konto beim ersten SSO-Login anlegen |
| `ucs_provisionierung` | aus | Konten per UCS-Sync anlegen |
| `freigegebene_gruppen` | leer | Klassen/Bereiche, für die Konten möglich sind (z. B. ab Klasse 5). Leer heißt: keine Konten |
| `keycloak_schuelerrolle` | leer | Rolle im Keycloak-Token, an der Schüler erkannt werden |
| `eltern_zustimmung` | aus | Option der Schule: Konto erst aktiv, wenn ein Elternteil zugestimmt hat |
| `messenger_lehrkraefte` | aus | Entscheidung der Schule: Schüler dürfen Lehrkräften schreiben (§4) |
| `eltern_codes` | aus | Eltern dürfen Einmal-Codes erzeugen |
| `loeschen_nach_tagen` | 30 | Löschfrist nach Austritt |
| `vertretung_abwesenheiten` | aus | Abwesenheiten von Lehrkräften auch Schülern zeigen |

**Eltern-Sicht:** Im Bereich „Kinder“ steht je Kind der Status des Schülerkontos (aktiv,
gesperrt, nicht vorhanden). Bei `eltern_zustimmung` können Eltern zustimmen oder das Konto
sperren. Bei `eltern_codes` können sie einen neuen Code erzeugen.

**Beiträge:** Im Editor steht unter den Gruppen das Feld „Sichtbar für“ mit den Werten
Eltern / Eltern und Schüler / nur Schüler. Es erscheint nur, wenn das Modul aktiv ist.

## 10. Datenschutz

* Rechtsgrundlage und Verarbeitungsverzeichnis klärt die Schule. Das System unterstützt
  sie mit Zustimmung der Eltern, Protokoll und Löschfrist.
* Keine E-Mail-Adressen, keine Telefonnummern, keine Fotos, keine Gesundheitsdaten.
* Schüler sehen keine Namen anderer Eltern oder Schüler, also keine Lesebestätigungen,
  keine Listen und keine Kommentare.
* Logs enthalten nur die Konto-ID, keine Namen.
* Die Datenschutz-Auskunft eines Elternteils enthält das Schülerkonto des Kindes.

## 11. Arbeitspakete

| # | Paket | Abhängig |
|---|---|---|
| S-01 | Migrationen (§3), `User::isStudent()`, Scope `withoutStudents()`, Modul + Einstellungen | – |
| S-02 | Keycloak-Login: Schüler per Claim erkennen und nie als normales Konto anlegen (unabhängig dringlich) | – |
| S-03 | Middleware `RestrictStudentAccounts`, `Gate::before`, Routen-Test (§5) | S-01 |
| S-04 | Schutz in Gruppen- und Familienlogik (`group_user`-Sperre, FamilyBuilder, Importe, Schuljahreswechsel) | S-01 |
| S-05 | `posts.zielgruppe`, Editor-Feld, `StudentPostQuery`, Schüler-Startseite mit Nachrichten und Terminen | S-03 |
| S-06 | Vertretungs- und Stundenplan für Schüler | S-03 |
| S-07 | Login mit Benutzername, Einmal-Code/QR, Verwaltung der Schülerkonten (Reiter, Sperren, Codes) | S-03 |
| S-08 | SSO: Zuordnung über `children.ucs_username`, JIT-Anlage | S-02, S-07 |
| S-09 | UCS-Provisionierung inkl. Aufräumen (§7) | S-08 |
| S-10 | Eltern-Sicht: Status, Zustimmung, Codes | S-07 |
| S-11 | Protokoll, Löschfrist, Datenschutz-Auskunft | S-01 |
| S-12 | API `v1/student/*`, Token-Fähigkeit `student`, `account_type` im Bootstrap | S-05, S-06 |
| S-13 | App: Schüleransicht, Login per Code/QR, Projektregel anpassen | S-12 |
| S-14 | Messenger Schüler ↔ Lehrkräfte (optional je Schule) | S-03 |

## 12. Entscheidungen und offene Fragen

| Frage | Entscheidung |
|---|---|
| Ab welcher Klasse / welchem Alter? | **offen** – vorerst frei wählbar je Schule (`freigegebene_gruppen`) |
| Zustimmung der Eltern | **Option** der Schule (`eltern_zustimmung`) |
| Schüler schreiben Lehrkräften | **Entscheidung der Schule** (`messenger_lehrkraefte`) |
| Erkennung im Keycloak | **Rolle** im Token (`keycloak_schuelerrolle`) |
| App für Schüler | **ja**, eigene Ansicht in der bestehenden App (§8) |

Noch offen:

1. Mindestalter bzw. Klassenstufe: Gibt es eine Vorgabe für alle Schulen oder bleibt es
   ganz bei der Schule?
