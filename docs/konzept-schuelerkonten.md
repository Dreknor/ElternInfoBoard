# Schülerkonten (optional)

Stand: 04.11.2026 · Konzept geprüft, Entscheidungen eingearbeitet (§12) · **umgesetzt: S-01 bis S-12** (S-13 App und S-14 Messenger offen)

Schulen können Schülerinnen und Schülern ein eigenes, stark eingeschränktes Konto geben.
Wo ein Schul-Login (SSO) besteht, melden sich Schüler darüber an. Ohne SSO gibt es einen
Benutzernamen statt einer E-Mail-Adresse. Die Übernahme aus UCS@school ist optional.
Ist die Funktion aus, ändert sich nichts und es gibt keinen Hinweis auf sie.

---

## 1. Ziele und Grundsätze

* **Optional und instanzgesperrt:** eigenes Modul „Schülerkonten“, standardmäßig inaktiv. Es
  steht in `config/modules.php` → `restricted` und ist nur auf Instanzen verfügbar, deren `.env`
  es per `MODULES_UNLOCKED` freischaltet. Erst dann kann der Schuladmin es aktivieren.
* **Ausgeschaltet heißt unsichtbar (§1.1).**
* **Grundsätzlich verboten:** Ein Schülerkonto darf nur, was ausdrücklich freigegeben ist.
  Neue Funktionen sind für Schüler automatisch gesperrt.
* **Schüler sind keine Eltern:** Schülerkonten stehen nie in `group_user`, nie in einer
  Familie und haben nie Elternrechte. Dadurch tauchen sie in keinem Verteiler, keiner
  Statistik und keinem Pool auf (Reinigung, Pflichtstunden, Rückmeldungen …).
* **Keine E-Mail nötig:** Anmeldung über SSO oder über einen Benutzernamen.
* **Datensparsam:** Es werden nur Name, Klasse und Login gespeichert, keine Kontaktdaten.
  Gesundheitsdaten und Krankmeldungen bleiben für Schüler unsichtbar.
* **Kein Mindestalter im System:** Welche Kinder ein Konto bekommen, entscheidet die Schule,
  indem sie Konten anlegt (manuell oder per Import, §9.1).

### 1.1 Ausgeschalteter Zustand

Es gibt genau eine Abfrage: `App\Support\StudentAccounts::enabled()` (Modul aktiv und für die
Instanz freigeschaltet). Ist sie `false`, sehen Verwaltung, Eltern, App und MCP-Server genau
das, was sie ohne Schülerkonten sehen. Alle üblichen Felder bleiben, alle schülerbezogenen
fehlen:

* Login-Maske: Beschriftung „E-Mail-Adresse“ (kein „oder Benutzername“).
* Nachrichten und Termine: kein Feld „Sichtbar für“, keine Badges, Filter oder Vorlagenfelder.
* Benutzerverwaltung ohne Reiter „Schülerkonten“, Kinderverwaltung ohne Spalte „Konto“,
  Import ohne Schülerkonten-Import.
* Eltern-Bereich „Kinder“ ohne Kontostatus, Zustimmung oder Codes.
* Rechteverwaltung ohne die Rechte des Moduls (Modulspalte `Schülerkonten`). Beim Speichern
  bleiben die ausgeblendeten Rechte der Rollen erhalten.
* Hilfe-Seiten, Changelog, Datenschutz-Texte ohne Schülerkonten.
* API: kein `account_type` in `v1/bootstrap` bzw. `UserResource`, kein `zielgruppe` in Post-
  und Termin-Ressourcen. Ältere App-Versionen zeigen dadurch nichts an.
* Einstellungen ohne Reiter „Schülerkonten“.

Ausgenommen ist der Schutz aus S-02 (Keycloak-Feld „Rollen für Schülerinnen und Schüler“,
Login-Meldung, API-Fehlercode `student`). Er gilt bewusst immer.

**Modul wird wieder ausgeschaltet:** Vorhandene Schülerkonten können sich nicht mehr anmelden
(Konten bleiben erhalten). Beiträge mit `zielgruppe = schueler` bleiben für Eltern
unsichtbar, `eltern_schueler` verhält sich wie `eltern`.

Ein Test schaltet das Modul aus und prüft Login, Editor, Benutzerverwaltung und API-Antworten
auf schülerbezogene Felder.

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
| Viele Stellen setzen eine E-Mail voraus (`EnsureUserHasEmail`, Passwort-Reset, Login-Link, Benachrichtigungen, Exporte) | `EnsureUserHasEmail` würde UCS-Schüler ohne E-Mail endlos auf die Einstellungen leiten. Schülerkonten werden dort und in allen Mail-Pfaden ausgenommen (S-03/S-04) |
| Allgemeiner Keycloak-Login (`LoginController::handleKeycloakCallback`) sucht das Konto über die E-Mail und **legt unbekannte Konten automatisch als normale Konten an** | Behoben in S-02 |

Daraus folgt: Schülerkonten bekommen **keine** Gruppenmitgliedschaft. Ihre Klassen werden
über das verknüpfte Kind bestimmt (`Child::class`, `Child::group`, `child_group`,
`Child::derivedGroupIds()`).

## 3. Datenmodell (nur additiv)

* `users.account_type`: `standard` (Standard) | `student`. Das ist ein festes Merkmal,
  keine Rolle, damit niemand es versehentlich über die Rollenverwaltung vergibt.
* `users.username` (nullable, eindeutig): **der Login** ohne E-Mail. Bei UCS-Schülern wird er
  aus `ucs_username` übernommen; `ucs_username` bleibt der Anker für den Abgleich mit UCS.
* `users.email` nullable. Für `account_type = student` ist die E-Mail immer leer, auch wenn
  das SSO eine liefert. So gehen keine Mails an Schüler, und die Adresse kann nicht mit einem
  Elternkonto kollidieren.
* `children.user_id` (nullable, eindeutig, FK `users`, bei Löschung `null`): ein Konto je Kind.
* `posts.zielgruppe` und `termine.zielgruppe`: `eltern` (Standard) | `eltern_schueler` |
  `schueler`. Bestehende Einträge bleiben `eltern`. **Eltern sehen `schueler` nie**, auch nicht
  in Mail, Newsletter, Push, Lesebestätigung, Erinnerungen, API und MCP-Server.
* Recht `create student posts` (Modul `Schülerkonten`): nur damit erscheint das Feld
  „Sichtbar für“ und lässt sich ein anderer Wert als `eltern` speichern.
* Protokoll über Anlage, Sperre, Code-Ausgabe und Löschung im vorhandenen Log-System
  (eigene Kategorie „Schülerkonten“, nur Konto-ID, keine Inhalte). Keine eigene Tabelle.
* Einstellungen `SchuelerSetting` (siehe §9), Modul „Schülerkonten“.

`User::isStudent()` ist die einzige Abfrage, die der restliche Code für die Kontoart benutzt;
`User::withoutStudents()` und `User::students()` sind die zugehörigen Scopes.

## 4. Was ein Schülerkonto darf

| Bereich | Schüler | Hinweis |
|---|---|---|
| Nachrichten | nur `zielgruppe` ≠ `eltern` **und** an eine Klasse/Gruppe des Kindes | keine Lesebestätigung, Rückmeldung, Umfrage, Kommentare, Reaktionen |
| Anhänge | Dateien dieser Nachrichten | eigene Download-Route, prüft über `StudentPostQuery` |
| Termine | nur Termine mit `zielgruppe` ≠ `eltern` der eigenen Gruppen | kein iCal-Abo mit Elterndaten |
| Vertretungsplan | eigene Klasse | ohne Abwesenheiten von Lehrkräften, falls die Schule das wünscht |
| Stundenplan | eigene Klasse | |
| AGs | ansehen, nur AGs der eigenen Gruppen und Klassen | Anmeldung bleibt bei den Eltern |
| Eigenes Profil | Name ansehen, Passwort ändern (nur Login ohne SSO) | keine Kontaktdaten |
| Push in der App | nur bei Schülerbeiträgen | Titel ja, kein Text (§8) |

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
   `student.` sowie eine feste Liste von Pfaden durch (Login, Logout, Assets). Unbenannte
   Routen, Broadcasting und Datei-Downloads sind damit gesperrt. Alles andere ergibt 404, wie
   bei inaktiven Modulen. Ist das Modul aus, werden Schülerkonten sofort abgemeldet.
2. **Rechte:** Ein `Gate::before` gibt Schülern bei jeder Berechtigung `false`, außer bei den
   wenigen Schülerrechten. Das wirkt auf `can()` und Policies, nicht auf direkte Abfragen wie
   `hasPermissionTo()`. Darum werden Rollen und Rechte an Schülerkonten gar nicht vergeben
   (`UserService::syncRoles` lehnt das ab, Test dazu).
3. **Daten:**
   * Schülerkonten werden nie in `group_user` geschrieben. `GroupsController`,
     `GroupMembershipService`, Importe und der Schuljahreswechsel (`SchoolYearService`)
     prüfen `isStudent()`. Zusätzlich verhindert ein Model-Event bzw. Observer das Schreiben
     (kein Datenbank-Trigger, damit Tests mit SQLite gleich laufen).
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
  dann `ucs_username`. Die Zuordnung zum Kind läuft über `children.ucs_username`.
  **Nie über die E-Mail-Adresse.**
* Ob jemand Schüler ist, ergibt sich **aus der Rolle** (umgesetzt in
  `App\Services\Auth\SsoStudentDetector`):
  * **UCS@school:** Claim `ucsschoolRole` mit dem Wert `student:school:<Schule>`. Die Rolle
    `student` gilt immer als Schüler, ohne Einstellung. Der Claim kommt nur ins Token, wenn
    er im Keycloak eingerichtet ist (§6.3).
  * **Allgemeiner Keycloak:** Realm- oder Client-Rolle im Token (`realm_access.roles`,
    `resource_access.<client>.roles`, `roles`). Die Rollennamen stehen in der vorhandenen
    Einstellung „Rollen für Schülerinnen und Schüler“ (`KeycloakSetting::$student_roles`).
  * E-Mail-Domain oder Gruppennamen werden nicht ausgewertet.
* Ist kein Konto vorhanden:
  * Bei aktivierter **UCS-Provisionierung** (§7) wurde es bereits angelegt.
  * Sonst und bei `jit_anlage = an`: Das Konto wird beim ersten Login angelegt, aber nur,
    wenn ein passendes Kind existiert und dessen Klasse in `freigegebene_gruppen` steht (§9).
  * Sonst erscheint der Hinweis „Für Sie ist kein Zugang freigeschaltet.“
* **S-02 (umgesetzt, gilt auch ohne Schülerkonten):** Der allgemeine Keycloak-Login legt
  Konten mit der Schülerrolle nie als normale Konten an. Die automatische Anlage lässt sich
  ganz abschalten.

### 6.2 Ohne SSO: Benutzername

* Das Anmeldefeld heißt „E-Mail oder Benutzername“, **nur** wenn `login_benutzername` an ist.
  Schülerkonten melden sich nur mit Benutzername an.
* **Benutzername:** `vorname.nachname` in Kleinbuchstaben, Umlaute umgeschrieben
  (ä → ae, ß → ss), Doppelnamen mit Bindestrich, Sonderzeichen entfernt. Bei Kollision eine
  Zahl anhängen (`max.muster2`). Kein `@` erlaubt, damit er nie mit einer E-Mail verwechselt
  wird. Die Verwaltung kann ihn ändern.
* **Erstzugang über einen Code:** Die Verwaltung (oder, falls erlaubt, ein Elternteil mit
  `can_manage`) erzeugt einen Einmal-Code bzw. QR-Code (gültig 7 Tage). Damit setzt der
  Schüler sein Passwort. Das nutzt den vorhandenen Login-Code-Mechanismus aus
  `API/V1/AuthController` (`app_login_code`).
* **Codes verteilen:** Ohne E-Mail nur auf Papier: Die Verwaltung druckt eine PDF mit einem
  Zettel je Kind (§9.1). Eltern mit `eltern_codes` sehen den Code im Bereich „Kinder“.
* **Passwort vergessen:** Ohne E-Mail gibt es kein Zurücksetzen per Mail. Verwaltung oder
  Eltern erzeugen einen neuen Code.
* Strengere Sperre bei Fehlversuchen (`throttle:login-student`). App-Tokens von Schülern
  laufen nach 30 Tagen ab (Sanctum-Ablauf je Token). Für die Web-Sitzung wird die Dauer in
  `RestrictStudentAccounts` geprüft (Zeitpunkt der Anmeldung in der Sitzung), weil Laravel
  nur eine Sitzungsdauer für alle kennt.

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

## 7. UCS@school-Provisionierung und Aufräumen

Einstellung `ucs_provisionierung` (Standard: aus), nur wirksam bei `UcsSetting::enabled`.

* **An:** `sync:ucs-parents` legt für Kelvin-Schüler der freigegebenen Klassen Schülerkonten
  an (`account_type = student`, `username = ucs_username`, `ucs_source = kelvin`, keine
  E-Mail, kein Passwort) und verknüpft sie mit dem Kind (`children.user_id`).
* **Aus:** Es werden keine Konten vorab angelegt. Bei SSO entstehen sie erst beim ersten
  Login (JIT, §6.1) oder durch manuelle Anlage bzw. Import (§9.1).
* Es kommen keine Daten außer Vorname, Nachname, Benutzername und Klassen in die
  Datenbank.

**Aufräumen (für alle Schülerkonten, egal wie angelegt):**

* Kind hat `status = left`, verschwindet aus UCS oder wechselt beim Schuljahreswechsel in eine
  Klasse, für die keine Konten vorgesehen sind: Das Konto wird sofort deaktiviert
  (`is_active = false`) und nach `loeschen_nach_tagen` (Standard 30) gelöscht.
* Wird das Kind vorher wieder aktiv bzw. freigegeben, kann die Verwaltung das Konto
  reaktivieren.
* Der UCS-Sync ändert oder löscht nur Konten mit `ucs_source = kelvin`; das Aufräumen der
  übrigen Konten übernimmt ein täglicher Befehl.

## 8. App (elterninfo_app)

Entschieden: **Schüler bekommen eine eigene Ansicht in der App.** Es wird bewusst keine
zweite App. Die bestehende App zeigt je nach Kontoart eine andere Oberfläche. Die
Projektregel der App („nur für Eltern“) wird entsprechend angepasst.

* **Backend:** Endpunkte unter `v1/student/*` (Start, Nachrichten, Termine, Vertretungs-
  und Stundenplan, AGs, Profil, ggf. Messenger). Schüler-Tokens tragen die Sanctum-Fähigkeit
  `student` und erreichen nur diese Endpunkte. Alle übrigen `v1`-Endpunkte liefern 403
  bzw. 404 (gleiche Freigabeliste wie §5).
* `v1/bootstrap` liefert `account_type`, **nur wenn das Modul aktiv ist** (§1.1). Daran
  entscheidet die App, welche Tabs sie zeigt; fehlt das Feld, ist es ein Elternkonto.
* **Login in der App:** über SSO (wie Eltern), Benutzername und Passwort oder den
  Einmal-Code/QR (§6.2).
* **Push:** nur für Schülerbeiträge und Termine, mit Titel, ohne Text.
* **Offline-Cache:** wie bei Eltern verschlüsselt. Beim Wechsel der Kontoart wird er
  gelöscht.
* **Reihenfolge:** Die Weboberfläche kommt zuerst. Die App folgt, sobald S-03 bis S-07
  stehen. Bis dahin lehnen `POST v1/token` und der SSO-Austausch Schülerkonten mit 403 ab.

## 9. Einstellungen (`SchuelerSetting`, Reiter „Schülerkonten“)

An/aus ist allein das Modul (§1), es gibt keinen zweiten Schalter.

| Einstellung | Standard | Bedeutung |
|---|---|---|
| `login_sso` | an | Login über SSO erlauben |
| `login_benutzername` | aus | Login mit Benutzername + Passwort (ohne SSO) |
| `jit_anlage` | aus | Konto beim ersten SSO-Login anlegen |
| `ucs_provisionierung` | aus | Konten per UCS-Sync anlegen |
| `freigegebene_gruppen` | leer | Klassen/Bereiche, für die Konten **automatisch** entstehen dürfen (JIT, UCS). Leer heißt: keine automatische Anlage. Manuelle Anlage und Import sind davon unabhängig |
| `eltern_zustimmung` | aus | Konto erst aktiv, wenn ein Elternteil zugestimmt hat |
| `messenger_lehrkraefte` | aus | Schüler dürfen Lehrkräften schreiben (§4) |
| `eltern_codes` | aus | Eltern dürfen Einmal-Codes erzeugen |
| `loeschen_nach_tagen` | 30 | Löschfrist nach Deaktivierung (§7) |
| `vertretung_abwesenheiten` | aus | Abwesenheiten von Lehrkräften auch Schülern zeigen |

Die Schülerrolle im Keycloak steht weiter in `KeycloakSetting::$student_roles` (S-02).

**Eltern-Sicht:** Im Bereich „Kinder“ steht je Kind der Status des Schülerkontos (aktiv,
gesperrt, nicht vorhanden). Bei `eltern_codes` können Eltern einen neuen Code erzeugen.

**Zustimmung bei mehreren Bezugspersonen:** Sagt eine Bezugsperson mit Sorgerecht ja, ist das
Konto aktiv. Widerspricht eine Bezugsperson, wird es gesperrt, bis wieder jemand zustimmt. Der
letzte Stand gilt, jede Änderung wird protokolliert.

**Beiträge:** Im Editor steht unter den Gruppen das Feld „Sichtbar für“ mit den Werten
Eltern / Eltern und Schüler / nur Schüler. Es erscheint nur, wenn das Modul aktiv ist **und**
der Nutzer das Recht `create student posts` hat. Eine Vorschau „So sehen es Schüler“ hilft,
Fehler zu vermeiden (S-05, optional).

### 9.1 Schülerkonten-Import (S-07b)

Name „Schülerkonten-Import“, damit er nicht mit dem vorhandenen „Schüler-Import mit
Schüler-ID“ (`SchuelerImportService`, legt Kinder und Elternkonten an) verwechselt wird. Er
erscheint nur bei aktivem Modul.

1. Datei mit einer Zeile je Kind: `Schüler-ID` (Pflicht, = `children.external_id`),
   optional `Benutzername` und `Konto` (`ja` = anlegen/aktivieren, `nein` = deaktivieren).
   Namen sind nicht nötig, das Kind existiert schon.
2. Abgleich nur über die Schüler-ID, nie über den Namen. Unbekannte ID oder Kind mit
   `status = left` → Klärungsfall im Bericht, keine Änderung.
3. Vorschau → Bestätigen wie bei Kinder- und Vereinsimport; die Vorschau ändert nichts.
4. Leerer Benutzername → wird nach §6.2 erzeugt. Vorhandene Konten werden nur verknüpft bzw.
   aktiviert, nie doppelt angelegt.
5. Ergebnis: PDF mit einem Zettel je neu angelegtem Konto (Name, Klasse, Benutzername,
   Einmal-Code, QR), sortiert nach Klasse. Die Codes sind danach nur noch als Hash gespeichert.
6. UCS-Kinder (`ucs_source = kelvin`) werden übersprungen, wenn `ucs_provisionierung` an ist.
7. Ohne Datei: Schaltfläche „Konten für diese Klasse anlegen“ in der Kinderverwaltung, gleiche
   Logik und gleiche PDF.

## 10. Datenschutz

* Rechtsgrundlage und Verarbeitungsverzeichnis klärt die Schule. Das System unterstützt
  sie mit Zustimmung der Eltern, Protokoll und Löschfrist.
* Keine E-Mail-Adressen, keine Telefonnummern, keine Fotos, keine Gesundheitsdaten.
* Schüler sehen keine Namen anderer Eltern oder Schüler, also keine Lesebestätigungen,
  keine Listen und keine Kommentare.
* Logs enthalten nur die Konto-ID, keine Namen.
* Die Datenschutz-Auskunft eines Elternteils enthält das Schülerkonto des Kindes.

## 11. Arbeitspakete

Reihenfolge: S-01, S-03, dann S-04 bis S-07, direkt danach der Import S-07b.

| # | Paket | Abhängig | Stand |
|---|---|---|---|
| S-01 | Migrationen (§3), `User::isStudent()`, Scopes, `StudentAccounts::enabled()`, Modul (instanzgesperrt), Recht, Einstellungen, Rechteverwaltung blendet Modulrechte aus | – | umgesetzt |
| S-02 | SSO-Login erkennt Schüler an der Rolle und legt sie nie als normales Konto an; automatische Kontoanlage abschaltbar | – | umgesetzt |
| S-03 | Middleware `RestrictStudentAccounts`, `Gate::before`, Routen-Test, Login-Sperre bei ausgeschaltetem Modul (§1.1, §5) | S-01 | umgesetzt |
| S-04 | Schutz in Gruppen- und Familienlogik (`group_user`-Sperre, FamilyBuilder, Importe, Schuljahreswechsel), Ausnahme in `EnsureUserHasEmail` und Mail-Pfaden | S-01 | umgesetzt |
| S-05 | `zielgruppe` in Editor und Abfragen (Eltern sehen `schueler` nie), `StudentPostQuery`, Anhänge, Schüler-Startseite | S-03 | umgesetzt |
| S-06 | Vertretungs- und Stundenplan, AGs für Schüler | S-03 | umgesetzt |
| S-07 | Login mit Benutzername, Einmal-Code/QR, Verwaltung der Schülerkonten (Reiter, Sperren, Codes) | S-03 | umgesetzt |
| S-07b | Schülerkonten-Import und Code-PDF (§9.1) | S-07 | umgesetzt |
| S-08 | SSO: Zuordnung über `children.ucs_username`, JIT-Anlage | S-02, S-07 | umgesetzt |
| S-09 | UCS-Provisionierung (§7) | S-08 | umgesetzt |
| S-10 | Eltern-Sicht: Status, Zustimmung, Codes | S-07 | umgesetzt |
| S-11 | Protokoll, Aufräumen und Löschfrist für alle Konten (§7), Datenschutz-Auskunft | S-01 | umgesetzt |
| S-12 | API `v1/student/*`, Token-Fähigkeit `student`, `account_type` im Bootstrap | S-05, S-06 | umgesetzt |
| S-13 | App: Schüleransicht, Login per Code/QR, Projektregel anpassen | S-12 | |
| S-14 | Messenger Schüler ↔ Lehrkräfte (optional je Schule) | S-03 | |

## 12. Entscheidungen

| Frage | Entscheidung |
|---|---|
| Ab welcher Klasse / welchem Alter? | **keine Vorgabe**, die Schule entscheidet durch die Konten, die sie anlegt |
| Wer darf das Modul einschalten? | **instanzgesperrt** (`MODULES_UNLOCKED`), danach der Schuladmin |
| Spuren aus S-02 bei ausgeschaltetem Modul | **in Ordnung**, Schutzfunktion |
| Import | **Schülerkonten-Import** über die Schüler-ID mit Code-PDF (§9.1) |
| Sehen Eltern „nur Schüler“-Beiträge? | **nein** |
| Wer legt Schülerbeiträge an? | **per Recht** `create student posts` |
| Zustimmung der Eltern | **Option** der Schule; ein Ja genügt, bis jemand widerspricht |
| Schuljahreswechsel in nicht vorgesehene Klasse | Konto **deaktivieren und später löschen** |
| AGs | nur AGs der eigenen **Gruppen und Klassen** |
| Schüler schreiben Lehrkräften | **Entscheidung der Schule** (`messenger_lehrkraefte`) |
| Erkennung im Keycloak | **Rolle** im Token (`KeycloakSetting::$student_roles`) |
| App für Schüler | **ja**, eigene Ansicht in der bestehenden App (§8) |

Prüfbericht mit allen Punkten: Claude-Dokument „Prüfung Konzept Schülerkonten“ (04.11.2026).
